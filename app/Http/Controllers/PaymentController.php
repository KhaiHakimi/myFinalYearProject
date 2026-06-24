<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\Schedule;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Stripe\Stripe;
use Stripe\Checkout\Session as StripeSession;

class PaymentController extends Controller
{
    public function __construct()
    {
        Stripe::setApiKey(config('services.stripe.secret'));
    }

    /**
     * Create a Stripe Checkout Session and redirect the user.
     */
    public function checkout(Request $request)
    {
        $validated = $request->validate([
            'schedule_id' => 'required|exists:schedules,id',
            'passenger_name' => 'required|string|max:255',
            'passenger_email' => 'required|email|max:255',
            'passenger_phone' => 'nullable|string|max:20',
            'quantity' => 'required|integer|min:1|max:10',
        ]);

        $schedule = Schedule::with(['ferry', 'origin', 'destination'])->findOrFail($validated['schedule_id']);

        // Prevent overbooking: check capacity across all channels
        if (! $schedule->hasAvailableSeats($validated['quantity'])) {
            $available = $schedule->availableSeats();
            $message = $available > 0
                ? "Only {$available} seat(s) remaining on this sailing."
                : 'This sailing is fully booked.';

            return redirect()->back()->with('error', $message);
        }

        $unitPrice = $schedule->price;
        $totalAmount = $unitPrice * $validated['quantity'];

        // Create booking record with 'pending' status
        $booking = Booking::create([
            'user_id' => auth()->id(),
            'schedule_id' => $schedule->id,
            'passenger_name' => $validated['passenger_name'],
            'passenger_email' => $validated['passenger_email'],
            'passenger_phone' => $validated['passenger_phone'] ?? null,
            'quantity' => $validated['quantity'],
            'total_amount' => $totalAmount,
            'currency' => 'MYR',
            'payment_status' => 'pending',
            'booking_reference' => Booking::generateReference(),
        ]);

        // Build line item description
        $description = sprintf(
            '%s → %s | %s | %s',
            $schedule->origin->name,
            $schedule->destination->name,
            $schedule->ferry->name,
            $schedule->departure_time->format('d M Y, h:i A')
        );

        // Create Stripe Checkout Session
        $session = StripeSession::create([
            'payment_method_types' => ['card'],
            'line_items' => [[
                'price_data' => [
                    'currency' => 'myr',
                    'product_data' => [
                        'name' => 'Ferry Ticket — ' . $schedule->ferry->name,
                        'description' => $description,
                    ],
                    'unit_amount' => (int) ($unitPrice * 100), // Stripe uses cents
                ],
                'quantity' => $validated['quantity'],
            ]],
            'mode' => 'payment',
            'success_url' => route('payment.success') . '?session_id={CHECKOUT_SESSION_ID}',
            'cancel_url' => route('payment.cancel') . '?booking_ref=' . $booking->booking_reference,
            'customer_email' => $validated['passenger_email'],
            'metadata' => [
                'booking_id' => $booking->id,
                'booking_reference' => $booking->booking_reference,
            ],
        ]);

        // Save Stripe session ID to the booking
        $booking->update([
            'stripe_session_id' => $session->id,
        ]);

        // Redirect to Stripe Checkout
        return Inertia::location($session->url);
    }

    /**
     * Handle successful payment return from Stripe.
     */
    public function success(Request $request)
    {
        $sessionId = $request->query('session_id');

        if (!$sessionId) {
            return redirect()->route('schedules.index')->with('error', 'Invalid payment session.');
        }

        try {
            $session = StripeSession::retrieve($sessionId);

            $booking = Booking::where('stripe_session_id', $sessionId)->first();

            if ($booking && $session->payment_status === 'paid') {
                $booking->update([
                    'payment_status' => 'paid',
                    'stripe_payment_intent' => $session->payment_intent,
                ]);

                // Update seat counts across all channels
                $booking->schedule->recalculateSeats();
            }

            return Inertia::render('Payment/Success', [
                'booking' => $booking ? $booking->load('schedule.ferry', 'schedule.origin', 'schedule.destination') : null,
                'session' => [
                    'payment_status' => $session->payment_status,
                    'amount_total' => $session->amount_total / 100,
                    'currency' => strtoupper($session->currency),
                ],
            ]);
        } catch (\Exception $e) {
            return redirect()->route('schedules.index')->with('error', 'Could not verify payment.');
        }
    }

    /**
     * Handle cancelled payment.
     */
    public function cancel(Request $request)
    {
        $bookingRef = $request->query('booking_ref');

        // Mark booking as failed
        if ($bookingRef) {
            Booking::where('booking_reference', $bookingRef)
                ->where('payment_status', 'pending')
                ->update(['payment_status' => 'failed']);
        }

        return Inertia::render('Payment/Cancel', [
            'bookingRef' => $bookingRef,
        ]);
    }

    /**
     * Show user's booking history.
     */
    public function bookings()
    {
        $bookings = Booking::where('user_id', auth()->id())
            ->with(['schedule.ferry', 'schedule.origin', 'schedule.destination'])
            ->orderBy('created_at', 'desc')
            ->get();

        return Inertia::render('Payment/Bookings', [
            'bookings' => $bookings,
        ]);
    }

    /**
     * View a printable ticket for a paid booking.
     */
    public function ticket(Booking $booking)
    {
        // Ensure user owns this booking or is an admin
        if ($booking->user_id !== auth()->id() && !auth()->user()->is_admin) {
            abort(403);
        }

        if ($booking->payment_status !== 'paid') {
            return redirect()->route('bookings.index')->with('error', 'Ticket is not available for unpaid bookings.');
        }

        $booking->load(['schedule.ferry', 'schedule.origin', 'schedule.destination']);

        return Inertia::render('Payment/Ticket', [
            'booking' => $booking,
        ]);
    }

    /**
     * Admin booking analytics data.
     */
    public function analytics()
    {
        // Revenue by day (last 30 days)
        $revenueByDay = Booking::where('payment_status', 'paid')
            ->where('created_at', '>=', now()->subDays(30))
            ->selectRaw('DATE(created_at) as date, SUM(total_amount) as revenue, COUNT(*) as count')
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        // Popular routes (top 5)
        $popularRoutes = Booking::where('payment_status', 'paid')
            ->with(['schedule.origin', 'schedule.destination'])
            ->selectRaw('schedule_id, COUNT(*) as booking_count, SUM(total_amount) as total_revenue')
            ->groupBy('schedule_id')
            ->orderByDesc('booking_count')
            ->limit(5)
            ->get()
            ->map(function ($item) {
                $item->load('schedule.origin', 'schedule.destination', 'schedule.ferry');
                return $item;
            });

        // Bookings by status
        $byStatus = Booking::selectRaw('payment_status, COUNT(*) as count')
            ->groupBy('payment_status')
            ->pluck('count', 'payment_status');

        // Revenue by ferry
        $revenueByFerry = Booking::where('payment_status', 'paid')
            ->join('schedules', 'bookings.schedule_id', '=', 'schedules.id')
            ->join('ferries', 'schedules.ferry_id', '=', 'ferries.id')
            ->selectRaw('ferries.name as ferry_name, SUM(bookings.total_amount) as revenue, COUNT(*) as count')
            ->groupBy('ferries.name')
            ->orderByDesc('revenue')
            ->get();

        // Total stats
        $totalRevenue = Booking::where('payment_status', 'paid')->sum('total_amount');
        $totalBookings = Booking::count();
        $paidBookings = Booking::where('payment_status', 'paid')->count();

        // AI Performance Metrics (mocked via cache)
        $aiAccuracy = \Illuminate\Support\Facades\Cache::get('ai_accuracy', 92.4);
        $aiEngagement = \Illuminate\Support\Facades\Cache::get('ai_engagement', 85.1);

        return response()->json([
            'revenue_by_day' => $revenueByDay,
            'popular_routes' => $popularRoutes,
            'by_status' => $byStatus,
            'revenue_by_ferry' => $revenueByFerry,
            'total_revenue' => $totalRevenue,
            'total_bookings' => $totalBookings,
            'paid_bookings' => $paidBookings,
            'ai_metrics' => [
                'accuracy' => $aiAccuracy,
                'engagement' => $aiEngagement,
            ],
        ]);
    }
}

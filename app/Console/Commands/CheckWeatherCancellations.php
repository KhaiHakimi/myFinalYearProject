<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Schedule;
use App\Models\Booking;
use App\Services\GeoIntelligenceService;
use App\Mail\TicketCancelledDueToWeather;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class CheckWeatherCancellations extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'weather:check-cancellations';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Checks upcoming ferry schedules and automatically cancels them if weather risk is too high.';

    /**
     * Execute the console command.
     */
    public function handle(GeoIntelligenceService $geoIntelligence)
    {
        $this->info("Starting weather cancellation check...");

        // Get schedules departing in the next 24 hours that are not yet cancelled or departed
        $schedules = Schedule::whereIn('status', ['open', 'full'])
            ->where('departure_time', '>=', now())
            ->where('departure_time', '<=', now()->addHours(24))
            ->with(['origin', 'destination', 'bookings'])
            ->get();

        if ($schedules->isEmpty()) {
            $this->info("No upcoming schedules to check.");
            return;
        }

        foreach ($schedules as $schedule) {
            $this->info("Checking schedule {$schedule->id} ({$schedule->origin->name} to {$schedule->destination->name})");

            // Analyze route viability
            $routeRisk = $geoIntelligence->analyzeRouteViability($schedule->origin, $schedule->destination);
            
            // If the route risk score is >= 85, or max wave height > 2.5m, we cancel
            if ($routeRisk['route_risk_score'] >= 85 || $routeRisk['max_wave_height'] > 2.5) {
                $this->warn("Schedule {$schedule->id} exceeded risk threshold (Score: {$routeRisk['route_risk_score']}). Cancelling...");

                // 1. Mark schedule as cancelled
                $schedule->update(['status' => 'cancelled']);

                // 2. Notify passengers and mark bookings for refund
                $paidBookings = $schedule->bookings->where('payment_status', 'paid');
                
                foreach ($paidBookings as $booking) {
                    try {
                        Mail::to($booking->passenger_email)->send(new TicketCancelledDueToWeather($booking, $schedule));
                        
                        // Mark booking as refunded (or pending refund depending on actual Stripe integration)
                        $booking->update(['payment_status' => 'refunded']);
                        
                        $this->info("Sent cancellation email to {$booking->passenger_email} (Booking Ref: {$booking->booking_reference})");
                    } catch (\Exception $e) {
                        Log::error("Failed to send cancellation email to {$booking->passenger_email}: " . $e->getMessage());
                        $this->error("Failed to send cancellation email to {$booking->passenger_email}.");
                    }
                }
            } else {
                $this->line("Schedule {$schedule->id} is safe (Score: {$routeRisk['route_risk_score']}).");
            }
        }

        $this->info("Weather cancellation check completed.");
    }
}

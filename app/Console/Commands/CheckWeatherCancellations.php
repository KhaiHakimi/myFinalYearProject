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

        // 1. ADVANCE WARNINGS (12 - 24 hours out)
        $this->info("Checking for advance warnings (12-24 hours)...");
        $warningSchedules = Schedule::whereIn('status', ['open', 'full'])
            ->where('weather_warning_sent', false)
            ->where('departure_time', '>', now()->addHours(12))
            ->where('departure_time', '<=', now()->addHours(24))
            ->with(['origin', 'destination', 'bookings'])
            ->get();

        foreach ($warningSchedules as $schedule) {
            $routeRisk = $geoIntelligence->analyzeRouteViability($schedule->origin, $schedule->destination, $schedule->departure_time);
            
            if ($routeRisk['route_risk_score'] >= 85 || $routeRisk['max_wave_height'] > 2.5) {
                $this->warn("Schedule {$schedule->id} at risk (Score: {$routeRisk['route_risk_score']}). Sending warnings...");
                
                $paidBookings = $schedule->bookings->where('payment_status', 'paid');
                foreach ($paidBookings as $booking) {
                    try {
                        Mail::to($booking->passenger_email)->send(new \App\Mail\TicketWeatherWarning($booking, $schedule));
                    } catch (\Exception $e) {
                        Log::error("Failed to send warning email for {$booking->passenger_email}: " . $e->getMessage());
                    }
                }
                
                $schedule->update(['weather_warning_sent' => true]);
            }
        }

        // 2. FINAL CANCELLATIONS (0 - 12 hours out)
        $this->info("Checking for final cancellations (0-12 hours)...");
        $cancelSchedules = Schedule::whereIn('status', ['open', 'full'])
            ->where('departure_time', '>=', now())
            ->where('departure_time', '<=', now()->addHours(12))
            ->with(['origin', 'destination', 'bookings'])
            ->get();

        if ($cancelSchedules->isEmpty() && $warningSchedules->isEmpty()) {
            $this->info("No upcoming schedules to check.");
            return;
        }

        foreach ($cancelSchedules as $schedule) {
            $this->info("Checking schedule {$schedule->id} ({$schedule->origin->name} to {$schedule->destination->name})");

            $routeRisk = $geoIntelligence->analyzeRouteViability($schedule->origin, $schedule->destination, $schedule->departure_time);
            
            if ($routeRisk['route_risk_score'] >= 85 || $routeRisk['max_wave_height'] > 2.5) {
                $this->warn("Schedule {$schedule->id} exceeded risk threshold (Score: {$routeRisk['route_risk_score']}). Cancelling...");

                $schedule->update(['status' => 'cancelled']);

                $paidBookings = $schedule->bookings->where('payment_status', 'paid');
                
                foreach ($paidBookings as $booking) {
                    try {
                        if (!empty($booking->stripe_payment_intent) && !str_starts_with($booking->stripe_payment_intent, 'pi_dummy')) {
                            \Stripe\Stripe::setApiKey(config('services.stripe.secret'));
                            \Stripe\Refund::create([
                                'payment_intent' => $booking->stripe_payment_intent,
                            ]);
                            $this->info("Issued Stripe refund for Booking Ref: {$booking->booking_reference}");
                        }

                        Mail::to($booking->passenger_email)->send(new TicketCancelledDueToWeather($booking, $schedule));
                        
                        $booking->update(['payment_status' => 'refunded']);
                        
                        $this->info("Sent cancellation email to {$booking->passenger_email}");
                    } catch (\Exception $e) {
                        Log::error("Failed to process refund/email for {$booking->passenger_email}: " . $e->getMessage());
                    }
                }
            } else {
                $this->line("Schedule {$schedule->id} is safe (Score: {$routeRisk['route_risk_score']}).");
            }
        }

        $this->info("Weather cancellation check completed.");
    }
}

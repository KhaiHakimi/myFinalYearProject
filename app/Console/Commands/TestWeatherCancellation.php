<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Port;
use App\Models\Ferry;
use App\Models\Schedule;
use App\Models\Booking;
use App\Models\User;
use App\Services\GeoIntelligenceService;

class TestWeatherCancellation extends Command
{
    protected $signature = 'test:weather-cancellation {email?}';
    protected $description = 'Creates a dummy booking and forces a weather cancellation to test email/refund logic.';

    public function handle()
    {
        $email = $this->argument('email') ?? 'test@example.com';
        
        // 1. Create or get required dummy data
        $origin = Port::firstOrCreate(['name' => 'Dummy Origin'], ['latitude' => 0, 'longitude' => 0]);
        $destination = Port::firstOrCreate(['name' => 'Dummy Destination'], ['latitude' => 1, 'longitude' => 1]);
        $ferry = Ferry::firstOrCreate(['name' => 'Dummy Ferry'], ['capacity' => 100]);
        
        // 2. Create schedule starting within 2 hours
        $schedule = Schedule::create([
            'ferry_id' => $ferry->id,
            'origin_port_id' => $origin->id,
            'destination_port_id' => $destination->id,
            'departure_time' => now()->addHour(),
            'arrival_time' => now()->addHours(2),
            'price' => 50,
            'status' => 'open'
        ]);
        
        $user = User::first() ?? User::factory()->create();

        // 3. Create paid booking
        $booking = Booking::create([
            'user_id' => $user->id,
            'schedule_id' => $schedule->id,
            'passenger_name' => 'Dummy Passenger',
            'passenger_email' => $email,
            'passenger_phone' => '123456789',
            'quantity' => 1,
            'total_amount' => 50,
            'currency' => 'usd',
            'payment_status' => 'paid',
            'stripe_payment_intent' => 'pi_dummy_' . uniqid(),
            'booking_reference' => Booking::generateReference(),
        ]);
        
        $this->info("Created dummy schedule {$schedule->id} and booking {$booking->id} for {$email}");

        // 4. Mock GeoIntelligenceService to force high risk (without Mockery for production compatibility)
        $mockGeoService = new class(app(\App\Services\WeatherService::class)) extends GeoIntelligenceService {
            public function analyzeRouteViability(Port $origin, Port $destination, \Carbon\Carbon $targetTime = null): array
            {
                return [
                    'is_deep_sea_risky' => true,
                    'max_wave_height' => 3.0,
                    'route_risk_score' => 90,
                    'checkpoints' => []
                ];
            }
        };
            
        app()->instance(GeoIntelligenceService::class, $mockGeoService);
        
        // 5. Run the cancellation command
        $this->info("Running weather cancellation check...");
        $this->call('weather:check-cancellations');
        
        $booking->refresh();
        $schedule->refresh();
        
        $this->info("====================================");
        $this->info("Test complete.");
        $this->info("Schedule Status: {$schedule->status}");
        $this->info("Booking Payment Status: {$booking->payment_status}");
        $this->info("Please check {$email} (or your mail logger e.g. Mailtrap/Telescope/log) to verify the email was sent.");
        $this->info("====================================");
    }
}

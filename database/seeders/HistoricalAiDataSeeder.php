<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Schedule;
use App\Models\Ferry;
use App\Models\Port;
use App\Models\WeatherData;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class HistoricalAiDataSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $ferries = Ferry::all();
        $ports = Port::all();

        if ($ferries->isEmpty() || $ports->count() < 2) {
            $this->command->warn('Not enough ferries or ports to seed AI data. Please run default seeders first.');
            return;
        }

        $this->command->info('Generating 100 realistic historical samples for AI training...');
        
        $samplesCount = 100;
        $cancelledCount = 0;
        
        for ($i = 0; $i < $samplesCount; $i++) {
            $ferry = $ferries->random();
            $origin = $ports->random();
            $destination = $ports->where('id', '!=', $origin->id)->random();
            
            // Random date in the last 60 days
            $departureTime = Carbon::now()->subDays(rand(1, 60))->addHours(rand(6, 20))->startOfHour();
            $arrivalTime = (clone $departureTime)->addHours(rand(1, 4));
            
            // Generate weather condition for this departure
            // Roughly 20% chance of bad weather
            $isBadWeather = (rand(1, 100) <= 20);
            
            if ($isBadWeather) {
                $windSpeed = rand(35, 65) + (rand(0, 99) / 100); // 35 - 65 km/h
                $waveHeight = rand(2, 5) + (rand(0, 99) / 100);  // 2 - 5 m
                $visibility = rand(1, 4) + (rand(0, 99) / 100);  // 1 - 4 km
                $riskStatus = 'High Risk';
                $status = 'cancelled';
                $cancelledCount++;
            } else {
                $windSpeed = rand(5, 25) + (rand(0, 99) / 100);  // 5 - 25 km/h
                $waveHeight = rand(0, 1) + (rand(0, 99) / 100);  // 0 - 1.9 m
                $visibility = rand(8, 20) + (rand(0, 99) / 100); // 8 - 20 km
                $riskStatus = 'Safe';
                // Small chance of cancellation for non-weather reasons
                if (rand(1, 100) <= 2) {
                    $status = 'cancelled';
                    $cancelledCount++;
                } else {
                    $status = rand(1, 100) <= 10 ? 'full' : 'open';
                }
            }

            DB::beginTransaction();
            try {
                // 1. Create Schedule
                $schedule = Schedule::create([
                    'ferry_id' => $ferry->id,
                    'origin_port_id' => $origin->id,
                    'destination_port_id' => $destination->id,
                    'departure_time' => $departureTime,
                    'arrival_time' => $arrivalTime,
                    'price' => rand(20, 100),
                    'total_seats' => $ferry->capacity ?? 100,
                    'booked_seats' => rand(10, $ferry->capacity ?? 100),
                    'status' => $status,
                ]);

                // 2. Create matching Weather Data for the origin port at departure time
                WeatherData::create([
                    'port_id' => $origin->id,
                    'recorded_at' => $departureTime,
                    'wind_speed' => $windSpeed,
                    'wave_height' => $waveHeight,
                    'visibility' => $visibility,
                    'tide_level' => rand(1, 3) + (rand(0, 99) / 100),
                    'precipitation' => $isBadWeather ? rand(10, 50) : rand(0, 5),
                    'risk_score' => $isBadWeather ? rand(70, 95) : rand(10, 30),
                    'risk_status' => $riskStatus,
                ]);
                
                DB::commit();
            } catch (\Exception $e) {
                DB::rollBack();
                $this->command->error("Failed to seed sample $i: " . $e->getMessage());
            }
        }
        
        $this->command->info("Successfully generated 100 AI training samples ($cancelledCount cancellations).");
    }
}

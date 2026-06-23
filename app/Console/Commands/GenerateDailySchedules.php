<?php

namespace App\Console\Commands;

use App\Models\Ferry;
use App\Models\Port;
use App\Models\Schedule;
use Carbon\Carbon;
use Illuminate\Console\Command;

class GenerateDailySchedules extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'schedules:generate-daily';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Simulates fetching data from external ferry operators and populates the daily schedule.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Connecting to external marine databases...');
        sleep(1); // Simulation effect
        $this->info('Fetching voyage manifests...');

        $routes = [
            // Langkawi
            ['origin' => 1, 'destination' => 3, 'price_min' => 18, 'price_max' => 23], // K.Perlis -> Kuah
            ['origin' => 3, 'destination' => 1, 'price_min' => 18, 'price_max' => 23],
            ['origin' => 2, 'destination' => 3, 'price_min' => 23, 'price_max' => 26], // K.Kedah -> Kuah
            ['origin' => 3, 'destination' => 2, 'price_min' => 23, 'price_max' => 26],

            // Penang
            ['origin' => 6, 'destination' => 5, 'price_min' => 1.20, 'price_max' => 2.00], // Butterworth -> Georgetown
            ['origin' => 5, 'destination' => 6, 'price_min' => 0, 'price_max' => 0], // Free return often

            // Pangkor
            ['origin' => 7, 'destination' => 9, 'price_min' => 10, 'price_max' => 14], // Lumut -> Pangkor
            ['origin' => 9, 'destination' => 7, 'price_min' => 10, 'price_max' => 14],
            ['origin' => 8, 'destination' => 9, 'price_min' => 10, 'price_max' => 14], // Marina -> Pangkor

            // Tioman
            ['origin' => 12, 'destination' => 14, 'price_min' => 35, 'price_max' => 70], // Mersing -> Tioman
            ['origin' => 14, 'destination' => 12, 'price_min' => 35, 'price_max' => 70],
            ['origin' => 13, 'destination' => 14, 'price_min' => 35, 'price_max' => 70], // Tg Gemok -> Tioman

            // Redang
            ['origin' => 23, 'destination' => 25, 'price_min' => 55, 'price_max' => 110], // Merang -> Redang
            ['origin' => 25, 'destination' => 23, 'price_min' => 55, 'price_max' => 110],
            ['origin' => 22, 'destination' => 25, 'price_min' => 55, 'price_max' => 110], // Shahbandar -> Redang

            // Perhentian
            ['origin' => 24, 'destination' => 26, 'price_min' => 35, 'price_max' => 70], // Besut -> Perhentian
            ['origin' => 26, 'destination' => 24, 'price_min' => 35, 'price_max' => 70],

            // Kapas
            ['origin' => 27, 'destination' => 28, 'price_min' => 20, 'price_max' => 40], // Marang -> Kapas
            ['origin' => 28, 'destination' => 27, 'price_min' => 20, 'price_max' => 40],

            // International (Melaka/Johor -> Indonesia)
            ['origin' => 17, 'destination' => 18, 'price_min' => 110, 'price_max' => 150], // Melaka -> Dumai
            ['origin' => 16, 'destination' => 20, 'price_min' => 90, 'price_max' => 130], // Puteri -> Batam
        ];

        $ferries = Ferry::all();
        if ($ferries->isEmpty()) {
            $this->error('No ferries found in database. Run seeders first.');

            return;
        }

        // Generate for Yesterday + Today + Next 88 Days (90 days total)
        // This ensures that if the command was missed yesterday, it catches up.
        $daysToGenerate = 90;
        $totalCreated = 0;

        for ($i = -1; $i < ($daysToGenerate - 1); $i++) {
            $date = Carbon::now()->addDays($i);
            $this->info('Processing schedule for: '.$date->toDateString());

            foreach ($routes as $route) {
                // Check if route has active ports in DB
                $origin = Port::find($route['origin']);
                $dest = Port::find($route['destination']);

                if (! $origin || ! $dest) {
                    continue;
                }

                // Check if schedules already exist for this route on this date to prevent duplicates
                $exists = Schedule::whereDate('departure_time', $date->toDateString())
                    ->where('origin_port_id', $origin->id)
                    ->where('destination_port_id', $dest->id)
                    ->exists();

                if ($exists) {
                    $this->line("  Skipping {$origin->name} -> {$dest->name} (Already scheduled)");

                    continue;
                }

                // Create 3-6 trips per route per day
                $tripsCount = rand(3, 6);

                for ($j = 0; $j < $tripsCount; $j++) {
                    $ferry = $ferries->random();

                    // Random time between 7 AM and 7 PM
                    $hour = rand(7, 19);
                    $minute = [0, 15, 30, 45][rand(0, 3)];

                    $departure = $date->copy()->setTime($hour, $minute, 0);
                    $duration = rand(45, 120); // Minutes
                    $arrival = $departure->copy()->addMinutes($duration);

                    $price = rand($route['price_min'] * 100, $route['price_max'] * 100) / 100;

                    Schedule::create([
                        'ferry_id' => $ferry->id,
                        'origin_port_id' => $origin->id,
                        'destination_port_id' => $dest->id,
                        'departure_time' => $departure,
                        'arrival_time' => $arrival,
                        'price' => $price,
                    ]);
                    $totalCreated++;
                }
            }
        }

        $this->info("Successfully generated {$totalCreated} new voyages.");
    }
}

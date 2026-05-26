<?php

namespace Database\Seeders;

use App\Models\Ferry;
use App\Models\Schedule;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Schema;

class ScheduleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Clear existing future schedules to avoid duplicates if re-run
        // Schedule::where('departure_time', '>=', now())->delete();
        // Better: Truncate if clean start desired, or just append. User said "find data", implying populate.
        // Let's Truncate for clean state.
        Schema::disableForeignKeyConstraints();
        Schedule::truncate();
        Schema::enableForeignKeyConstraints();

        $routes = [
            // --- LANGKAWI ROUTES ---
            [
                'origin_id' => 1, // Kuala Perlis
                'dest_id' => 3,   // Kuah
                'ferries' => [1, 2, 3, 4], // Langkawi Ferry Line / MyFerry
                'price' => 21.00,
                'duration' => 75, // minutes
                'times' => ['07:00', '08:30', '10:00', '11:30', '13:00', '14:30', '16:00', '18:00', '19:30'],
            ],
            [
                'origin_id' => 3, // Kuah
                'dest_id' => 1,   // Kuala Perlis
                'ferries' => [1, 2, 3, 4],
                'price' => 21.00,
                'duration' => 75,
                'times' => ['07:00', '08:30', '10:00', '11:30', '13:00', '14:30', '16:00', '18:00', '19:30'],
            ],
            [
                'origin_id' => 2, // Kuala Kedah
                'dest_id' => 3,   // Kuah
                'ferries' => [5, 6, 7], // Dragon Star / Starcity
                'price' => 26.50,
                'duration' => 105,
                'times' => ['07:00', '10:30', '13:00', '16:00', '19:00'],
            ],
            [
                'origin_id' => 3, // Kuah
                'dest_id' => 2,   // Kuala Kedah
                'ferries' => [5, 6, 7],
                'price' => 26.50,
                'duration' => 105,
                'times' => ['07:30', '10:00', '13:30', '16:30', '19:00'],
            ],

            // --- TIOMAN ROUTES (Mersing/Tg Gemok) ---
            [
                'origin_id' => 12, // Mersing
                'dest_id' => 14,   // Tioman
                'ferries' => [23, 24, 21], // Bluewater / Cataferry
                'price' => 60.00,
                'duration' => 120,
                'times' => ['07:00', '09:00', '11:00', '13:00', '15:30'], // Dependent on tide technically, but fixed for demo
            ],
            [
                'origin_id' => 14, // Tioman
                'dest_id' => 12,   // Mersing
                'ferries' => [23, 24, 21],
                'price' => 60.00,
                'duration' => 120,
                'times' => ['07:30', '09:30', '11:30', '14:00', '16:00'],
            ],
            [
                'origin_id' => 13, // Tg Gemok
                'dest_id' => 14,   // Tioman
                'ferries' => [23, 22],
                'price' => 60.00,
                'duration' => 90,
                'times' => ['07:00', '11:00', '15:00'],
            ],

            // --- PANGKOR ROUTES ---
            [
                'origin_id' => 7, // Lumut
                'dest_id' => 9,   // Pangkor
                'ferries' => [15, 16, 17, 18], // Pan Silver / Mesra / Duta
                'price' => 10.00,
                'duration' => 30,
                'times' => ['07:00', '08:00', '09:00', '10:00', '11:00', '12:00', '13:00', '14:00', '15:00', '16:00', '17:00', '18:00', '19:00'],
            ],
            [
                'origin_id' => 9, // Pangkor
                'dest_id' => 7,   // Lumut
                'ferries' => [15, 16, 17, 18],
                'price' => 10.00,
                'duration' => 30,
                'times' => ['06:30', '07:30', '08:30', '09:30', '10:30', '11:30', '12:30', '13:30', '14:30', '15:30', '16:30', '17:30', '18:30'],
            ],
            [
                'origin_id' => 8, // Marina Island
                'dest_id' => 9,   // Pangkor
                'ferries' => [15, 16],
                'price' => 14.00,
                'duration' => 10,
                'times' => ['07:15', '08:15', '09:15', '10:15', '11:15', '12:15', '13:15', '14:15', '15:15', '16:15', '17:15', '18:15', '19:15', '20:15'],
            ],

            // --- PERHENTIAN / REDANG (East Coast) ---
            [
                'origin_id' => 24, // Kuala Besut
                'dest_id' => 26,   // Perhentian
                'ferries' => [27, 28, 29], // Perhentian Trans
                'price' => 35.00,
                'duration' => 45,
                'times' => ['08:00', '09:00', '10:00', '11:00', '12:00', '13:00', '14:00', '15:00', '16:00'],
            ],
            [
                'origin_id' => 26, // Perhentian
                'dest_id' => 24,   // Kuala Besut
                'ferries' => [27, 28, 29],
                'price' => 35.00,
                'duration' => 45,
                'times' => ['08:00', '12:00', '16:00'],
            ],
            [
                'origin_id' => 22, // Shahbandar (KT)
                'dest_id' => 25,   // Redang
                'ferries' => [25], // Redang Express
                'price' => 55.00,
                'duration' => 90,
                'times' => ['09:00', '10:30', '15:00'],
            ],
            [
                'origin_id' => 23, // Merang
                'dest_id' => 25,   // Redang
                'ferries' => [26, 31], // MGH Boat?
                'price' => 55.00,
                'duration' => 45,
                'times' => ['08:00', '09:30', '11:00', '13:00', '15:00'],
            ],
            [
                'origin_id' => 27, // Marang
                'dest_id' => 28,   // Kapas
                'ferries' => [30], // Kapas Boat
                'price' => 20.00,
                'duration' => 15,
                'times' => ['09:00', '11:00', '13:00', '15:00', '17:00'],
            ],

            // --- INTERNATIONAL ---
            [
                'origin_id' => 17, // Melaka
                'dest_id' => 18,   // Dumai
                'ferries' => [32, 33], // Indomal / Tunas Rupat
                'price' => 150.00,
                'duration' => 120,
                'times' => ['08:30', '10:00'],
            ],
            [
                'origin_id' => 15, // Muar
                'dest_id' => 19,   // Bengkalis
                'ferries' => [34], // Putri Anggreni
                'price' => 135.00,
                'duration' => 150,
                'times' => ['09:00', '13:00'],
            ],
            [
                'origin_id' => 16, // Puteri Harbour
                'dest_id' => 21,   // Harbour Bay (Batam)
                'ferries' => [34], // Putri Anggreni
                'price' => 110.00,
                'duration' => 90,
                'times' => ['07:30', '08:30', '10:30', '11:30', '12:30', '14:30', '15:30', '18:00', '18:30'],
            ],
        ];

        // Generate for next 14 days from today
        $startDate = Carbon::today();
        $days = 14;

        for ($i = 0; $i < $days; $i++) {
            $currentDate = $startDate->copy()->addDays($i);

            foreach ($routes as $route) {
                // If ferry has amenities, get capacity? We just pick random available from list.

                foreach ($route['times'] as $time) {
                    // Pick a random ferry for this specific time slot from the allowed list
                    $ferryId = $route['ferries'][array_rand($route['ferries'])];

                    // Create Schedule
                    $departure = $currentDate->copy()->setTimeFromTimeString($time);
                    $arrival = $departure->copy()->addMinutes($route['duration']);

                    Schedule::create([
                        'ferry_id' => $ferryId,
                        'origin_port_id' => $route['origin_id'],
                        'destination_port_id' => $route['dest_id'],
                        'departure_time' => $departure,
                        'arrival_time' => $arrival,
                        'price' => $route['price'],
                    ]);
                }
            }
        }
    }
}

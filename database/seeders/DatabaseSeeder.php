<?php

namespace Database\Seeders;

use App\Models\Ferry;
use App\Models\Port;
use App\Models\Schedule;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Create Admin User
        User::updateOrCreate(
            ['email' => 'admin@ferrycast.space'],
            [
                'name' => 'Admin User',
                'password' => Hash::make('password'),
                'is_admin' => true,
                'email_verified_at' => now(),
            ]
        );

        // Create Regular Customer
        User::firstOrCreate(
            ['email' => 'customer@ferrycast.space'],
            [
                'name' => 'Ferry Customer',
                'password' => bcrypt('password'),
                'is_admin' => false,
                'phone_number' => '0198765432',
            ]
        );

        // --- 1. DEFINE PORTS ---
        $portsList = [
            // Northern Region
            ['name' => 'Kuala Perlis Jetty', 'location' => 'Kuala Perlis, Perlis', 'latitude' => 6.4000, 'longitude' => 100.1333],
            ['name' => 'Kuala Kedah Jetty', 'location' => 'Kuala Kedah, Kedah', 'latitude' => 6.1000, 'longitude' => 100.2833],
            ['name' => 'Kuah Jetty (Langkawi)', 'location' => 'Kuah, Langkawi', 'latitude' => 6.3089, 'longitude' => 99.8514],
            ['name' => 'Tanjung Lembung Port', 'location' => 'Langkawi', 'latitude' => 6.2877, 'longitude' => 99.7894], // Cargo/RoRo usually
            ['name' => 'Swettenham Pier', 'location' => 'Georgetown, Penang', 'latitude' => 5.4192, 'longitude' => 100.3444],
            ['name' => 'Sultan Abdul Halim Ferry Terminal', 'location' => 'Butterworth, Penang', 'latitude' => 5.3942, 'longitude' => 100.3625],

            // Central & Southern Region
            ['name' => 'Lumut Jetty', 'location' => 'Lumut, Perak', 'latitude' => 4.2333, 'longitude' => 100.6333],
            ['name' => 'Marina Island Jetty', 'location' => 'Lumut, Perak', 'latitude' => 4.2144, 'longitude' => 100.6025],
            ['name' => 'Pulau Pangkor Jetty', 'location' => 'Pangkor Island', 'latitude' => 4.2250, 'longitude' => 100.5750],
            ['name' => 'South Port Passenger Terminal', 'location' => 'Port Klang, Selangor', 'latitude' => 3.0000, 'longitude' => 101.3800],
            ['name' => 'Pulau Ketam Jetty', 'location' => 'Pulau Ketam, Selangor', 'latitude' => 3.0167, 'longitude' => 101.2500],
            ['name' => 'Mersing Jetty', 'location' => 'Mersing, Johor', 'latitude' => 2.4333, 'longitude' => 103.8333],
            ['name' => 'Tanjung Gemok Jetty', 'location' => 'Kuala Rompin, Pahang', 'latitude' => 2.6636, 'longitude' => 103.6264],
            ['name' => 'Tioman Island Marina', 'location' => 'Tioman Island, Pahang', 'latitude' => 2.8167, 'longitude' => 104.1667],
            ['name' => 'Puteri Harbour International Ferry Terminal', 'location' => 'Iskandar Puteri, Johor', 'latitude' => 1.4200, 'longitude' => 103.6600],
            ['name' => 'Melaka International Ferry Terminal', 'location' => 'Melaka City, Melaka', 'latitude' => 2.1896, 'longitude' => 102.2460],

            // International / Cross-border
            ['name' => 'Dumai Ferry Terminal', 'location' => 'Dumai, Indonesia', 'latitude' => 1.6667, 'longitude' => 101.4500],
            ['name' => 'Bengkalis Ferry Terminal', 'location' => 'Bengkalis, Indonesia', 'latitude' => 1.4833, 'longitude' => 102.1000],
            ['name' => 'Batam Centre Ferry Terminal', 'location' => 'Batam, Indonesia', 'latitude' => 1.1261, 'longitude' => 104.0573],
            ['name' => 'Harbour Bay Ferry Terminal', 'location' => 'Batam, Indonesia', 'latitude' => 1.1500, 'longitude' => 104.0000],

            // East Coast
            ['name' => 'Shahbandar Jetty', 'location' => 'Kuala Terengganu', 'latitude' => 5.3333, 'longitude' => 103.1333],
            ['name' => 'Merang Jetty', 'location' => 'Setiu, Terengganu', 'latitude' => 5.5333, 'longitude' => 102.9500],
            ['name' => 'Kuala Besut Jetty', 'location' => 'Besut, Terengganu', 'latitude' => 5.8333, 'longitude' => 102.5500],
            ['name' => 'Redang Island Jetty', 'location' => 'Redang Island', 'latitude' => 5.7667, 'longitude' => 103.0000],
            ['name' => 'Perhentian Island Jetty', 'location' => 'Perhentian Island', 'latitude' => 5.9000, 'longitude' => 102.7333],
            ['name' => 'Marang Jetty', 'location' => 'Marang, Terengganu', 'latitude' => 5.2000, 'longitude' => 103.2000],
            ['name' => 'Kapas Island Jetty', 'location' => 'Kapas Island', 'latitude' => 5.2167, 'longitude' => 103.2667],
        ];

        foreach ($portsList as $portData) {
            Port::updateOrCreate(['name' => $portData['name']], $portData);
        }

        $ports = Port::all()->keyBy('name');

        // --- 2. DEFINE FERRIES ---
        $ferriesList = [
            // Langkawi
            ['name' => 'Langkawi Ferry Line 1', 'capacity' => 200, 'operator' => 'Langkawi Ferry Line'],
            ['name' => 'Langkawi Ferry Line 2', 'capacity' => 200, 'operator' => 'Langkawi Ferry Line'],
            ['name' => 'MyFerry 1', 'capacity' => 150, 'operator' => 'MyFerry'],
            ['name' => 'MyFerry 2', 'capacity' => 150, 'operator' => 'MyFerry'],
            ['name' => 'Dragon Star 1', 'capacity' => 180, 'operator' => 'Dragon Star Shipping'],
            ['name' => 'Starcity 6', 'capacity' => 200, 'operator' => 'Starcity'],
            ['name' => 'Starcity 5', 'capacity' => 200, 'operator' => 'Starcity'],
            ['name' => 'Eks Bahagia 168', 'capacity' => 160, 'operator' => 'Eks Bahagia'],
            ['name' => 'My Master', 'capacity' => 160, 'operator' => 'Master Ferry'],
            ['name' => 'Kenangan 2', 'capacity' => 160, 'operator' => 'Kenangan Ferry'],

            // Penang
            ['name' => 'Teluk Bahang', 'capacity' => 250, 'operator' => 'Penang Port Commission'],
            ['name' => 'Teluk Kumbar', 'capacity' => 250, 'operator' => 'Penang Port Commission'],
            ['name' => 'Teluk Duyung', 'capacity' => 250, 'operator' => 'Penang Port Commission'],
            ['name' => 'Teluk Kampi', 'capacity' => 250, 'operator' => 'Penang Port Commission'],

            // Pangkor
            ['name' => 'Pan Silver 1', 'capacity' => 100, 'operator' => 'Pan Silver Ferry'],
            ['name' => 'Mesra Ferry 1', 'capacity' => 120, 'operator' => 'Mesra Ferry'],
            ['name' => 'Duta Pangkor 1', 'capacity' => 120, 'operator' => 'Duta Pangkor Ferry'],
            ['name' => 'Duta Pangkor 2', 'capacity' => 120, 'operator' => 'Duta Pangkor Ferry'],

            // Port Klang / Pulau Ketam
            ['name' => 'Alibaba Cruise 1', 'capacity' => 100, 'operator' => 'Alibaba Cruises'],
            ['name' => 'Alibaba Cruise 2', 'capacity' => 100, 'operator' => 'Alibaba Cruises'],

            // Tioman / Mersing
            ['name' => 'Cataferry 1', 'capacity' => 140, 'operator' => 'Cataferry'],
            ['name' => 'Cataferry 2', 'capacity' => 140, 'operator' => 'Cataferry'],
            ['name' => 'Bluewater Express 1', 'capacity' => 100, 'operator' => 'Bluewater Express'],
            ['name' => 'Bluewater Express 2', 'capacity' => 120, 'operator' => 'Bluewater Express'],

            // East Coast (Perhentian/Redang/Kapas)
            ['name' => 'Redang Express 1', 'capacity' => 50, 'operator' => 'Redang Express'],
            ['name' => 'Redang Express 2', 'capacity' => 50, 'operator' => 'Redang Express'],
            ['name' => 'Perhentian Trans 1', 'capacity' => 40, 'operator' => 'Perhentian Trans'],
            ['name' => 'Perhentian Sunny 1', 'capacity' => 40, 'operator' => 'Perhentian Sunny Travel'],
            ['name' => 'Perhentian Boat 3', 'capacity' => 40, 'operator' => 'Said Bonaza Express'],
            ['name' => 'Kapas Boat 1', 'capacity' => 30, 'operator' => 'Kapas Speedboat'],
            ['name' => 'MGH Boat Service', 'capacity' => 30, 'operator' => 'MGH Boat Service'],

            // Johor/Indo Ferries
            ['name' => 'Indomal Express', 'capacity' => 200, 'operator' => 'Indomal Fast Ferry'],
            ['name' => 'Tunas Rupat', 'capacity' => 150, 'operator' => 'Tunas Rupat'],
            ['name' => 'Putri Anggreni', 'capacity' => 200, 'operator' => 'Putri Anggreni'],
        ];

        foreach ($ferriesList as $ferryData) {
            Ferry::updateOrCreate(['name' => $ferryData['name']], $ferryData);
        }

        $ferries = Ferry::all()->keyBy('name');

        // --- 3. SEED SCHEDULES ---

        // A. Langkawi Routes
        // Kuala Kedah -> Langkawi (Real Data)
        $this->createSchedule($ferries['Starcity 6'], $ports['Kuala Kedah Jetty'], $ports['Kuah Jetty (Langkawi)'], '07:00', '08:30', 23.00);
        $this->createSchedule($ferries['Eks Bahagia 168'], $ports['Kuala Kedah Jetty'], $ports['Kuah Jetty (Langkawi)'], '10:30', '12:00', 23.00);
        $this->createSchedule($ferries['My Master'], $ports['Kuala Kedah Jetty'], $ports['Kuah Jetty (Langkawi)'], '13:00', '14:30', 23.00);
        $this->createSchedule($ferries['Starcity 5'], $ports['Kuala Kedah Jetty'], $ports['Kuah Jetty (Langkawi)'], '15:00', '16:30', 23.00);
        $this->createSchedule($ferries['Kenangan 2'], $ports['Kuala Kedah Jetty'], $ports['Kuah Jetty (Langkawi)'], '17:00', '18:30', 23.00);

        // Kuala Perlis -> Langkawi (Frequent: every hour approx for demo)
        $start = 7;
        $end = 19;
        foreach (range($start, $end) as $hour) {
            $ferry = ($hour % 2 == 0) ? $ferries['Langkawi Ferry Line 1'] : $ferries['MyFerry 1'];
            $this->createSchedule($ferry, $ports['Kuala Perlis Jetty'], $ports['Kuah Jetty (Langkawi)'], sprintf('%02d:00', $hour), sprintf('%02d:15', $hour + 1), 18.00);
        }

        // B. Penang Routes (Butterworth <-> Georgetown)
        // High frequency: every 30 mins from 6:30am to 11pm
        $current = now()->setTime(6, 30);
        $endTime = now()->setTime(23, 0);
        $switch = 0;
        $penangFerries = ['Teluk Bahang', 'Teluk Kumbar', 'Teluk Duyung', 'Teluk Kampi'];

        while ($current <= $endTime) {
            $ferryName = $penangFerries[$switch % 4];
            $departure = $current->format('H:i');
            $arrival = $current->copy()->addMinutes(15)->format('H:i');

            // Butterworth -> Georgetown
            $this->createSchedule($ferries[$ferryName], $ports['Sultan Abdul Halim Ferry Terminal'], $ports['Swettenham Pier'], $departure, $arrival, 2.00);

            // Georgetown -> Butterworth (Simultaneous return usually, slightly offset for single vessel logic, but we'll assume multiple vessels)
            $this->createSchedule($ferries[$ferryName], $ports['Swettenham Pier'], $ports['Sultan Abdul Halim Ferry Terminal'], $departure, $arrival, 2.00);

            $current->addMinutes(30);
            $switch++;
        }

        // C. Pangkor Routes (Lumut <-> Pangkor)
        // Every 45 mins from 7:15 AM
        $pCurrent = now()->setTime(7, 15);
        $pEnd = now()->setTime(20, 30);
        $pSwitch = 0;
        while ($pCurrent <= $pEnd) {
            $ferry = ($pSwitch % 2 == 0) ? $ferries['Pan Silver 1'] : $ferries['Duta Pangkor 1'];
            $dep = $pCurrent->format('H:i');
            $arr = $pCurrent->copy()->addMinutes(30)->format('H:i');

            $this->createSchedule($ferry, $ports['Lumut Jetty'], $ports['Pulau Pangkor Jetty'], $dep, $arr, 10.00);
            $this->createSchedule($ferry, $ports['Pulau Pangkor Jetty'], $ports['Lumut Jetty'], $dep, $arr, 10.00);

            $pCurrent->addMinutes(45);
            $pSwitch++;
        }

        // D. Port Klang <-> Pulau Ketam (Hourly 8:30 - 18:30)
        $kCurrent = now()->setTime(8, 30);
        $kEnd = now()->setTime(18, 30);
        while ($kCurrent <= $kEnd) {
            $dep = $kCurrent->format('H:i');
            $arr = $kCurrent->copy()->addMinutes(40)->format('H:i');
            $this->createSchedule($ferries['Alibaba Cruise 1'], $ports['South Port Passenger Terminal'], $ports['Pulau Ketam Jetty'], $dep, $arr, 10.00);
            $this->createSchedule($ferries['Alibaba Cruise 2'], $ports['Pulau Ketam Jetty'], $ports['South Port Passenger Terminal'], $dep, $arr, 10.00);
            $kCurrent->addHour();
        }

        // E. Tioman (Mersing/Gemok) - Specific times
        // Mersing -> Tioman
        $this->createSchedule($ferries['Bluewater Express 1'], $ports['Mersing Jetty'], $ports['Tioman Island Marina'], '06:00', '08:00', 35.00);
        $this->createSchedule($ferries['Cataferry 1'], $ports['Mersing Jetty'], $ports['Tioman Island Marina'], '07:00', '08:30', 35.00);
        $this->createSchedule($ferries['Bluewater Express 2'], $ports['Mersing Jetty'], $ports['Tioman Island Marina'], '08:30', '10:30', 35.00);
        $this->createSchedule($ferries['Cataferry 2'], $ports['Mersing Jetty'], $ports['Tioman Island Marina'], '11:00', '12:30', 35.00);
        $this->createSchedule($ferries['Bluewater Express 1'], $ports['Mersing Jetty'], $ports['Tioman Island Marina'], '14:00', '16:00', 35.00);

        // Tanjung Gemok -> Tioman
        $this->createSchedule($ferries['Cataferry 1'], $ports['Tanjung Gemok Jetty'], $ports['Tioman Island Marina'], '07:00', '08:30', 35.00);
        $this->createSchedule($ferries['Cataferry 2'], $ports['Tanjung Gemok Jetty'], $ports['Tioman Island Marina'], '11:00', '12:30', 35.00);

        // F. East Coast (Perhentian/Redang)
        // Kuala Besut -> Perhentian (Hourly 8-5)
        $pbCurrent = now()->setTime(8, 0);
        $pbEnd = now()->setTime(17, 0);
        $pbSwitch = 0;
        while ($pbCurrent <= $pbEnd) {
            $ferry = ($pbSwitch % 2 == 0) ? $ferries['Perhentian Trans 1'] : $ferries['Perhentian Sunny 1'];
            $dep = $pbCurrent->format('H:i');
            $arr = $pbCurrent->copy()->addMinutes(45)->format('H:i');

            $this->createSchedule($ferry, $ports['Kuala Besut Jetty'], $ports['Perhentian Island Jetty'], $dep, $arr, 35.00);

            $pbCurrent->addHour();
            $pbSwitch++;
        }

        // Merang -> Redang
        $this->createSchedule($ferries['Redang Express 1'], $ports['Merang Jetty'], $ports['Redang Island Jetty'], '08:00', '08:45', 55.00);
        $this->createSchedule($ferries['Redang Express 2'], $ports['Merang Jetty'], $ports['Redang Island Jetty'], '09:30', '10:15', 55.00);
        $this->createSchedule($ferries['Redang Express 1'], $ports['Merang Jetty'], $ports['Redang Island Jetty'], '10:30', '11:15', 55.00);
        $this->createSchedule($ferries['Redang Express 2'], $ports['Merang Jetty'], $ports['Redang Island Jetty'], '15:00', '15:45', 55.00);

        // G. Southern International
        // Melaka -> Dumai
        $this->createSchedule($ferries['Indomal Express'], $ports['Melaka International Ferry Terminal'], $ports['Dumai Ferry Terminal'], '09:00', '11:00', 160.00);
        $this->createSchedule($ferries['Tunas Rupat'], $ports['Melaka International Ferry Terminal'], $ports['Dumai Ferry Terminal'], '15:00', '17:00', 160.00);

        // Melaka -> Bengkalis (Skip complexity of specific days, just add daily for demo or specific if needed)
        // For demo, adding one daily
        $this->createSchedule($ferries['Indomal Express'], $ports['Melaka International Ferry Terminal'], $ports['Bengkalis Ferry Terminal'], '10:00', '13:00', 160.00);

        // Puteri Harbour -> Batam
        $this->createSchedule($ferries['Putri Anggreni'], $ports['Puteri Harbour International Ferry Terminal'], $ports['Batam Centre Ferry Terminal'], '07:30', '09:00', 60.00);
        $this->createSchedule($ferries['Putri Anggreni'], $ports['Puteri Harbour International Ferry Terminal'], $ports['Batam Centre Ferry Terminal'], '11:30', '13:00', 60.00);
        $this->createSchedule($ferries['Putri Anggreni'], $ports['Puteri Harbour International Ferry Terminal'], $ports['Batam Centre Ferry Terminal'], '14:30', '16:00', 60.00);
        $this->createSchedule($ferries['Putri Anggreni'], $ports['Puteri Harbour International Ferry Terminal'], $ports['Batam Centre Ferry Terminal'], '18:30', '20:00', 60.00);

        // Run details seeder to add images and descriptions
        $this->call(FerryDetailsSeeder::class);
    }

    // Helper to create schedule
    private function createSchedule($ferry, $origin, $dest, $timeStr, $arrTimeStr, $price)
    {
        $now = now();
        $date = $now->format('Y-m-d');

        $dep = \Carbon\Carbon::parse("$date $timeStr");
        $arr = \Carbon\Carbon::parse("$date $arrTimeStr");

        // If passed time for today, set for tomorrow?
        // For seeding purposes, let's just make it mostly for "today" or "tomorrow" relative to seeding time.
        // Actually, let's clear old schedules logic to be simple:
        // Set Schedules for TODAY and TOMORROW to populate the view.

        foreach ([0, 1] as $addDays) {
            Schedule::create([
                'ferry_id' => $ferry->id,
                'origin_port_id' => $origin->id,
                'destination_port_id' => $dest->id,
                'departure_time' => $dep->copy()->addDays($addDays),
                'arrival_time' => $arr->copy()->addDays($addDays),
                'price' => $price,
            ]);
        }
    }

    private function callDetailsSeeder()
    {
        $this->call(FerryDetailsSeeder::class);
    }
}

<?php
require 'vendor/autoload.php';
require 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$schedules = \App\Models\Schedule::where('departure_time', '>=', now()->startOfDay())->get();
foreach($schedules as $s) {
    $parsed = \Carbon\Carbon::parse($s->departure_time);
    $isPast = $parsed->isPast();
    $hasSeats = $s->hasAvailableSeats(1);
    
    echo "ID: {$s->id} | Dep: {$s->departure_time} | Parsed: {$parsed} | Now: " . now() . " | isPast: " . ($isPast ? 'Y' : 'N') . " | hasSeats: " . ($hasSeats ? 'Y' : 'N') . "\n";
}

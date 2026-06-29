<?php
require 'vendor/autoload.php';
$tz = 'Asia/Kuala_Lumpur';
$carbon = \Carbon\Carbon::parse('2026-06-29 21:00:00', $tz);
echo "Carbon: " . $carbon->toDateTimeString() . " TZ: " . $carbon->timezoneName . "\n";
$parsed = \Carbon\Carbon::parse($carbon);
echo "Parsed: " . $parsed->toDateTimeString() . " TZ: " . $parsed->timezoneName . "\n";
echo "Now (MYT): " . \Carbon\Carbon::now($tz)->toDateTimeString() . "\n";

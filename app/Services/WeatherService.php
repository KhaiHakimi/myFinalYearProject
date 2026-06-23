<?php

namespace App\Services;

use App\Models\Port;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WeatherService
{
    /**
     * Fetch live weather for a port, run it through risk analysis,
     * persist the result, and trigger alerts if needed.
     */
    public function updateWeatherForPort(Port $port)
    {
        $lat = $port->latitude;
        $lon = $port->longitude;

        try {
            $apiKey = env('VITE_OPENWEATHER_API_KEY');

            // Wind & visibility from OpenWeatherMap
            $owm = Http::get('https://api.openweathermap.org/data/2.5/weather', [
                'lat'   => $lat,
                'lon'   => $lon,
                'appid' => $apiKey,
                'units' => 'metric',
            ]);

            $windSpeed  = 0;
            $visibility = 10;

            if ($owm->successful()) {
                $data       = $owm->json();
                $windSpeed  = ($data['wind']['speed'] ?? 0) * 3.6;       // m/s → km/h
                $visibility = ($data['visibility'] ?? 10000) / 1000;     // m → km
            }

            // Wave height from Open-Meteo Marine (OWM doesn't provide this)
            $waveHeight = 0;
            $marine = Http::get('https://marine-api.open-meteo.com/v1/marine', [
                'latitude'  => $lat,
                'longitude' => $lon,
                'current'   => 'wave_height',
                'timezone'  => 'Asia/Singapore',
            ]);

            if ($marine->successful()) {
                $waveHeight = $marine->json('current')['wave_height'] ?? 0;
            }

            $readings = [
                'wind_speed'  => $windSpeed,
                'wave_height' => $waveHeight,
                'visibility'  => $visibility,
                'tide_level'  => 1.0,
                'recorded_at' => now(),
            ];

            // Run through the AI / heuristic risk engine
            $risk = app(GeoIntelligenceService::class)->calculateRisk($readings);

            $weather = $port->weatherData()->create(array_merge($readings, $risk));

            if ($risk['risk_status'] === 'High Risk') {
                \Illuminate\Support\Facades\Log::warning("High Risk Alert for {$port->name}");
            }

            return $weather;

        } catch (\Exception $e) {
            Log::error("Weather fetch failed for port {$port->id}: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Get a marine forecast for an arbitrary coordinate (e.g. a mid-sea
     * waypoint).  Results are cached for one hour so repeated route
     * scans don't hammer the APIs.
     */
    public function getMarineForecast(float $lat, float $lon, \Carbon\Carbon $targetTime = null): ?array
    {
        $lat = round($lat, 2);
        $lon = round($lon, 2);

        $isFuture = $targetTime && $targetTime->isFuture();
        $cacheKey = "marine_forecast_{$lat}_{$lon}";
        if ($isFuture) {
            $cacheKey .= "_" . $targetTime->format('YmdH');
        }

        return Cache::remember($cacheKey, 3600, function () use ($lat, $lon, $targetTime, $isFuture) {
            try {
                if ($isFuture) {
                    $marine = Http::timeout(5)->get('https://marine-api.open-meteo.com/v1/marine', [
                        'latitude'  => $lat,
                        'longitude' => $lon,
                        'hourly'    => 'wave_height,wave_direction,wave_period,swell_wave_height',
                        'timezone'  => 'UTC',
                    ]);

                    $weather = Http::timeout(5)->get('https://api.open-meteo.com/v1/forecast', [
                        'latitude'  => $lat,
                        'longitude' => $lon,
                        'hourly'    => 'wind_speed_10m,visibility',
                        'timezone'  => 'UTC',
                    ]);

                    $windSpeed     = 0;
                    $visibility    = 10;
                    $waveHeight    = 0;
                    $waveDirection = 0;
                    $wavePeriod    = 0;
                    $swellHeight   = 0;

                    if ($marine->successful() && $weather->successful()) {
                        $m = $marine->json('hourly');
                        $w = $weather->json('hourly');
                        
                        $targetStr = $targetTime->copy()->setTimezone('UTC')->format('Y-m-d\TH:00');
                        $index = array_search($targetStr, $m['time'] ?? []);
                        
                        if ($index !== false) {
                            $waveHeight    = $m['wave_height'][$index] ?? 0;
                            $waveDirection = $m['wave_direction'][$index] ?? 0;
                            $wavePeriod    = $m['wave_period'][$index] ?? 0;
                            $swellHeight   = $m['swell_wave_height'][$index] ?? 0;
                            
                            $windSpeed     = $w['wind_speed_10m'][$index] ?? 0;
                            $visibility    = ($w['visibility'][$index] ?? 10000) / 1000;
                        }
                    }
                } else {
                    $apiKey = env('VITE_OPENWEATHER_API_KEY');

                    $owm = Http::timeout(5)->get('https://api.openweathermap.org/data/2.5/weather', [
                        'lat'   => $lat,
                        'lon'   => $lon,
                        'appid' => $apiKey,
                        'units' => 'metric',
                    ]);

                    $marine = Http::timeout(3)->get('https://marine-api.open-meteo.com/v1/marine', [
                        'latitude'  => $lat,
                        'longitude' => $lon,
                        'current'   => 'wave_height,wave_direction,wave_period,swell_wave_height',
                        'timezone'  => 'Asia/Singapore',
                    ]);

                    $windSpeed     = 0;
                    $visibility    = 10;
                    $waveHeight    = 0;
                    $waveDirection = 0;
                    $wavePeriod    = 0;
                    $swellHeight   = 0;

                    if ($owm->successful()) {
                        $data       = $owm->json();
                        $windSpeed  = ($data['wind']['speed'] ?? 0) * 3.6;
                        $visibility = ($data['visibility'] ?? 10000) / 1000;
                    }

                    if ($marine->successful()) {
                        $m             = $marine->json('current');
                        $waveHeight    = $m['wave_height']       ?? 0;
                        $waveDirection = $m['wave_direction']     ?? 0;
                        $wavePeriod    = $m['wave_period']        ?? 0;
                        $swellHeight   = $m['swell_wave_height']  ?? 0;
                    }
                }

                $readings = [
                    'wind_speed'     => $windSpeed,
                    'wave_height'    => $waveHeight,
                    'wave_direction' => $waveDirection,
                    'wave_period'    => $wavePeriod,
                    'swell_height'   => $swellHeight,
                    'visibility'     => $visibility,
                ];

                $risk = app(GeoIntelligenceService::class)->calculateRisk($readings);

                return array_merge($readings, $risk);

            } catch (\Exception $e) {
                Log::error("Marine forecast failed: " . $e->getMessage());
                return null;
            }
        });
    }


}

<?php

use App\Http\Controllers\FerryController;
use App\Http\Controllers\ProfileController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    // Live stats for hero section
    $stats = \Illuminate\Support\Facades\Cache::remember('landing_stats', 300, function () {
        return [
            'total_ferries' => \App\Models\Ferry::count(),
            'active_routes' => \App\Models\Schedule::where('departure_time', '>=', now())->distinct('origin_port_id', 'destination_port_id')->count(),
            'total_ports' => \App\Models\Port::count(),
            'total_bookings' => \App\Models\Booking::where('payment_status', 'paid')->count(),
        ];
    });

    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
        'stats' => $stats,
    ]);
});

Route::get('/dashboard', [\App\Http\Controllers\DashboardController::class, 'index'])->middleware(['auth', 'verified'])->name('dashboard');
Route::get('/dashboard/logs', [\App\Http\Controllers\DashboardController::class, 'systemLogs'])->middleware(['auth', 'verified', 'admin'])->name('dashboard.logs');
Route::post('/dashboard/geo-analysis', [\App\Http\Controllers\DashboardController::class, 'geoAnalysis'])->middleware(['auth', 'verified'])->name('dashboard.geo_analysis');
Route::post('/dashboard/analyze-route', [\App\Http\Controllers\DashboardController::class, 'analyzeRoute'])->middleware(['auth', 'verified'])->name('dashboard.analyze_route');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';

// Customer / Public Routes (Accessible by guests)
Route::get('/schedules', [\App\Http\Controllers\ScheduleController::class, 'index'])->name('schedules.index');
Route::post('/api/travel-windows', [\App\Http\Controllers\TravelWindowController::class, 'analyze'])->name('travel_windows.analyze');
Route::post('/api/recommend', [\App\Http\Controllers\RecommendationController::class, 'recommend'])->name('recommend');
Route::get('/our-fleet', [\App\Http\Controllers\FerryController::class, 'publicIndex'])->name('ferries.public_index');
Route::get('/our-fleet/{ferry}', [\App\Http\Controllers\FerryController::class, 'publicShow'])->name('ferries.public_show');

// Weather Routes (Accessible by all for safety)
Route::get('/ports/{port}/weather', [\App\Http\Controllers\WeatherController::class, 'show'])->name('weather.show');

// PUBLIC ACCESS FOR DEMO PURPOSES - MOVED TO ADMIN
// Route::post('/ports/{port}/weather', [\App\Http\Controllers\WeatherController::class, 'store'])->name('weather.store');
Route::post('/ports/{port}/weather/refresh', [\App\Http\Controllers\WeatherController::class, 'refresh'])->name('weather.refresh');
Route::post('/weather/refresh-all', [\App\Http\Controllers\WeatherController::class, 'refreshAll'])->name('weather.refresh_all');
Route::get('/weather/wind-data', [\App\Http\Controllers\WeatherController::class, 'windData'])->name('weather.wind_data');
Route::get('/weather/wave-data', [\App\Http\Controllers\WeatherController::class, 'waveData'])->name('weather.wave_data');

Route::middleware(['auth', 'verified'])->group(function () {
    // Authenticated actions
    Route::post('/ferries/{ferry}/reviews', [\App\Http\Controllers\FerryController::class, 'storeReview'])->name('ferries.reviews.store');

    // Payment / Booking Routes
    Route::post('/payment/checkout', [\App\Http\Controllers\PaymentController::class, 'checkout'])->name('payment.checkout');
    Route::get('/payment/success', [\App\Http\Controllers\PaymentController::class, 'success'])->name('payment.success');
    Route::get('/payment/cancel', [\App\Http\Controllers\PaymentController::class, 'cancel'])->name('payment.cancel');
    Route::get('/my-bookings', [\App\Http\Controllers\PaymentController::class, 'bookings'])->name('bookings.index');
    Route::get('/my-bookings/{booking}/ticket', [\App\Http\Controllers\PaymentController::class, 'ticket'])->name('bookings.ticket');

    // Admin Only Routes
    Route::middleware(['admin'])->group(function () {
        Route::resource('users', \App\Http\Controllers\UserController::class)->only(['index', 'destroy']);
        Route::resource('ferries', FerryController::class);
        // Schedules: Admin can create/edit/delete. Index is public.
        Route::post('/schedules/generate', [\App\Http\Controllers\ScheduleController::class, 'generateDaily'])->name('schedules.generate');
        Route::resource('schedules', \App\Http\Controllers\ScheduleController::class)->except(['index', 'show']);

        // Weather Simulation & Data Fetching
        Route::post('/ports/{port}/weather', [\App\Http\Controllers\WeatherController::class, 'store'])->name('weather.store');

        // Admin Booking Analytics
        Route::get('/admin/booking-analytics', [\App\Http\Controllers\PaymentController::class, 'analytics'])->name('admin.booking_analytics');
        Route::post('/admin/ai/train', [\App\Http\Controllers\RecommendationController::class, 'train'])->name('admin.ai.train');
        Route::get('/admin/ai/diagnostics', [\App\Http\Controllers\RecommendationController::class, 'diagnosticsPage'])->name('admin.ai.diagnostics');
        Route::get('/admin/analytics', function () {
            return \Inertia\Inertia::render('Admin/BookingAnalytics');
        })->name('admin.analytics_page');

        // Channel Management
        Route::get('/admin/channel-manager', [\App\Http\Controllers\ChannelManagerController::class, 'index'])->name('admin.channel_manager');
        Route::get('/admin/channel-manager/schedule/{schedule}', [\App\Http\Controllers\ChannelManagerController::class, 'scheduleDetail'])->name('admin.channel_manager.schedule');
        Route::post('/admin/channel-manager/sync', [\App\Http\Controllers\ChannelManagerController::class, 'syncAll'])->name('admin.channel_manager.sync_all');
        Route::post('/admin/channel-manager/sync/{operator}', [\App\Http\Controllers\ChannelManagerController::class, 'syncOperator'])->name('admin.channel_manager.sync_operator');
        Route::post('/admin/channel-manager/operators', [\App\Http\Controllers\ChannelManagerController::class, 'storeOperator'])->name('admin.operators.store');
        Route::put('/admin/channel-manager/operators/{operator}', [\App\Http\Controllers\ChannelManagerController::class, 'updateOperator'])->name('admin.operators.update');
        Route::delete('/admin/channel-manager/operators/{operator}', [\App\Http\Controllers\ChannelManagerController::class, 'destroyOperator'])->name('admin.operators.destroy');
        Route::post('/admin/channel-manager/external-booking', [\App\Http\Controllers\ChannelManagerController::class, 'recordExternalBooking'])->name('admin.external_booking.store');
        Route::delete('/admin/channel-manager/external-booking/{externalBooking}', [\App\Http\Controllers\ChannelManagerController::class, 'cancelExternalBooking'])->name('admin.external_booking.cancel');
    });
});

// iCal Feed (public so external calendar apps can subscribe)
Route::get('/schedules/ical', [\App\Http\Controllers\ChannelManagerController::class, 'icalFeed'])->name('schedules.ical');

// --- Hostinger Shared Hosting Storage Fix ---
// FAULT DIAGNOSIS: The 'symlink()' and 'exec()' functions are disabled on this server.
// SOLUTION: We use a custom route to serve files directly from the storage folder via PHP.
Route::get('/storage/{extra}', function ($extra) {
    // 1. Construct the real path to the file
    $path = storage_path('app/public/' . $extra);

    // 2. Security: Prevent trying to access files outside public folder
    if (strpos($extra, '..') !== false) {
        abort(403);
    }

    // 3. Check if file exists
    if (!file_exists($path)) {
        abort(404);
    }

    // 4. Return the file directly (Laravel handles MIME types)
    return response()->file($path);
})->where('extra', '.*'); // '.*' allows matching subdirectories like 'ferries/image.jpg'

// --- Database Fixer (One-time use) ---
Route::get('/fix-prices', function () {
    $p1 = \App\Models\Port::where('name', 'Sultan Abdul Halim Ferry Terminal')->first();
    $p2 = \App\Models\Port::where('name', 'Swettenham Pier')->first();

    if (!$p1 || !$p2) {
        return "Error: Could not find one or both ports.";
    }

    // Update schedules going BOTH directions
    $updated = \App\Models\Schedule::where(function($query) use ($p1, $p2) {
        $query->where('origin_port_id', $p1->id)->where('destination_port_id', $p2->id);
    })->orWhere(function($query) use ($p1, $p2) {
        $query->where('origin_port_id', $p2->id)->where('destination_port_id', $p1->id);
    })->update(['price' => 2.00]);

    return "SUCCESS: Updated price to RM 2.00 for $updated schedules between " . $p1->name . " and " . $p2->name;
});


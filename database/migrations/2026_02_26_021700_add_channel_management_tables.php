<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Track which operators manage which ferries and their API credentials
        Schema::create('operators', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code', 20)->unique(); // Short code like "PNFM", "LFSB"
            $table->string('contact_email')->nullable();
            $table->string('contact_phone')->nullable();
            $table->string('api_endpoint')->nullable();  // Their external system URL
            $table->string('api_key')->nullable();        // Credentials for pulling schedules
            $table->boolean('sync_enabled')->default(false);
            $table->timestamp('last_synced_at')->nullable();
            $table->timestamps();
        });

        // Capacity tracking on schedules to prevent overbooking
        Schema::table('schedules', function (Blueprint $table) {
            $table->unsignedInteger('total_seats')->nullable()->after('price');
            $table->unsignedInteger('booked_seats')->default(0)->after('total_seats');
            $table->enum('status', ['open', 'full', 'cancelled', 'delayed'])->default('open')->after('booked_seats');
            $table->string('external_ref')->nullable()->after('status'); // External platform reference ID
            $table->string('source')->default('local')->after('external_ref'); // 'local', 'operator_api', 'ical'
        });

        // Log external bookings from other platforms to track seat usage across channels
        Schema::create('external_bookings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('schedule_id')->constrained()->onDelete('cascade');
            $table->string('platform');          // e.g., "operator_portal", "walk_in", "partner_site"
            $table->string('external_ref');       // The platform's own booking reference
            $table->unsignedInteger('quantity')->default(1);
            $table->string('passenger_name')->nullable();
            $table->string('passenger_contact')->nullable();
            $table->enum('status', ['confirmed', 'cancelled', 'pending'])->default('confirmed');
            $table->timestamps();

            $table->index(['schedule_id', 'platform']);
            $table->unique(['platform', 'external_ref']); // No duplicate imports
        });

        // Link ferries to operators for multi-operator aggregation
        Schema::table('ferries', function (Blueprint $table) {
            $table->foreignId('operator_id')->nullable()->after('operator')->constrained('operators')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('ferries', function (Blueprint $table) {
            $table->dropConstrainedForeignId('operator_id');
        });

        Schema::dropIfExists('external_bookings');

        Schema::table('schedules', function (Blueprint $table) {
            $table->dropColumn(['total_seats', 'booked_seats', 'status', 'external_ref', 'source']);
        });

        Schema::dropIfExists('operators');
    }
};

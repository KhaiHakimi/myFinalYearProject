<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bookings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('schedule_id')->constrained()->onDelete('cascade');

            // Passenger Details
            $table->string('passenger_name');
            $table->string('passenger_email');
            $table->string('passenger_phone')->nullable();
            $table->integer('quantity')->default(1);

            // Payment Details
            $table->decimal('total_amount', 10, 2);
            $table->string('currency', 3)->default('MYR');
            $table->string('stripe_session_id')->nullable()->unique();
            $table->string('stripe_payment_intent')->nullable();
            $table->enum('payment_status', ['pending', 'paid', 'failed', 'refunded'])->default('pending');

            // Booking Reference
            $table->string('booking_reference')->unique();

            $table->timestamps();

            // Indexes
            $table->index('payment_status');
            $table->index('booking_reference');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bookings');
    }
};

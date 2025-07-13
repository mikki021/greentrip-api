<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('bookings', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('flight_details_id');
            $table->float('emissions');
            $table->string('status');
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('flight_details_id')->references('id')->on('flight_details')->onDelete('cascade');

            // Performance indexes for common query patterns
            $table->index(['user_id', 'created_at'], 'bookings_user_created_at_index');
            $table->index(['user_id', 'status'], 'bookings_user_status_index');
            $table->index('created_at', 'bookings_created_at_index');
            $table->index('emissions', 'bookings_emissions_index');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('bookings');
    }
};

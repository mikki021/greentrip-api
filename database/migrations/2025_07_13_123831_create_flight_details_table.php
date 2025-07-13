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
        Schema::create('flight_details', function (Blueprint $table) {
            $table->id();
            $table->string('flight_id');
            $table->string('airline');
            $table->string('flight_number');
            $table->string('from', 3);
            $table->string('to', 3);
            $table->string('departure_time');
            $table->string('arrival_time');
            $table->string('duration');
            $table->decimal('price', 10, 2);
            $table->integer('seats_available');
            $table->string('aircraft');
            $table->float('carbon_footprint');
            $table->float('eco_rating');
            $table->date('date');
            $table->decimal('total_price', 12, 2)->nullable();
            $table->timestamps();
            $table->unique(['flight_id', 'date']);

            // Performance indexes for flight search and filtering
            $table->index(['from', 'to', 'date'], 'flight_details_route_date_index');
            $table->index(['airline', 'date'], 'flight_details_airline_date_index');
            $table->index('date', 'flight_details_date_index');
            $table->index('price', 'flight_details_price_index');
            $table->index('seats_available', 'flight_details_seats_index');
            $table->index('eco_rating', 'flight_details_eco_rating_index');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('flight_details');
    }
};

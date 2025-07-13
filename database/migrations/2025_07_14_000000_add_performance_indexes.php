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
        // Add composite indexes for complex queries
        Schema::table('bookings', function (Blueprint $table) {
            // For emissions reporting queries (user_id + created_at range)
            $table->index(['user_id', 'created_at', 'emissions'], 'bookings_emissions_reporting_index');

            // For booking status queries
            $table->index(['user_id', 'status', 'created_at'], 'bookings_user_status_date_index');
        });

        Schema::table('flight_details', function (Blueprint $table) {
            // For flight search with price filtering
            $table->index(['from', 'to', 'date', 'price'], 'flight_details_search_price_index');

            // For eco-friendly flight filtering
            $table->index(['from', 'to', 'date', 'eco_rating'], 'flight_details_search_eco_index');
        });

        Schema::table('users', function (Blueprint $table) {
            // For user lookup by email (login performance)
            $table->index('email', 'users_email_index');

            // For email verification queries
            $table->index(['email', 'email_verified_at'], 'users_email_verification_index');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropIndex('bookings_emissions_reporting_index');
            $table->dropIndex('bookings_user_status_date_index');
        });

        Schema::table('flight_details', function (Blueprint $table) {
            $table->dropIndex('flight_details_search_price_index');
            $table->dropIndex('flight_details_search_eco_index');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex('users_email_index');
            $table->dropIndex('users_email_verification_index');
        });
    }
};
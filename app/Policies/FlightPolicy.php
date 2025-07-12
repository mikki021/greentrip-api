<?php

namespace App\Policies;

use App\Models\User;

class FlightPolicy
{
    /**
     * Determine whether the user can view/search/book flights.
     *
     * @param  \App\Models\User|null  $user
     * @return bool
     */
    public function view(?User $user): bool
    {
        return $user !== null;
    }
}
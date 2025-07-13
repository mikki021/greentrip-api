<?php

namespace Tests\Feature;

use Tests\TestCase;

class EmissionControllerUnauthenticatedTest extends TestCase
{
    public function test_unauthenticated_access_to_emissions_calculate_returns_401(): void
    {
        $response = $this->postJson('/api/emissions/calculate', [
            'from' => 'LHR',
            'to' => 'JFK',
            'class' => 'economy',
            'passengers' => 1
        ]);

        $response->assertStatus(401);
    }
}
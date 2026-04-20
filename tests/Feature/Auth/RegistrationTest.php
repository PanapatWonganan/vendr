<?php

namespace Tests\Feature\Auth;

use Tests\TestCase;

class RegistrationTest extends TestCase
{
    /**
     * Registration is disabled — Filament handles user creation via admin panel.
     */
    public function test_registration_route_is_not_available(): void
    {
        $response = $this->get('/register');

        // Registration route doesn't exist in this app
        $response->assertStatus(404);
    }
}

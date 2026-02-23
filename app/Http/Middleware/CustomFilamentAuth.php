<?php

namespace App\Http\Middleware;

use Filament\Http\Middleware\Authenticate as FilamentAuthenticate;

class CustomFilamentAuth extends FilamentAuthenticate
{
    /**
     * Authenticate the request using the parent's implementation,
     * which properly calls canAccessPanel() on the User model.
     * No override needed — the parent handles auth + panel access check.
     */
}

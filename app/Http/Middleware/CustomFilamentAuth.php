<?php

namespace App\Http\Middleware;

use Filament\Http\Middleware\Authenticate as FilamentAuthenticate;

class CustomFilamentAuth extends FilamentAuthenticate
{
    protected function authenticate($request, array $guards): void
    {
        // ให้ผ่านถ้า user login แล้ว (bypass canAccessPanel check)
        if (auth()->check()) {
            return;
        }

        parent::authenticate($request, $guards);
    }
}

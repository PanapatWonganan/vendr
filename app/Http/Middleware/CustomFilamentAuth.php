<?php

namespace App\Http\Middleware;

use Filament\Http\Middleware\Authenticate as FilamentAuthenticate;

class CustomFilamentAuth extends FilamentAuthenticate
{
    protected function authenticate($request, array $guards): void
    {
        \Log::info('CustomFilamentAuth called', [
            'path' => $request->path(),
            'user' => auth()->user()?->id,
        ]);

        // ให้ผ่านถ้า user login แล้ว
        if (auth()->check()) {
            \Log::info('CustomFilamentAuth: User authenticated, bypassing canAccessPanel');
            return;
        }

        parent::authenticate($request, $guards);
    }
}

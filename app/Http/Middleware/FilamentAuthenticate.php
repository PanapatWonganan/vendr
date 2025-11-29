<?php

namespace App\Http\Middleware;

use Filament\Http\Middleware\Authenticate as FilamentAuthenticate;

class FilamentAuthenticate extends FilamentAuthenticate
{
    protected function authenticate($request, array $guards): void
    {
        \Log::info('FilamentAuthenticate called', [
            'path' => $request->path(),
            'user' => auth()->user()?->id,
            'guards' => $guards,
        ]);

        // ให้ผ่านถ้า user login แล้ว (ไม่เช็ค canAccessPanel ที่นี่)
        if (auth()->check()) {
            \Log::info('FilamentAuthenticate: User authenticated, allowing access');
            return;
        }

        parent::authenticate($request, $guards);
    }
}

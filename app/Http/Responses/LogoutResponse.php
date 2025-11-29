<?php

namespace App\Http\Responses;

use Filament\Http\Responses\Auth\Contracts\LogoutResponse as LogoutResponseContract;
use Illuminate\Http\RedirectResponse;

class LogoutResponse implements LogoutResponseContract
{
    public function toResponse($request): RedirectResponse
    {
        // Clear company session เมื่อ logout
        session()->forget(['company_id', 'company_connection', 'company_name']);

        return redirect()->to(filament()->getLoginUrl());
    }
}

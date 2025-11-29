<?php

namespace App\Http\Responses;

use Filament\Http\Responses\Auth\Contracts\LoginResponse as LoginResponseContract;
use Illuminate\Http\RedirectResponse;

class LoginResponse implements LoginResponseContract
{
    public function toResponse($request): RedirectResponse
    {
        // ถ้ามี company_id ใน session แล้ว ให้ไปหน้า admin
        if (session('company_id')) {
            return redirect()->intended(filament()->getUrl());
        }

        // ถ้ายังไม่มี ให้ไปหน้าเลือกบริษัทก่อน
        return redirect()->route('company.select');
    }
}

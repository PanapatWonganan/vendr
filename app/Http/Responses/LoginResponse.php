<?php

namespace App\Http\Responses;

use Filament\Http\Responses\Auth\Contracts\LoginResponse as LoginResponseContract;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Redirect;

class LoginResponse implements LoginResponseContract
{
    public function toResponse($request): RedirectResponse
    {
        // ถ้ามี company_id ใน session แล้ว ให้ไปหน้า admin
        if (session('company_id')) {
            return Redirect::intended(filament()->getUrl());
        }

        // ถ้ายังไม่มี ให้ไปหน้าเลือกบริษัทก่อน (ใน Filament Panel)
        return Redirect::to('/admin/company-select');
    }
}

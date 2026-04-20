<?php

namespace App\Http\Responses;

use App\Filament\Pages\CompanySelect;
use Filament\Http\Responses\Auth\Contracts\LoginResponse as LoginResponseContract;

class LoginResponse implements LoginResponseContract
{
    public function toResponse($request)
    {
        // ถ้ามี company_id ใน session แล้ว ให้ไปหน้า admin dashboard
        if (session('company_id')) {
            return redirect()->intended(filament()->getUrl());
        }

        // ถ้ายังไม่มี ให้ไปหน้าเลือกบริษัท (Filament page เดียว)
        return redirect()->to(CompanySelect::getUrl());
    }
}

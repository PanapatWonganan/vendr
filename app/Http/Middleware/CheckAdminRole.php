<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckAdminRole
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // ใช้ hasRole() เพื่อให้เช็ค is_active/expires_at ตาม role_user pivot
        if (!auth()->check() || !auth()->user()->hasRole('admin')) {
            abort(403, 'ไม่มีสิทธิ์เข้าถึง จำเป็นต้องมีสิทธิ์ผู้ดูแลระบบ (admin)');
        }

        return $next($request);
    }
}

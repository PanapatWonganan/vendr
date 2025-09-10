@extends('layouts.auth')

@section('content')
<div class="bg-white rounded-xl shadow-2xl overflow-hidden transform hover:scale-105 transition-all duration-300">
    <!-- Header -->
    <div class="bg-gradient-to-r from-purple-600 to-purple-700 px-8 py-6 text-center text-white">
        <div class="mb-2">
            <div class="w-16 h-16 mx-auto bg-white rounded-full flex items-center justify-center mb-3">
                <i class="fas fa-lock-open text-purple-600 text-xl"></i>
            </div>
        </div>
        <h2 class="text-xl font-bold mb-1">ตั้งรหัสผ่านใหม่</h2>
        <p class="text-purple-100 text-sm">กรุณาตั้งรหัสผ่านใหม่ของคุณ</p>
    </div>

    <!-- Body -->
    <div class="px-8 py-6">
        <form method="POST" action="{{ route('password.store') }}" class="space-y-5">
            @csrf
            
            <!-- Password Reset Token -->
            <input type="hidden" name="token" value="{{ $request->route('token') }}">

            <!-- Email Address -->
            <div>
                <label for="email" class="block text-sm font-medium text-gray-700 mb-2">
                    <i class="fas fa-envelope text-gray-400 mr-2"></i>อีเมล
                </label>
                <input id="email" 
                       name="email" 
                       type="email" 
                       value="{{ old('email', $request->email) }}" 
                       required 
                       autofocus 
                       autocomplete="username"
                       class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-purple-500 transition-colors duration-200 @error('email') border-red-500 @enderror"
                       placeholder="กรุณากรอกอีเมล">
                @error('email')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <!-- Password -->
            <div>
                <label for="password" class="block text-sm font-medium text-gray-700 mb-2">
                    <i class="fas fa-lock text-gray-400 mr-2"></i>รหัสผ่านใหม่
                </label>
                <input id="password" 
                       name="password" 
                       type="password" 
                       required 
                       autocomplete="new-password"
                       class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-purple-500 transition-colors duration-200 @error('password') border-red-500 @enderror"
                       placeholder="กรุณากรอกรหัสผ่านใหม่">
                @error('password')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <!-- Confirm Password -->
            <div>
                <label for="password_confirmation" class="block text-sm font-medium text-gray-700 mb-2">
                    <i class="fas fa-lock text-gray-400 mr-2"></i>ยืนยันรหัสผ่านใหม่
                </label>
                <input id="password_confirmation" 
                       name="password_confirmation" 
                       type="password" 
                       required 
                       autocomplete="new-password"
                       class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-purple-500 transition-colors duration-200"
                       placeholder="กรุณายืนยันรหัสผ่านใหม่">
            </div>

            <!-- Submit Button -->
            <button type="submit" 
                    class="w-full bg-purple-600 hover:bg-purple-700 text-white font-medium py-3 px-4 rounded-lg transition-all duration-200 transform hover:scale-105 hover:shadow-lg focus:outline-none focus:ring-2 focus:ring-purple-500 focus:ring-offset-2">
                <i class="fas fa-check mr-2"></i>
                ตั้งรหัสผ่านใหม่
            </button>
        </form>
    </div>
</div>
@endsection

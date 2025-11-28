@extends('layouts.auth')

@section('content')
<div class="bg-white rounded-xl shadow-2xl overflow-hidden transform hover:scale-105 transition-all duration-300">
    <!-- Header -->
    <div class="bg-gradient-to-r from-blue-600 to-blue-700 px-8 py-6 text-center text-white">
        <div class="mb-2">
            <div class="w-16 h-16 mx-auto bg-white rounded-full flex items-center justify-center mb-3">
                <i class="fas fa-lock text-blue-600 text-xl"></i>
            </div>
        </div>
        <h2 class="text-xl font-bold mb-1">เข้าสู่ระบบ</h2>
        <p class="text-blue-100 text-sm">Procurement Management System</p>
    </div>

    <!-- Body -->
    <div class="px-8 py-6">
        <!-- Session Status -->
        @if (session('status'))
            <div class="mb-4 p-4 text-sm text-green-700 bg-green-100 rounded-lg border border-green-200" role="alert">
                <div class="flex items-center">
                    <i class="fas fa-info-circle mr-2"></i>
                    {{ session('status') }}
                </div>
            </div>
        @endif

        <form method="POST" action="{{ route('login') }}" class="space-y-5">
            @csrf
            
            <!-- Email Address -->
            <div>
                <label for="email" class="block text-sm font-medium text-gray-700 mb-2">
                    <i class="fas fa-envelope text-gray-400 mr-2"></i>อีเมล
                </label>
                <input id="email" 
                       name="email" 
                       type="email" 
                       value="{{ old('email') }}" 
                       required 
                       autofocus 
                       autocomplete="username"
                       class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors duration-200 @error('email') border-red-500 @enderror"
                       placeholder="กรุณากรอกอีเมล">
                @error('email')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <!-- Password -->
            <div>
                <label for="password" class="block text-sm font-medium text-gray-700 mb-2">
                    <i class="fas fa-lock text-gray-400 mr-2"></i>รหัสผ่าน
                </label>
                <input id="password" 
                       name="password" 
                       type="password" 
                       required 
                       autocomplete="current-password"
                       class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors duration-200 @error('password') border-red-500 @enderror"
                       placeholder="กรุณากรอกรหัสผ่าน">
                @error('password')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <!-- Remember Me -->
            <div class="flex items-center justify-between">
                <label for="remember_me" class="flex items-center text-sm text-gray-600">
                    <input id="remember_me" 
                           name="remember" 
                           type="checkbox" 
                           class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                    <span class="ml-2">จดจำการเข้าสู่ระบบ</span>
                </label>

                @if (Route::has('password.request'))
                    <a href="{{ route('password.request') }}" 
                       class="text-sm text-blue-600 hover:text-blue-700 hover:underline transition-colors duration-200">
                        ลืมรหัสผ่าน?
                    </a>
                @endif
            </div>

            <!-- Submit Button -->
            <button type="submit" 
                    class="w-full bg-blue-600 hover:bg-blue-700 text-white font-medium py-3 px-4 rounded-lg transition-all duration-200 transform hover:scale-105 hover:shadow-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                <i class="fas fa-sign-in-alt mr-2"></i>
                เข้าสู่ระบบ
            </button>
        </form>
    </div>
</div>
@endsection

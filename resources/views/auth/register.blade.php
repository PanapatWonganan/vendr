@extends('layouts.auth')

@section('content')
<div class="bg-white rounded-xl shadow-2xl overflow-hidden transform hover:scale-105 transition-all duration-300">
    <!-- Header -->
    <div class="bg-gradient-to-r from-green-600 to-green-700 px-8 py-6 text-center text-white">
        <div class="mb-2">
            <div class="w-16 h-16 mx-auto bg-white rounded-full flex items-center justify-center mb-3">
                <i class="fas fa-user-plus text-green-600 text-xl"></i>
            </div>
        </div>
        <h2 class="text-xl font-bold mb-1">สมัครสมาชิก</h2>
        <p class="text-green-100 text-sm">สร้างบัญชีใหม่เพื่อใช้งานระบบ</p>
    </div>

    <!-- Body -->
    <div class="px-8 py-6">
        <form method="POST" action="{{ route('register') }}" class="space-y-5">
            @csrf
            
            <!-- Name -->
            <div>
                <label for="name" class="block text-sm font-medium text-gray-700 mb-2">
                    <i class="fas fa-user text-gray-400 mr-2"></i>ชื่อ-นามสกุล
                </label>
                <input id="name" 
                       name="name" 
                       type="text" 
                       value="{{ old('name') }}" 
                       required 
                       autofocus 
                       autocomplete="name"
                       class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500 transition-colors duration-200 @error('name') border-red-500 @enderror"
                       placeholder="กรุณากรอกชื่อ-นามสกุล">
                @error('name')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

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
                       autocomplete="username"
                       class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500 transition-colors duration-200 @error('email') border-red-500 @enderror"
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
                       autocomplete="new-password"
                       class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500 transition-colors duration-200 @error('password') border-red-500 @enderror"
                       placeholder="กรุณากรอกรหัสผ่าน">
                @error('password')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <!-- Confirm Password -->
            <div>
                <label for="password_confirmation" class="block text-sm font-medium text-gray-700 mb-2">
                    <i class="fas fa-lock text-gray-400 mr-2"></i>ยืนยันรหัสผ่าน
                </label>
                <input id="password_confirmation" 
                       name="password_confirmation" 
                       type="password" 
                       required 
                       autocomplete="new-password"
                       class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500 transition-colors duration-200"
                       placeholder="กรุณายืนยันรหัสผ่าน">
            </div>

            <!-- Submit Button -->
            <button type="submit" 
                    class="w-full bg-green-600 hover:bg-green-700 text-white font-medium py-3 px-4 rounded-lg transition-all duration-200 transform hover:scale-105 hover:shadow-lg focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2">
                <i class="fas fa-user-plus mr-2"></i>
                สมัครสมาชิก
            </button>
        </form>

        <!-- Additional Links -->
        <div class="mt-6 text-center text-sm text-gray-600">
            <p>มีบัญชีอยู่แล้ว? 
                <a href="{{ route('login') }}" class="text-green-600 hover:text-green-700 hover:underline font-medium">
                    เข้าสู่ระบบ
                </a>
            </p>
        </div>
    </div>
</div>
@endsection

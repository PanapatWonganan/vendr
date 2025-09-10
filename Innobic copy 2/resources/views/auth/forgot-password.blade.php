@extends('layouts.auth')

@section('content')
<div class="bg-white rounded-xl shadow-2xl overflow-hidden transform hover:scale-105 transition-all duration-300">
    <!-- Header -->
    <div class="bg-gradient-to-r from-amber-600 to-amber-700 px-8 py-6 text-center text-white">
        <div class="mb-2">
            <div class="w-16 h-16 mx-auto bg-white rounded-full flex items-center justify-center mb-3">
                <i class="fas fa-key text-amber-600 text-xl"></i>
            </div>
        </div>
        <h2 class="text-xl font-bold mb-1">ลืมรหัสผ่าน</h2>
        <p class="text-amber-100 text-sm">รีเซ็ตรหัสผ่านของคุณ</p>
    </div>

    <!-- Body -->
    <div class="px-8 py-6">
        <div class="mb-4 text-sm text-gray-600 text-center">
            <p>กรุณากรอกอีเมลของคุณ เราจะส่งลิงก์สำหรับรีเซ็ตรหัสผ่านให้คุณ</p>
        </div>

        <!-- Session Status -->
        @if (session('status'))
            <div class="mb-4 p-4 text-sm text-green-700 bg-green-100 rounded-lg border border-green-200" role="alert">
                <div class="flex items-center">
                    <i class="fas fa-check-circle mr-2"></i>
                    {{ session('status') }}
                </div>
            </div>
        @endif

        <form method="POST" action="{{ route('password.email') }}" class="space-y-5">
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
                       class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-amber-500 focus:border-amber-500 transition-colors duration-200 @error('email') border-red-500 @enderror"
                       placeholder="กรุณากรอกอีเมลของคุณ">
                @error('email')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <!-- Submit Button -->
            <button type="submit" 
                    class="w-full bg-amber-600 hover:bg-amber-700 text-white font-medium py-3 px-4 rounded-lg transition-all duration-200 transform hover:scale-105 hover:shadow-lg focus:outline-none focus:ring-2 focus:ring-amber-500 focus:ring-offset-2">
                <i class="fas fa-paper-plane mr-2"></i>
                ส่งลิงก์รีเซ็ตรหัสผ่าน
            </button>
        </form>

        <!-- Additional Links -->
        <div class="mt-6 text-center text-sm text-gray-600">
            <p>จำรหัสผ่านได้แล้ว? 
                <a href="{{ route('login') }}" class="text-amber-600 hover:text-amber-700 hover:underline font-medium">
                    กลับไปเข้าสู่ระบบ
                </a>
            </p>
        </div>
    </div>
</div>
@endsection

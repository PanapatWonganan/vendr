@extends('layouts.auth')

@section('content')
<div class="bg-white rounded-xl shadow-2xl overflow-hidden transform hover:scale-105 transition-all duration-300">
    <!-- Header -->
    <div class="bg-gradient-to-r from-red-600 to-red-700 px-8 py-6 text-center text-white">
        <div class="mb-2">
            <div class="w-16 h-16 mx-auto bg-white rounded-full flex items-center justify-center mb-3">
                <i class="fas fa-shield-alt text-red-600 text-xl"></i>
            </div>
        </div>
        <h2 class="text-xl font-bold mb-1">ยืนยันรหัสผ่าน</h2>
        <p class="text-red-100 text-sm">กรุณายืนยันรหัสผ่านเพื่อดำเนินการต่อ</p>
    </div>

    <!-- Body -->
    <div class="px-8 py-6">
        <div class="mb-4 text-sm text-gray-600 text-center">
            <p>นี่เป็นพื้นที่ปลอดภัยของแอปพลิเคชัน กรุณายืนยันรหัสผ่านของคุณก่อนดำเนินการต่อ</p>
        </div>

        <form method="POST" action="{{ route('password.confirm') }}" class="space-y-5">
            @csrf
            
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
                       class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-red-500 transition-colors duration-200 @error('password') border-red-500 @enderror"
                       placeholder="กรุณากรอกรหัสผ่านเพื่อยืนยัน">
                @error('password')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <!-- Submit Button -->
            <button type="submit" 
                    class="w-full bg-red-600 hover:bg-red-700 text-white font-medium py-3 px-4 rounded-lg transition-all duration-200 transform hover:scale-105 hover:shadow-lg focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2">
                <i class="fas fa-check-circle mr-2"></i>
                ยืนยันรหัสผ่าน
            </button>
        </form>
    </div>
</div>
@endsection

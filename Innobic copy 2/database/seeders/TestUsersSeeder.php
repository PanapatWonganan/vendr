<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class TestUsersSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create 10 test users if they don't exist
        if (User::count() < 10) {
            $testUsers = [
                ['name' => 'สมชาย ใจดี', 'email' => 'somchai@test.com'],
                ['name' => 'สมหญิง มีสุข', 'email' => 'somying@test.com'],
                ['name' => 'วิชัย รักงาน', 'email' => 'wichai@test.com'],
                ['name' => 'นิรันดร์ สวยงาม', 'email' => 'niran@test.com'],
                ['name' => 'ปรีชา ฉลาด', 'email' => 'preecha@test.com'],
                ['name' => 'มาลี น่ารัก', 'email' => 'malee@test.com'],
                ['name' => 'สุนิสา เก่ง', 'email' => 'sunisa@test.com'],
                ['name' => 'ธนากร ดีมาก', 'email' => 'thanakorn@test.com'],
                ['name' => 'อัญชลี ขยัน', 'email' => 'anchalee@test.com'],
                ['name' => 'ชาตรี มั่นใจ', 'email' => 'chatree@test.com'],
            ];

            foreach ($testUsers as $userData) {
                User::firstOrCreate(
                    ['email' => $userData['email']],
                    [
                        'name' => $userData['name'],
                        'password' => Hash::make('password'),
                        'email_verified_at' => now(),
                    ]
                );
            }
        }
    }
}

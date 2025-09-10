<?php

namespace Database\Seeders;

use App\Models\Department;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Find the IT department
        $itDepartment = Department::where('code', 'IT')->first();
        
        if (!$itDepartment) {
            // If IT department doesn't exist, create it
            $itDepartment = Department::create([
                'name' => 'IT',
                'code' => 'IT',
                'description' => 'Information Technology Department',
                'is_active' => true,
            ]);
        }
        
        // Create admin user
        $adminUser = User::firstOrCreate(
            ['email' => 'admin@example.com'],
            [
                'name' => 'System Administrator',
                'password' => Hash::make('password'), // Change this in production
                'department_id' => $itDepartment->id,
                'email_verified_at' => now(),
            ]
        );
        
        // Assign admin role to the user
        $adminRole = Role::where('name', 'admin')->first();
        
        if ($adminRole && !$adminUser->hasRole('admin')) {
            $adminUser->roles()->attach($adminRole->id, [
                'is_active' => true,
                'assigned_at' => now(),
            ]);
        }
    }
}

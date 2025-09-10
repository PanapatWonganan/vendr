<?php

namespace Database\Seeders;

use App\Models\Department;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DepartmentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create basic departments
        $departments = [
            [
                'name' => 'IT',
                'code' => 'IT',
                'description' => 'Information Technology Department',
                'is_active' => true,
            ],
            [
                'name' => 'Finance',
                'code' => 'FIN',
                'description' => 'Finance Department',
                'is_active' => true,
            ],
            [
                'name' => 'Procurement',
                'code' => 'PROC',
                'description' => 'Procurement Department',
                'is_active' => true,
            ],
            [
                'name' => 'Human Resources',
                'code' => 'HR',
                'description' => 'Human Resources Department',
                'is_active' => true,
            ],
            [
                'name' => 'Operations',
                'code' => 'OPS',
                'description' => 'Operations Department',
                'is_active' => true,
            ],
            [
                'name' => 'Marketing',
                'code' => 'MKT',
                'description' => 'Marketing Department',
                'is_active' => true,
            ],
            [
                'name' => 'Sales',
                'code' => 'SALES',
                'description' => 'Sales Department',
                'is_active' => true,
            ],
            [
                'name' => 'Research & Development',
                'code' => 'RND',
                'description' => 'Research and Development Department',
                'is_active' => true,
            ],
            [
                'name' => 'Quality Assurance',
                'code' => 'QA',
                'description' => 'Quality Assurance Department',
                'is_active' => true,
            ],
            [
                'name' => 'Administration',
                'code' => 'ADMIN',
                'description' => 'Administration Department',
                'is_active' => true,
            ],
        ];
        
        foreach ($departments as $departmentData) {
            Department::firstOrCreate(
                ['code' => $departmentData['code']],
                $departmentData
            );
        }
    }
}

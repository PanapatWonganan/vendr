<?php

namespace Database\Seeders;

use App\Models\Role;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create admin role
        Role::firstOrCreate(
            ['name' => 'admin'],
            [
                'display_name' => 'System Administrator',
                'description' => 'Can access and manage all system features',
                'is_active' => true,
                'scope' => 'system',
                'priority' => 100,
            ]
        );
        
        // Create procurement roles
        $procurementRoles = [
            [
                'name' => 'requester',
                'display_name' => 'Requester',
                'description' => 'Can create and view purchase requisitions',
                'is_active' => true,
                'scope' => 'department',
                'priority' => 10,
            ],
            [
                'name' => 'department_head',
                'display_name' => 'Department Head',
                'description' => 'Can approve purchase requisitions for their department',
                'is_active' => true,
                'scope' => 'department',
                'priority' => 20,
            ],
            [
                'name' => 'procurement_officer',
                'display_name' => 'Procurement Officer',
                'description' => 'Can create purchase orders and manage suppliers',
                'is_active' => true,
                'scope' => 'system',
                'priority' => 30,
            ],
            [
                'name' => 'procurement_manager',
                'display_name' => 'Procurement Manager',
                'description' => 'Can approve purchase orders and manage procurement team',
                'is_active' => true,
                'scope' => 'system',
                'priority' => 40,
            ],
            [
                'name' => 'finance_officer',
                'display_name' => 'Finance Officer',
                'description' => 'Can manage invoices and payments',
                'is_active' => true,
                'scope' => 'system',
                'priority' => 30,
            ],
            [
                'name' => 'warehouse_staff',
                'display_name' => 'Warehouse Staff',
                'description' => 'Can receive goods and manage inventory',
                'is_active' => true,
                'scope' => 'system',
                'priority' => 20,
            ],
            [
                'name' => 'auditor',
                'display_name' => 'Auditor',
                'description' => 'Can view all procurement activities for auditing',
                'is_active' => true,
                'scope' => 'system',
                'priority' => 50,
            ],
            [
                'name' => 'procurement_committee',
                'display_name' => 'Procurement Committee',
                'description' => 'Committee member for procurement decisions',
                'is_active' => true,
                'scope' => 'system',
                'priority' => 35,
            ],
            [
                'name' => 'inspection_committee',
                'display_name' => 'Inspection Committee',
                'description' => 'Committee member for goods inspection and acceptance',
                'is_active' => true,
                'scope' => 'system',
                'priority' => 35,
            ],
            [
                'name' => 'other_stakeholder',
                'display_name' => 'Other Stakeholder',
                'description' => 'Other stakeholders involved in procurement process',
                'is_active' => true,
                'scope' => 'system',
                'priority' => 15,
            ],
        ];

        foreach ($procurementRoles as $roleData) {
            Role::firstOrCreate(
                ['name' => $roleData['name']],
                $roleData
            );
        }
    }
}

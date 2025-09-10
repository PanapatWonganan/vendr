<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class PermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Define modules
        $modules = [
            'user' => [
                'create' => 'Create users',
                'read' => 'View users',
                'update' => 'Update users',
                'delete' => 'Delete users',
                'manage_roles' => 'Manage user roles',
            ],
            'role' => [
                'create' => 'Create roles',
                'read' => 'View roles',
                'update' => 'Update roles',
                'delete' => 'Delete roles',
                'assign_permissions' => 'Assign permissions to roles',
            ],
            'department' => [
                'create' => 'Create departments',
                'read' => 'View departments',
                'update' => 'Update departments',
                'delete' => 'Delete departments',
            ],
            'supplier' => [
                'create' => 'Create suppliers',
                'read' => 'View suppliers',
                'update' => 'Update suppliers',
                'delete' => 'Delete suppliers',
            ],
            'purchase_requisition' => [
                'create' => 'Create purchase requisitions',
                'read' => 'View purchase requisitions',
                'update' => 'Update purchase requisitions',
                'delete' => 'Delete purchase requisitions',
                'approve' => 'Approve purchase requisitions',
                'reject' => 'Reject purchase requisitions',
            ],
            'purchase_order' => [
                'create' => 'Create purchase orders',
                'read' => 'View purchase orders',
                'update' => 'Update purchase orders',
                'delete' => 'Delete purchase orders',
                'approve' => 'Approve purchase orders',
                'reject' => 'Reject purchase orders',
                'send_to_supplier' => 'Send purchase orders to suppliers',
            ],
            'goods_receipt' => [
                'create' => 'Create goods receipts',
                'read' => 'View goods receipts',
                'update' => 'Update goods receipts',
                'delete' => 'Delete goods receipts',
                'quality_check' => 'Perform quality checks',
            ],
            'invoice' => [
                'create' => 'Create invoices',
                'read' => 'View invoices',
                'update' => 'Update invoices',
                'delete' => 'Delete invoices',
                'approve' => 'Approve invoices',
                'reject' => 'Reject invoices',
                'process_payment' => 'Process payments',
            ],
            'report' => [
                'procurement_report' => 'Access procurement reports',
                'supplier_report' => 'Access supplier reports',
                'budget_report' => 'Access budget reports',
                'audit_report' => 'Access audit reports',
            ],
            'dashboard' => [
                'admin_dashboard' => 'Access admin dashboard',
                'procurement_dashboard' => 'Access procurement dashboard',
                'finance_dashboard' => 'Access finance dashboard',
                'user_dashboard' => 'Access user dashboard',
            ],
        ];

        // Create permissions
        foreach ($modules as $module => $actions) {
            foreach ($actions as $action => $display_name) {
                Permission::firstOrCreate(
                    [
                        'name' => $module . '.' . $action,
                    ],
                    [
                        'display_name' => $display_name,
                        'description' => $display_name,
                        'module' => $module,
                        'action' => $action,
                        'is_active' => true,
                    ]
                );
            }
        }

        // Assign all permissions to admin role
        $adminRole = Role::where('name', 'admin')->first();
        if ($adminRole) {
            $allPermissions = Permission::all();
            $adminRole->permissions()->sync($allPermissions->pluck('id')->toArray());
        }

        // TODO: Assign specific permissions to other roles
        // This would be implementation-specific based on exact requirements
    }
}

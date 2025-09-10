<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Role;

class AdditionalRolesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $roles = [
            [
                'name' => 'procurement_committee',
                'display_name' => 'คณะกรรมการจัดหาพัสดุ',
                'description' => 'คณะกรรมการที่ทำหน้าที่ในการจัดหาพัสดุและพิจารณาการจัดซื้อจัดจ้าง',
                'is_active' => true,
                'scope' => 'procurement',
                'priority' => 6,
            ],
            [
                'name' => 'inspection_committee',
                'display_name' => 'คณะกรรมการตรวจรับ',
                'description' => 'คณะกรรมการที่ทำหน้าที่ตรวจรับสินค้าและบริการ',
                'is_active' => true,
                'scope' => 'quality_control',
                'priority' => 7,
            ],
            [
                'name' => 'other_stakeholder',
                'display_name' => 'ผู้เกี่ยวข้องอื่น',
                'description' => 'บุคคลหรือหน่วยงานอื่นที่เกี่ยวข้องกับกระบวนการจัดซื้อจัดจ้าง',
                'is_active' => true,
                'scope' => 'general',
                'priority' => 8,
            ],
        ];

        foreach ($roles as $roleData) {
            // ตรวจสอบว่า role นี้มีอยู่แล้วหรือไม่
            $existingRole = Role::where('name', $roleData['name'])->first();
            
            if (!$existingRole) {
                Role::create($roleData);
                $this->command->info("Created role: {$roleData['display_name']}");
            } else {
                $this->command->info("Role already exists: {$roleData['display_name']}");
            }
        }
    }
}

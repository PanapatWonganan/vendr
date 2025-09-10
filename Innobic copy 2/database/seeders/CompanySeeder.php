<?php

namespace Database\Seeders;

use App\Models\Company;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class CompanySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $companies = [
            [
                'name' => 'Innobic Asia Co., Ltd.',
                'code' => 'asia',
                'database_connection' => 'innobic_asia',
                'display_name' => 'Innobic Asia',
                'description' => 'บริษัท อินโนบิค เอเชีย จำกัด',
                'is_active' => true,
                'settings' => [
                    'currency' => 'THB',
                    'timezone' => 'Asia/Bangkok',
                    'language' => 'th',
                ],
            ],
            [
                'name' => 'Innobic Nutrition Co., Ltd.',
                'code' => 'nutrition',
                'database_connection' => 'innobic_nutrition',
                'display_name' => 'Innobic Nutrition',
                'description' => 'บริษัท อินโนบิค นิวทริชั่น จำกัด',
                'is_active' => true,
                'settings' => [
                    'currency' => 'THB',
                    'timezone' => 'Asia/Bangkok',
                    'language' => 'th',
                ],
            ],
            [
                'name' => 'Innobic LL Co., Ltd.',
                'code' => 'll',
                'database_connection' => 'innobic_ll',
                'display_name' => 'Innobic LL',
                'description' => 'บริษัท อินโนบิค แอลแอล จำกัด',
                'is_active' => true,
                'settings' => [
                    'currency' => 'THB',
                    'timezone' => 'Asia/Bangkok',
                    'language' => 'th',
                ],
            ],
        ];

        foreach ($companies as $companyData) {
            Company::create($companyData);
        }
    }
}

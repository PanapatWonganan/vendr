<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class KnowledgeBaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $adminUser = \App\Models\User::where('email', 'admin@innobic.com')->first();
        if (!$adminUser) {
            $adminUser = \App\Models\User::first();
        }
        
        $articles = [
            [
                'title' => 'วิธีการเข้าสู่ระบบ Innobic',
                'content' => '<h3>การเข้าสู่ระบบ</h3><p>1. เข้าไปที่หน้าเว็บของระบบ Innobic</p><p>2. ใส่ email และ password</p><p>3. คลิกปุ่ม "เข้าสู่ระบบ"</p><p>หากมีปัญหาการเข้าสู่ระบบ กรุณาติดต่อ Admin</p>',
                'category' => 'getting-started',
                'type' => 'document',
                'youtube_url' => null,
            ],
            [
                'title' => 'การสร้างใบขอซื้อ (PR)',
                'content' => '<h3>ขั้นตอนการสร้าง PR</h3><p>1. ไปที่เมนู Purchase Requisitions</p><p>2. คลิก "สร้างใหม่"</p><p>3. เลือกประเภทการจัดซื้อ</p><p>4. กรอกข้อมูลรายการสินค้า</p><p>5. แนบเอกสารที่เกี่ยวข้อง</p><p>6. ส่งเพื่อขออนุมัติ</p>',
                'category' => 'purchase-requisition',
                'type' => 'document',
                'youtube_url' => null,
            ],
            [
                'title' => 'วิธีการใช้งาน Dashboard',
                'content' => '<h3>ภาพรวม Dashboard</h3><p>Dashboard แสดงข้อมูลสำคัญ:</p><ul><li>จำนวน PR ที่รออนุมัติ</li><li>สถิติการประหยัด</li><li>กราฟแสดงผลการดำเนินงาน</li><li>ปฏิทินการส่งมอบ</li></ul>',
                'category' => 'getting-started',
                'type' => 'document',
                'youtube_url' => null,
            ],
            [
                'title' => 'การจัดการผู้ขาย (Vendor)',
                'content' => '<h3>การลงทะเบียนผู้ขายใหม่</h3><p>1. ไปที่เมนู Vendors</p><p>2. คลิก "เพิ่มผู้ขาย"</p><p>3. กรอกข้อมูลบริษัท</p><p>4. อัพโหลดเอกสารที่จำเป็น</p><p>5. รอการอนุมัติจาก Admin</p>',
                'category' => 'vendor-management',
                'type' => 'document',
                'youtube_url' => null,
            ],
            [
                'title' => 'การใช้งานระบบเบื้องต้น - วิดีโอแนะนำ',
                'content' => '<p>วิดีโอสาธิตการใช้งานระบบ Innobic เบื้องต้น รวมถึงการนำทาง เมนูต่างๆ และฟีเจอร์หลัก</p>',
                'category' => 'getting-started',
                'type' => 'video',
                'youtube_url' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ', // ตัวอย่าง URL
            ],
            [
                'title' => 'การสร้างรายงาน',
                'content' => '<h3>ประเภทรายงานที่มี</h3><ul><li>รายงาน PR Summary</li><li>รายงาน PO Summary</li><li>รายงานผลการปฏิบัติงานของผู้ขาย</li><li>รายงานการใช้งบประมาณ</li></ul><p>สามารถ Export เป็น Excel หรือ CSV ได้</p>',
                'category' => 'reports',
                'type' => 'document',
                'youtube_url' => null,
            ]
        ];
        
        foreach ($articles as $article) {
            \App\Models\KnowledgeArticle::create([
                'title' => $article['title'],
                'content' => $article['content'],
                'category' => $article['category'],
                'type' => $article['type'],
                'youtube_url' => $article['youtube_url'],
                'is_published' => true,
                'views_count' => rand(10, 100),
                'created_by' => $adminUser->id,
            ]);
        }
    }
}

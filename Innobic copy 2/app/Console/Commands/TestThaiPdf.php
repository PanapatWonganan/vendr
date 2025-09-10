<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Mccarlosen\LaravelMpdf\Facades\LaravelMpdf as PDF;
use Mpdf\Config\ConfigVariables;
use Mpdf\Config\FontVariables;

class TestThaiPdf extends Command
{
    protected $signature = 'test:thai-pdf';
    protected $description = 'Test Thai text display in PDF';

    public function handle()
    {
        $this->info("Creating Thai PDF test...");
        
        try {
            // Get mPDF default configurations
            $defaultConfig = (new ConfigVariables())->getDefaults();
            $fontDirs = $defaultConfig['fontDir'];
            
            $defaultFontConfig = (new FontVariables())->getDefaults();
            $fontData = $defaultFontConfig['fontdata'];
            
            // HTML with Thai text
            $html = '
            <!DOCTYPE html>
            <html>
            <head>
                <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
                <style>
                    body { 
                        font-family: serif;
                        font-size: 16px;
                        line-height: 1.5;
                    }
                    .thai-test {
                        color: red;
                        font-weight: bold;
                    }
                </style>
            </head>
            <body>
                <h1>ทดสอบการแสดงผลภาษาไทย</h1>
                <p>สวัสดีครับ ผมทดสอบการแสดงผลภาษาไทยใน PDF</p>
                <p class="thai-test">ข้อความนี้เป็นสีแดง: บริษัท อินโนบิค จำกัด</p>
                <p>ตัวเลข: ๑๒๓๔๕๖๗๘๙๐</p>
                <p>English text: Hello World 123</p>
                <p>วันนี้: ' . now()->format('d/m/Y') . '</p>
            </body>
            </html>';
            
            // Configuration
            $config = [
                'mode' => 'utf-8',
                'format' => 'A4',
                'orientation' => 'P',
                'tempDir' => storage_path('app/temp'),
                'default_font' => 'freeserif',
                'autoLangToFont' => true,
                'autoScriptToLang' => true
            ];
            
            $pdf = PDF::loadHTML($html, [], [], $config);
            $pdfContent = $pdf->output();
            
            // Save file
            $filename = 'thai-test-' . now()->format('YmdHis') . '.pdf';
            $fullPath = storage_path("app/pdf-tests/{$filename}");
            file_put_contents($fullPath, $pdfContent);
            
            $this->info("Thai PDF test created successfully!");
            $this->info("File: {$fullPath}");
            $this->info("Size: " . number_format(strlen($pdfContent)) . " bytes");
            
            return 0;
            
        } catch (\Exception $e) {
            $this->error("Failed to create Thai PDF: " . $e->getMessage());
            return 1;
        }
    }
}
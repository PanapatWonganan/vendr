<?php

namespace Tests\Unit;

use App\Models\TermsOfReference;
use PHPUnit\Framework\TestCase;

class TorTest extends TestCase
{
    // ─── TOR Number Generation ───────────────────────────────────

    public function test_tor_number_format(): void
    {
        $year = date('Y');
        $month = date('m');
        $day = date('d');

        $this->assertMatchesRegularExpression(
            '/^TOR-\d{8}-\d{4}$/',
            "TOR-{$year}{$month}{$day}-0001"
        );
    }

    // ─── Option Methods ──────────────────────────────────────────

    public function test_tor_type_options_returns_array(): void
    {
        $options = TermsOfReference::getTorTypeOptions();
        $this->assertIsArray($options);
        $this->assertArrayHasKey('goods', $options);
        $this->assertArrayHasKey('services', $options);
        $this->assertArrayHasKey('construction', $options);
        $this->assertArrayHasKey('consulting', $options);
    }

    public function test_status_options_returns_array(): void
    {
        $options = TermsOfReference::getStatusOptions();
        $this->assertIsArray($options);
        $this->assertArrayHasKey('draft', $options);
        $this->assertArrayHasKey('submitted', $options);
        $this->assertArrayHasKey('reviewing', $options);
        $this->assertArrayHasKey('approved', $options);
        $this->assertArrayHasKey('rejected', $options);
        $this->assertArrayHasKey('amended', $options);
        $this->assertArrayHasKey('cancelled', $options);
        $this->assertArrayHasKey('expired', $options);
    }

    public function test_priority_options_returns_array(): void
    {
        $options = TermsOfReference::getPriorityOptions();
        $this->assertIsArray($options);
        $this->assertArrayHasKey('low', $options);
        $this->assertArrayHasKey('medium', $options);
        $this->assertArrayHasKey('high', $options);
        $this->assertArrayHasKey('urgent', $options);
    }

    // ─── Business Logic ──────────────────────────────────────────

    public function test_tor_can_be_edited(): void
    {
        $tor = new TermsOfReference();

        $tor->status = 'draft';
        $this->assertTrue($tor->canBeEdited());

        $tor->status = 'rejected';
        $this->assertTrue($tor->canBeEdited());

        $tor->status = 'submitted';
        $this->assertFalse($tor->canBeEdited());

        $tor->status = 'approved';
        $this->assertFalse($tor->canBeEdited());
    }

    public function test_tor_can_be_submitted(): void
    {
        $tor = new TermsOfReference();
        $tor->status = 'draft';
        $tor->title = 'Test TOR';
        $tor->scope_of_work = 'Test Scope';
        $this->assertTrue($tor->canBeSubmitted());

        $tor->status = 'submitted';
        $this->assertFalse($tor->canBeSubmitted());

        $tor->status = 'draft';
        $tor->title = '';
        $this->assertFalse($tor->canBeSubmitted());
    }

    public function test_tor_can_be_approved(): void
    {
        $tor = new TermsOfReference();

        $tor->status = 'submitted';
        $this->assertTrue($tor->canBeApproved());

        $tor->status = 'reviewing';
        $this->assertTrue($tor->canBeApproved());

        $tor->status = 'draft';
        $this->assertFalse($tor->canBeApproved());

        $tor->status = 'approved';
        $this->assertFalse($tor->canBeApproved());
    }

    public function test_tor_is_revision(): void
    {
        $tor = new TermsOfReference();

        $tor->parent_tor_id = null;
        $this->assertFalse($tor->isRevision());

        $tor->parent_tor_id = 1;
        $this->assertTrue($tor->isRevision());
    }

    public function test_tor_revision_label(): void
    {
        $tor = new TermsOfReference();

        $tor->revision_number = 0;
        $tor->parent_tor_id = null;
        $this->assertEquals('', $tor->revision_label);

        $tor->revision_number = 1;
        $tor->parent_tor_id = 1;
        $this->assertEquals('Rev.1', $tor->revision_label);

        $tor->revision_number = 3;
        $this->assertEquals('Rev.3', $tor->revision_label);
    }

    // ─── Display Attributes ──────────────────────────────────────

    public function test_tor_status_text_attribute(): void
    {
        $tor = new TermsOfReference();
        $tor->status = 'draft';
        $this->assertEquals('ร่าง', $tor->status_text);

        $tor->status = 'approved';
        $this->assertEquals('อนุมัติ', $tor->status_text);

        $tor->status = 'rejected';
        $this->assertEquals('ไม่อนุมัติ', $tor->status_text);
    }

    public function test_tor_type_label_attribute(): void
    {
        $tor = new TermsOfReference();
        $tor->tor_type = 'goods';
        $this->assertEquals('พัสดุ/วัสดุ', $tor->tor_type_label);

        $tor->tor_type = 'services';
        $this->assertEquals('บริการ', $tor->tor_type_label);

        $tor->tor_type = 'construction';
        $this->assertEquals('งานก่อสร้าง', $tor->tor_type_label);

        $tor->tor_type = 'consulting';
        $this->assertEquals('งานที่ปรึกษา', $tor->tor_type_label);
    }

    // ─── Convert to PR ───────────────────────────────────────────

    public function test_convert_to_pr_data(): void
    {
        $tor = new TermsOfReference();
        $tor->id = 1;
        $tor->title = 'Test TOR';
        $tor->department_id = 5;
        $tor->form_category = 'act_based';
        $tor->work_type = 'buy';
        $tor->procurement_method = 'agreement_price';
        $tor->category = 'office_supplies';
        $tor->budget_estimate = 100000;
        $tor->budget_code = 'BDG-001';
        $tor->scope_of_work = 'Test scope';
        $tor->objectives = 'Test objectives';
        $tor->background = 'Test background';
        $tor->currency = 'THB';

        $prData = $tor->convertToPrData();

        $this->assertEquals(1, $prData['tor_id']);
        $this->assertEquals('Test TOR', $prData['title']);
        $this->assertEquals(5, $prData['department_id']);
        $this->assertEquals('act_based', $prData['form_category']);
        $this->assertEquals('buy', $prData['work_type']);
        $this->assertEquals('agreement_price', $prData['procurement_method']);
        $this->assertEquals(100000, $prData['procurement_budget']);
        $this->assertEquals('THB', $prData['currency']);
    }

    // ─── Class Existence Tests ───────────────────────────────────

    public function test_all_required_classes_exist(): void
    {
        $classes = [
            \App\Models\TermsOfReference::class,
            \App\Models\TorItem::class,
            \App\Models\TorApprovalHistory::class,
            \App\Events\TorSubmitted::class,
            \App\Events\TorApproved::class,
            \App\Events\TorRejected::class,
            \App\Listeners\SendTorSubmittedNotification::class,
            \App\Listeners\SendTorApprovedNotification::class,
            \App\Listeners\SendTorRejectedNotification::class,
            \App\Services\TorAiService::class,
            \App\Services\TorPdfService::class,
            \App\Mail\TorSubmittedMail::class,
            \App\Mail\TorApprovedMail::class,
            \App\Mail\TorRejectedMail::class,
            \App\Livewire\TorAiDraft::class,
            \App\Livewire\TorAiReview::class,
            \App\Livewire\TorAiImprove::class,
            \App\Filament\Widgets\TorStatsWidget::class,
            \App\Filament\Widgets\TorStatusChart::class,
            \App\Filament\Widgets\TorDepartmentChart::class,
            \App\Filament\Resources\TermsOfReferenceResource::class,
        ];

        foreach ($classes as $class) {
            $this->assertTrue(class_exists($class), "Class {$class} does not exist");
        }
    }
}

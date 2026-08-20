# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

VENDR is a multi-company procurement management system built for Thai businesses (Innobic Asia, Innobic Nutrition, Innobic LL). It handles Terms of Reference (TOR), Purchase Requisitions (PR), Purchase Orders (PO), Goods Receipts (GR), Vendor Management, Payment Milestones, Value Analysis, and SLA Tracking.

## Tech Stack

- **Laravel 12** + **Filament 3.2** (Livewire 3-based admin panel)
- **Vite 6** + **Tailwind CSS 3** + **Alpine.js 3** (with collapse, focus, persist plugins) + Sarabun font (Thai)
- **MySQL** with multi-tenancy via `company_id` column (single DB, session-based company selection)
- **SendGrid** (email via SMTP), **Telegram Bot SDK** (notifications), **OpenAI** (AI services)
- **mPDF** (Thai PDF generation with freeserif font), **Maatwebsite Excel** (exports), **ApexCharts** (data visualization)

### Key Dependencies

**Backend:** `barryvdh/laravel-dompdf`, `carlos-meneses/laravel-mpdf`, `mpdf/mpdf` (^8.2), `doctrine/dbal` (^3.2), `irazasyed/telegram-bot-sdk` (^3.15), `leandrocfe/filament-apex-charts` (^3.2), `maatwebsite/excel` (^3.1), `saade/filament-fullcalendar` (^3.2), `sendgrid/sendgrid` (^8.1)

**Dev:** `laravel/breeze` (^2.3, auth scaffolding), `laravel/pail` (^1.2.2, log viewer), `laravel/pint` (code style)

## Development Commands

```bash
composer install && npm install  # First-time setup
composer run dev                 # Start all 4 services concurrently:
                                 #   server (artisan serve)
                                 #   queue (queue:listen --tries=1)
                                 #   logs (pail --timeout=0)
                                 #   vite (npm run dev)
npm run build                    # Production build
```

```bash
# Testing
composer run test                                               # Clear config + run all tests
php artisan test                                                # Run tests via Artisan
php artisan test tests/Unit/TorTest.php                         # Single file
php artisan test tests/Unit/TorTest.php --filter=test_tor_can   # Single method
php artisan test --parallel                                     # Parallel execution
```

Tests use SQLite in-memory with overrides: `DB_CONNECTION=sqlite`, `DB_DATABASE=:memory:`, queue `sync`, session/cache/mail `array`, bcrypt rounds `4`. Complete isolation from production.

```bash
# Migrations
php artisan migrate              # Apply new migrations ONLY
php artisan migrate:status       # Check migration status
php artisan migrate:rollback     # Revert last batch
```

**CRITICAL: Never use `migrate:fresh`, `migrate:refresh`, or `db:wipe` — production data exists. This has caused data loss before.**

## Authentication System

- **Primary login:** Filament `/admin/login` (single entry point)
- **Backward compat:** `/login` redirects to `/admin/login`, password reset routes from Breeze preserved
- **Custom responses** in `AppServiceProvider::register()`:
  - `LoginResponse` — redirects to CompanySelect page if no `company_id` in session, otherwise to dashboard
  - `LogoutResponse` — clears all company session data (`company_id`, `company_connection`, `company_name`, `company_display_name`) before redirect

## Multi-Company Architecture

- **Single MySQL database** with `company_id` column for tenant isolation
- User selects company → session stores `company_id` + `company_connection` → `CompanyMiddleware` validates → queries filtered by `company_id`
- `config/database.php` defines named connections (`innobic_asia`, `innobic_nutrition`, `innobic_ll`) but all currently point to the same physical database via separate env vars
- `CompanyMiddleware` caches company active status for 60 seconds (`company_active_{id}`) to reduce DB queries

### Model Inheritance

**`BaseModel` (extends Model):** Auto-injects `company_id` on create from session (guarded by `in_array('company_id', $model->getFillable())`). Provides `setCompanyConnection()`, `clearCompanyConnection()`, `getCurrentConnection()` static helpers.

- Models extending `BaseModel`: TermsOfReference, TorItem, TorApprovalHistory, PurchaseOrder, PurchaseOrderItem, GoodsReceipt, GoodsReceiptItem, PaymentMilestone, PoAmendment, Supplier, CommitteeMember, PurchaseOrderFile, SlaTracking, ProcurementAnomaly
- Models extending `Model` directly (master data): User, Role, Permission, Company, Department, ApprovalLevel, ChatConversation, ChatMessage, KnowledgeArticle
- Models extending `Model` with manual `company_id` handling: PurchaseRequisition, ValueAnalysis, ValueAnalysisItem, Vendor, VendorEvaluation, VendorAssessment

**Gotcha:** `PurchaseRequisition` does NOT extend `BaseModel` — it manages `company_id` via fillable array directly. Newer models like `TermsOfReference` use `BaseModel`.

**Gotcha:** Master-data models (User, Role, Permission, Company, Department) use dynamic `getConnectionName()` that returns `config('database.default')` during tests and `'mysql'` in production — not `protected $connection`.

### Status Constants

PurchaseOrder, PurchaseRequisition, TermsOfReference, and ValueAnalysis define `STATUS_*` constants and a `STATUSES` array for all valid statuses. Prefer these constants over raw strings.

## Key Architecture Patterns

### Event-Driven Notifications

Events and listeners are **manually registered** in `EventServiceProvider` via the `$listen` array (not auto-discovery, not `Event::listen()` closures):

| Event | Listener |
|-------|----------|
| `PurchaseRequisitionSubmitted` | `SendPurchaseRequisitionSubmittedNotification` |
| `PurchaseRequisitionApproved` | `SendPurchaseRequisitionApprovedNotification` |
| `PurchaseRequisitionRejected` | `SendPurchaseRequisitionRejectedNotification` |
| `PurchaseOrderApproved` | `SendPurchaseOrderApprovedNotification` |
| `PurchaseOrderRejected` | `SendPurchaseOrderRejectedNotification` |
| `PurchaseOrderAmended` | `SendPurchaseOrderAmendedNotification` |
| `GoodsReceiptCreated` | `SendGoodsReceiptNotification` + `SendGoodsReceiptCreatedNotification` |
| `PaymentMilestonePaid` | `SendPaymentMilestoneNotification` |
| `TorSubmitted` | `SendTorSubmittedNotification` |
| `TorApproved` | `SendTorApprovedNotification` |
| `TorRejected` | `SendTorRejectedNotification` |

All listeners implement `ShouldQueue` + `InteractsWithQueue` + `failed()`. Duplicate prevention via cache key with 5-minute TTL.

### Model Observers

Registered in `AppServiceProvider::boot()`:
- `VendorEvaluationObserver` — triggers score recalculation
- `PurchaseRequisitionObserver` — SLA timestamp capture (`submitted_at`, `pr_approved_at`)
- `PurchaseOrderObserver` — captures `po_created_at` and `po_approved_at` for SLA
- `GoodsReceiptObserver` — GR lifecycle hooks

### SLA Tracking

`SlaService` calculates working days (excludes weekends) against standards: agreement_price (9 days), invitation_bid (25 days), open_bid (34 days), selection (20 days). Grades: S (<=50%), A (<=70%), B (<=90%), C (<=100%), D (<=120%), F (>120%). Also tracks TOR submission-to-approval via `trackTorSubmissionToApproval()`.

### Role-Based Authorization

Roles support: scoped by department (`department_id` on pivot), time-limited (`expires_at`), toggleable (`is_active`). Check with `$user->hasRole()` / `$user->hasAnyRole()` / `$user->hasPermission()`. Active roles filter by both `role_user.is_active` and `roles.is_active` and unexpired `expires_at`.

### OpenAI Integration Pattern

Uses `Http` facade directly (no SDK package). Pattern used by `VendorRiskAssessmentService`, `AiChatService`, and `TorAiService`:
```php
Http::timeout(60)->withToken(config('services.openai.api_key'))
    ->post('https://api.openai.com/v1/chat/completions', [
        'model' => config('services.openai.model', 'gpt-4o-mini'),
        'messages' => [...],
        'temperature' => 0.3,
        'response_format' => ['type' => 'json_object'],
    ]);
```
Always implement rule-based fallback when API key is missing or API fails.

### Polymorphic Attachments

`ProcurementAttachment` uses `morphMany` for PR, PO, TOR, and other entities. Categories include: specification, quotation, proposal, contract, invoice, receipt, delivery_note, inspection_report, approval_document, tor_specification, tor_drawing, tor_reference, tor_template, other.

## Services

| Service | Purpose |
|---------|---------|
| `SlaService` | Working day calculation, SLA grading (S-F), tracking per stage |
| `TorAiService` | AI TOR draft generation, review, field improvement (OpenAI + fallback templates) |
| `TorBuilderPdfService` | TOR document PDF (mPDF + freeserif, repeating header/page numbers) |
| `PurchaseOrderPdfService` | SOW/PO PDF documents |
| `DeliveryNotePdfService` | Delivery note PDFs |
| `VendorRiskAssessmentService` | AI vendor risk assessment (DBD data + OpenAI + rule-based fallback) |
| `VendorScoreService` | Vendor evaluation score aggregation (monthly/quarterly/annual) |
| `AnomalyDetectionService` | Price anomalies, split PRs, budget overruns, vendor concentration |
| `AiChatService` | OpenAI chat with function calling (6 tools: PR/PO/GR status, budget, vendor, knowledge) |
| `TelegramBotService` | Webhook processing, command handling (/today, /approvals, /tor_pending, etc.), notifications |
| `DbdApiService` | Thai Department of Business Development API integration |
| `AmendmentService` | PO amendment workflow via VA revisions |

## Core Workflows

1. **TOR:** draft → submitted → reviewing → approved/rejected → amended/cancelled (approved TOR can create PR)
2. **PR:** draft → submitted → pending_approval → approved/rejected → auto-creates PO draft
3. **PO:** draft → pending_approval → approved → sent_to_supplier → acknowledged → partially_received/fully_received → closed
4. **GR:** draft → completed/returned (inspection: pending → passed/failed/partial)
5. **Payment Milestones:** pending → due → paid/overdue (tracked per PO with percentage splits)
6. **Value Analysis:** draft → in_progress → completed → approved/rejected (revision chain via `parent_va_id`)
7. **Vendor:** pending → approved/rejected/suspended

## TOR Module (Terms of Reference) — Document Builder (rebuilt 2026-08)

The old 5-step wizard + AI Livewire components (`TorAiDraft/Review/Improve`) + `TorPdfService` were REMOVED. TOR is now a document builder mirroring the customer's paper form (see `docs/TOR_DOCUMENT_BUILDER_SCHEMA.md`):

- **Models:** `TermsOfReference` (BaseModel; content lives in `document_sections` JSON), `TorTemplate` + `TorTemplateSection` (clause library, 6 procurement types, `{{party}}`/`{{company_*}}`/`{{penalty_*}}` placeholders resolved at creation), `TorApprovalHistory`
- **Builder UI:** `app/Filament/Pages/TorBuilder` (`/admin/tor-builder`, edit via `?tor={id}`) — setup form + custom Blade editor (nested add/remove items, hide sections with renumber, timeline 1-of-3, payment options summing to 100%, copy old TOR, Submit button)
- **Resource:** `TermsOfReferenceResource` is list-only; table actions: preview/PDF/edit(→builder)/submit/approve/reject/create_pr
- **Services:** `TorDocumentService` (build/validate/renumber/flattenScope), `TorBuilderPdfService` (mPDF, header+page numbers repeat every page)
- **Routes:** `/tor-builder/{tor}/preview` (HTML) and `/tor-builder/{tor}/pdf`
- **Seeder:** `TorTemplateSeeder` (idempotent; clause text from customer Word templates)
- **Events/Listeners:** `TorSubmitted`, `TorApproved`, `TorRejected` with Email (links point to builder/preview) + Telegram
- **Conversion:** `convertToPrData()` + `convertItemsToPrItems()` — builder TORs derive payment_schedule from the payment section and a single lump-sum PR item from budget_estimate
- **Revision chain:** via `parent_tor_id`

## Livewire Components

| Component | Purpose |
|-----------|---------|
| `ChatWidget` | Floating AI chat assistant with conversation history |
| `NotificationBar` | Top-bar notification display |
| `SearchableTable` | Reusable searchable/filterable table |

### Livewire-to-Filament Event Pattern

Child Livewire components embedded in Filament forms via `Forms\Components\Livewire::make()` communicate with the parent Filament page using `$this->dispatch('event-name', ...)`. The parent page listens with `#[On('event-name')]` attribute and sets `$this->data[$field] = $value` to update form fields.

## Filament Panel Configuration

**Panel ID:** `admin` | **Path:** `/admin` | **Max width:** full | **Font:** Sarabun

Features: dark mode, collapsible sidebar, ApexCharts plugin, auto-discovery of Resources/Pages. Brand logo loaded dynamically from selected company. Colors: primary (blue), warning (orange), success (green), danger (red).

**Auth middleware stack:** standard Filament middleware + `CustomFilamentAuth` + `CompanyMiddleware`.

### Resources (16)

`TermsOfReferenceResource`, `PurchaseRequisitionResource`, `PurchaseOrderResource`, `GoodsReceiptResource`, `PaymentMilestoneResource`, `ValueAnalysisResource`, `VendorResource`, `VendorEvaluationResource`, `VendorPerformanceReportResource`, `CompanyResource`, `DepartmentResource`, `UserResource`, `KnowledgeArticleResource`, `ContractApprovalResource`, `SlaReportResource`, `ProcurementAttachmentResource`.

All resources use custom pages (List, Create, Edit, View) with inline table actions for approve/reject workflows.

### Custom Pages (7)

`Dashboard`, `CompanySelect`, `ProcurementReports`, `VendorRiskAssessment`, `AnomalyDetection`, `CalendarPage`, `KnowledgeBase` — standalone pages not tied to a resource CRUD.

### Widgets (15)

**Dashboard:** DashboardStatsOverview, ProcurementStatsOverview, SlaPerformanceOverview, PendingApprovalsChart, UpcomingDeliveries
**TOR:** TorStatsWidget, TorStatusChart, TorDepartmentChart
**Vendor:** VendorGradeStats, VendorGradeApexChart
**Value Analysis:** ValueAnalysisStats, ValueAnalysisSavingsChart
**Utility:** CompanySelector, CalendarLinkWidget, DeliveryCalendarWidget

### Navigation Groups

Procurement Management, Milestone Management, Contract Management, Master Data, Reports & Analytics, System Management, User Management.

### Custom Navigation Items

Defined in `AdminPanelProvider` with role-based visibility:
- My Requests (user's own PRs)
- Pending PR Approvals (with live count badge)
- Pending PO Approvals (with live count badge)
- Direct procurement <=10k THB
- Direct procurement <=100k THB

## Middleware

- `CompanyMiddleware` — validates `company_id` in session, caches active status 60s, redirects to company selection if missing
- `CustomFilamentAuth` — extends Filament's default auth to integrate company context
- `CheckAdminRole` — guards admin-only routes

## Routing

Root `/` redirects to `/admin` (Filament panel). Company selection happens before panel access via `CompanyMiddleware`. All procurement UI is within the Filament admin panel.

**File downloads** (protected by auth + CompanyMiddleware):
- `GET /po-files/{file}/download` — PO file downloads
- `GET /pr-attachments/{attachment}/download` — PR attachment downloads (+ view variant)

**Telegram:** `POST /telegram/webhook/{token}` (excluded from CSRF)

## Third-Party Services

All configured in `config/services.php` with env vars:

| Service | Env Vars | Used By |
|---------|----------|---------|
| **OpenAI** | `OPENAI_API_KEY`, `OPENAI_MODEL` (default: gpt-4o-mini) | TorAiService, AiChatService, VendorRiskAssessmentService |
| **DBD API** | `DBD_API_BASE_URL`, `DBD_API_TOKEN` | DbdApiService |
| **Telegram** | `TELEGRAM_BOT_TOKEN`, `TELEGRAM_WEBHOOK_URL`, `TELEGRAM_BOT_USERNAME` | TelegramBotService |
| **SendGrid** | `SENDGRID_API_KEY` (via SMTP mailer) | All Mail classes |
| **Procurement** | `PROCUREMENT_FALLBACK_ADMIN_EMAIL` (default: admin@innobic.com) | SendDeliveryReminders |

Telegram user linking: OTP-based via `/link_otp` command. User fields: `telegram_chat_id`, `telegram_username`, `telegram_otp`, `telegram_otp_expires_at`.

## Queue

Queue driver is `database`. The `composer run dev` command starts `queue:listen` automatically. For production use `queue:work --daemon --tries=3`.

## Console Commands

### Scheduled (routes/console.php)

- **Daily 8:00 AM:** `TelegramMorningBriefing`, `SendGoodsReceiptReminders`
- **Daily 9:00 AM:** `SendDeliveryReminders`, `TelegramBudgetAlert`
- **Daily 10:00 AM:** `TelegramSmartReminders`
- **Twice daily (7 AM, 5 PM):** `ScanAnomalies`
- **Weekly Monday 8:00 AM:** `TelegramWeeklyDigest`

### Development/Testing

`telegram:poll` (local dev polling), `test:email`, `test:po-email`, `test:po-email2`, `test:po-approval-email`, `test:pr-emails`, `test:po-pdf`, `test:gr-creation`, `sla:backfill`, `company:set-default`

## Thai Language Conventions

- All user-facing strings use Thai labels; database enums use English slugs (e.g., `status = 'approved'`, label = `'อนุมัติ'`)
- Sarabun font configured in Tailwind and Filament for Thai character rendering
- PDF generation uses freeserif font (mPDF) for full Thai/Unicode support
- Timezone: Asia/Bangkok (UTC+7)
- Bootstrap 5 pagination styling (configured in `AppServiceProvider`)
- Default string length: 191 characters for MySQL compatibility

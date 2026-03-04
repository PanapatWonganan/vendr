# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

VENDR is a multi-company procurement management system built for Thai businesses (Innobic Asia, Innobic Nutrition, Innobic LL). It handles Purchase Requisitions (PR), Purchase Orders (PO), Goods Receipts (GR), Vendor Management, Payment Milestones, and SLA Tracking.

## Tech Stack

- **Laravel 12** + **Filament 3.2** (Livewire-based admin panel)
- **Vite 6** + **Tailwind CSS 3** + **Alpine.js 3** + Sarabun font (Thai)
- **MySQL** with multi-database architecture (1 master + 3 company databases)
- **SendGrid** (email), **Telegram Bot SDK** (notifications), **OpenAI** (vendor risk analysis)
- **DOMPDF/MPDF** (PDF generation), **Maatwebsite Excel** (exports)

## Development Commands

```bash
composer run dev          # Start all services (serve + queue + pail + vite)
npm run build             # Production build
composer run test         # Run PHPUnit tests
php artisan test          # Run tests via Artisan
php artisan migrate       # Apply new migrations ONLY
```

**CRITICAL: Never use `migrate:fresh`, `migrate:refresh`, or `db:wipe` — production data exists across all databases.**

## Multi-Company Database Architecture

- **Master DB (`vendr`):** Users, Roles, Permissions, Companies
- **Company DBs (`innobic_asia`, `innobic_nutrition`, `innobic_ll`):** All procurement data

User selects company → session stores `company_id` + `company_connection` → `CompanyMiddleware` validates → `BaseModel::getConnectionName()` routes queries to correct database.

All procurement models extend `BaseModel` which:
- Auto-injects `company_id` on create from session
- Dynamically switches database connection based on session

## Key Architecture Patterns

### Event-Driven Notifications
Events (`PurchaseOrderApproved`, `PurchaseRequisitionSubmitted`, etc.) trigger listeners that send Email + Telegram + PDF. Duplicate prevention via cache key with 5-minute TTL. All dispatched to queue.

### Observer-Based SLA Tracking
`PurchaseOrderObserver` captures `po_created_at` and `po_approved_at` timestamps. `SlaService` calculates working days (excludes weekends) against standards: agreement_price (9 days), invitation_bid (25 days), open_bid (34 days).

### Role-Based Authorization
Roles support: scoped by department (`department_id` on pivot), time-limited (`expires_at`), toggleable (`is_active`). Check with `$user->hasRole()` / `$user->hasAnyRole()`.

### Filament Resource Structure
15 resources with custom pages, inline actions (approve/reject with confirmation), and widgets. Key resources: `PurchaseRequisitionResource`, `PurchaseOrderResource`, `GoodsReceiptResource`, `PaymentMilestoneResource`, `VendorResource`.

## Core Workflows

1. **PR:** draft → submitted → pending_approval → approved/rejected → auto-creates PO draft
2. **PO:** draft → pending_approval → approved → sent → acknowledged → closed
3. **GR:** draft → received → pending_approval → approved → triggers payment milestone updates
4. **Payment Milestones:** pending → due → paid/overdue (tracked per PO with percentage splits)

## Telegram Integration

Bot commands: `/start`, `/link_otp <code>`, `/today`, `/approvals`, `/deliveries`, `/budget`. Webhook at `POST /telegram/webhook/{token}`. Automated: morning briefings, delivery reminders, budget alerts, anomaly alerts.

## Queue

Queue driver is `database`. The `composer run dev` command starts `queue:listen` automatically. For production use `queue:work --daemon --tries=3`.

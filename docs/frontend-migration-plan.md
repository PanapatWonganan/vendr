# VENDR Frontend Migration Plan

## Current Stack
- Laravel 12 + Filament 3 + Livewire 3 + Alpine.js
- Tailwind CSS, Sarabun font
- Multi-company procurement system (Thai language)

---

## Recommended Options

### Option 1: Laravel + Inertia.js + React + shadcn/ui (Recommended)
- **Pros:** Beautiful modern UI, uses existing Laravel backend, no separate API needed, huge community
- **Cons:** React learning curve if team is PHP-only
- **Key packages:** `@inertiajs/react`, `shadcn/ui`, `@tanstack/react-table`, `recharts`, `tailwindcss`
- **Migration path:** Gradual - Inertia can coexist with Filament during transition

### Option 2: Laravel + Inertia.js + Vue 3 + PrimeVue
- **Pros:** Rich pre-built components (DataTable, Calendar), easier for PHP devs, great for ERP/data-heavy apps
- **Cons:** Less "trendy" than React ecosystem
- **Key packages:** `@inertiajs/vue3`, `primevue`, `@primevue/themes`, `chart.js`
- **Migration path:** Same as Option 1 - gradual via Inertia

### Option 3: Next.js (Separate Frontend)
- **Pros:** Most flexible, best performance (SSR/SSG), can scale frontend independently
- **Cons:** Must build REST API in Laravel, maintain 2 projects, CORS handling
- **Key packages:** `next`, `shadcn/ui`, `next-auth`, `@tanstack/react-query`
- **Migration path:** Build new frontend project, connect to Laravel API

---

## Recommended Architecture (Option 1)

```
vendr/
├── app/                    # Laravel backend (existing)
│   ├── Http/Controllers/   # Return Inertia responses
│   ├── Models/             # Existing models (unchanged)
│   └── Services/           # Business logic (unchanged)
├── resources/
│   ├── js/
│   │   ├── app.tsx         # React entry point
│   │   ├── Pages/          # Inertia pages (replace Filament)
│   │   │   ├── Dashboard.tsx
│   │   │   ├── PR/
│   │   │   │   ├── Index.tsx
│   │   │   │   ├── Create.tsx
│   │   │   │   └── Show.tsx
│   │   │   ├── PO/
│   │   │   ├── Vendor/
│   │   │   └── ...
│   │   ├── Components/     # Reusable UI components
│   │   │   ├── ui/         # shadcn/ui components
│   │   │   ├── Layout/
│   │   │   │   ├── Sidebar.tsx
│   │   │   │   ├── Header.tsx
│   │   │   │   └── AppLayout.tsx
│   │   │   ├── DataTable/
│   │   │   ├── Charts/
│   │   │   └── Forms/
│   │   ├── Hooks/          # Custom React hooks
│   │   └── Lib/            # Utilities
│   └── css/
│       └── app.css         # Tailwind CSS
├── routes/
│   ├── web.php             # Inertia routes (new)
│   └── filament.php        # Filament routes (keep during transition)
└── vite.config.ts          # Vite + React config
```

## Migration Steps

### Phase 1: Setup & Foundation
- Install Inertia.js + React + TypeScript + Vite
- Install shadcn/ui + Tailwind CSS v4
- Create AppLayout with sidebar, header, breadcrumbs
- Build Dashboard page with stats cards and charts
- Setup authentication flow (login/logout)
- Company selector component

### Phase 2: Core Procurement Pages
- Purchase Requisition (CRUD + custom pages: MyRequests, PendingApprovals, DirectPurchase)
- Value Analysis (CRUD + PR linking)
- Purchase Order (CRUD + auto-fill from PR/VA)
- Goods Receipt (CRUD + inspection workflow)
- Payment Milestones (tracking + alerts)

### Phase 3: Master Data & Reports
- Vendor Management (CRUD + evaluation + risk assessment)
- Department Management
- Contract Management
- Knowledge Base
- Reports & Analytics dashboards
- SLA tracking

### Phase 4: Advanced Features
- Real-time notifications (Laravel Echo + Pusher)
- AI Chat widget (migrate existing Livewire to React)
- Calendar view
- Anomaly detection dashboard
- Export/Import functionality

### Phase 5: Cleanup
- Remove Filament dependency
- Remove Livewire dependency
- Full React-based admin panel

---

## Key shadcn/ui Components to Use

| Feature | Component |
|---------|-----------|
| Data tables | `<DataTable>` + `@tanstack/react-table` |
| Forms | `<Form>` + `react-hook-form` + `zod` |
| Charts | `recharts` or `@tremor/react` |
| Dialogs/Modals | `<Dialog>`, `<Sheet>`, `<AlertDialog>` |
| Navigation | `<Sidebar>`, `<Breadcrumb>`, `<NavigationMenu>` |
| Notifications | `<Toast>` (sonner) |
| File upload | `<Input type="file">` + `react-dropzone` |
| Date picker | `<Calendar>` + `<DatePicker>` |
| Status badges | `<Badge>` with color variants |
| Command palette | `<Command>` (Ctrl+K search) |

---

## Design References
- shadcn/ui examples: https://ui.shadcn.com/examples
- shadcn admin dashboard: https://github.com/salimi-my/shadcn-ui-sidebar
- Taxonomy (Next.js + shadcn): https://tx.shadcn.com
- PrimeVue Sakai template: https://sakai.primevue.org

---

## Notes
- Inertia.js allows gradual migration: Filament and Inertia can run side-by-side
- All existing Laravel models, services, middleware remain unchanged
- Only controllers and views need to be rewritten
- Thai language support: use i18next (React) or vue-i18n (Vue)
- Keep Sarabun font for Thai readability

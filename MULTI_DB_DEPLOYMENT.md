# Multi-Database Deployment Guide

## Overview
This guide will help you deploy the multi-database setup on production server where each company has its own isolated database.

## Architecture
- **Company 1 (Innobic Asia)** → Database: `innobic_asia`
- **Company 2 (Innobic Nutrition)** → Database: `innobic_nutrition`
- **Company 3 (Innobic LL)** → Database: `innobic_ll`

## Step 1: Pull Latest Code

```bash
cd /var/www/innobic
git pull origin main
```

## Step 2: Verify Database Connections

Check that `.env` has the multi-database credentials:

```bash
cat .env | grep INNOBIC
```

Should show:
```
INNOBIC_ASIA_DB_USERNAME=innobic_asia_user
INNOBIC_ASIA_DB_PASSWORD=InnobicAsia2024

INNOBIC_NUTRITION_DB_USERNAME=innobic_nutrition_user
INNOBIC_NUTRITION_DB_PASSWORD=InnobicNutrition2024

INNOBIC_LL_DB_USERNAME=innobic_ll_user
INNOBIC_LL_DB_PASSWORD=InnobicLL2024
```

## Step 3: Verify Databases Exist

```bash
mysql -u root -p'TempPassword123!' -e "SHOW DATABASES LIKE 'innobic%';"
```

Should show:
```
innobic_asia
innobic_nutrition
innobic_ll
innobic_production
```

## Step 4: Run Migrations on All Databases

```bash
# Run migrations on each company database
php artisan migrate --database=innobic_asia
php artisan migrate --database=innobic_nutrition
php artisan migrate --database=innobic_ll
```

## Step 5: Update Company Database Connections

```bash
php update_company_connections.php
```

This will:
- Set Company 1 to use `innobic_asia` connection
- Set Company 2 to use `innobic_nutrition` connection
- Set Company 3 to use `innobic_ll` connection

## Step 6: Test Company Switching

1. Login to https://innobicprocurement.com/admin
2. Select a company from the dashboard
3. Try switching between companies using the company selector
4. Create a test Purchase Requisition for each company
5. Verify data isolation:

```bash
# Check Company 1 data
mysql -u innobic_asia_user -pInnobicAsia2024 innobic_asia -e "SELECT COUNT(*) FROM purchase_requisitions;"

# Check Company 2 data
mysql -u innobic_nutrition_user -pInnobicNutrition2024 innobic_nutrition -e "SELECT COUNT(*) FROM purchase_requisitions;"

# Check Company 3 data
mysql -u innobic_ll_user -pInnobicLL2024 innobic_ll -e "SELECT COUNT(*) FROM purchase_requisitions;"
```

## Step 7: Clear Cache

```bash
php artisan cache:clear
php artisan config:clear
php artisan view:clear
```

## Verification Checklist

- [ ] All 3 databases exist and are accessible
- [ ] Migrations completed successfully on all databases
- [ ] Company connections updated (innobic_asia, innobic_nutrition, innobic_ll)
- [ ] Can switch between companies in the UI
- [ ] Each company creates data in its own database
- [ ] No data mixing between companies
- [ ] Sessions persist after company switch

## Troubleshooting

### Cannot switch companies
- Check session driver is set to `file` in .env
- Verify storage/framework/sessions/ is writable

### Database connection errors
- Verify database users have correct permissions
- Check .env credentials match MySQL users

### Data appears in wrong database
- Verify company.database_connection field is set correctly
- Check session('company_connection') is being set when switching

### 403 errors
- Ensure CustomFilamentAuth middleware is being used
- Check User model has canAccessPanel() method

## Database Structure

Each company database contains:
- purchase_requisitions
- purchase_orders
- goods_receipts
- vendors
- payment_milestones
- procurement_attachments
- contract_approvals
- committee_members
- sla_trackings
- vendor_evaluations
- vendor_scores

Shared tables (in innobic_production):
- users
- roles
- permissions
- departments
- companies
- migrations

## Important Notes

1. **Data Isolation**: Each company's operational data is completely isolated in separate databases
2. **User Access**: Users are shared across all companies (stored in main database)
3. **Session Management**: Company selection is stored in session and persists across requests
4. **Connection Switching**: BaseModel automatically uses the correct database based on session

# Terms & Conditions Acceptance - Setup Guide

## Quick Start

1. **Run Migrations**
   ```bash
   php artisan migrate
   ```

2. **Create Initial Terms Version** (Choose one method)

   **Option A: Via Admin UI (Recommended)**
   - Log in as admin
   - Navigate to: Settings > Terms & Conditions
   - Click "Create New Version"
   - Version: `2026-01`
   - Title: `NRAPA Membership Terms & Conditions`
   - Upload the HTML file: `C:\Users\pchar\Downloads\NRAPA_Membership_Terms_and_Conditions.html`
   - OR paste the HTML content
   - Click "Create Version"
   - Click "Activate" on the new version

   **Option B: Via Seeder**
   ```bash
   php artisan db:seed --class=TermsVersionSeeder
   ```
   Then activate it via admin UI.

3. **Test the Flow**
   - Create a test member (or use existing)
   - Try to access member portal features
   - Should be redirected to `/terms` acceptance page
   - Accept terms
   - Verify access is granted

## How It Works

### For Members
1. When membership is activated, they receive an email requiring terms acceptance
2. They must visit `/terms` and accept before accessing features
3. Once accepted, they can:
   - Access dashboard
   - View/download certificates
   - Submit endorsement requests
   - Access all member features

### For Admins
- Can manage terms versions at: **Admin > Settings > Terms & Conditions**
- Can create new versions when terms change
- Can preview terms before activating
- Only one version can be active at a time

### Hard Gates
- **Cannot become "Member in Good Standing"** without acceptance
- **Cannot issue certificates/letters** without acceptance  
- **Cannot submit endorsement requests** without acceptance
- **Cannot access member portal** without acceptance

## Email Triggers

Terms acceptance emails are automatically sent when:
- Membership status changes to 'active' (via Membership model hook)

To send manually:
```php
\App\Helpers\TermsHelper::sendTermsAcceptanceEmail($user);
```

## Admin Routes

- **Manage Terms**: `/admin/settings/terms`
- **Preview Terms**: `/admin/settings/terms/{version}/preview`

## Member Routes

- **Accept Terms**: `/terms` (accessible without terms middleware)

## Database Tables

- `terms_versions` - Stores terms versions
- `terms_acceptances` - Stores user acceptances with audit trail

## Important Notes

- If no active terms version exists, users can access features (admin should set one)
- Admins, owners, and developers bypass terms acceptance
- Each acceptance records: version, timestamp, IP, user agent
- Historical acceptances are preserved for audit purposes

# Terms & Conditions Acceptance System - Implementation Summary

## Overview
Implemented a comprehensive Terms & Conditions acceptance system that acts as a hard gate before membership activation and document issuance.

## Completed Tasks

### ✅ A) Database Design
1. **`terms_versions` table** (`2026_01_29_100007_create_terms_versions_table.php`)
   - `id`, `version` (unique), `title`, `html_path`, `html_content`, `is_active`, `published_at`, timestamps
   - Index on `is_active` for quick lookup

2. **`terms_acceptances` table** (`2026_01_29_100008_create_terms_acceptances_table.php`)
   - `id`, `user_id` (FK), `terms_version_id` (FK), `accepted_at`, `accepted_ip`, `accepted_user_agent`, timestamps
   - Unique constraint on `(user_id, terms_version_id)` - user accepts each version once

### ✅ B) Models Created
1. **`TermsVersion` Model** (`app/Models/TermsVersion.php`)
   - `active()` static method to get active version
   - `getHtmlContent()` - retrieves HTML from file or database
   - `isAcceptedBy(User $user)` - checks if user accepted this version
   - `activate()` - activates this version (deactivates all others)

2. **`TermsAcceptance` Model** (`app/Models/TermsAcceptance.php`)
   - Relationships: `user()`, `termsVersion()`

3. **User Model Updates** (`app/Models/User.php`)
   - `termsAcceptances()` relationship
   - `hasAcceptedActiveTerms()` method
   - `latestTermsAcceptance()` method

### ✅ C) Admin Management UI
**File**: `resources/views/pages/admin/settings/terms.blade.php`

**Features**:
- View all terms versions in a table
- Create new version (upload HTML file OR paste HTML content)
- Edit existing versions
- Activate a version (deactivates all others)
- Preview terms HTML
- Safety: Cannot deactivate last active version without selecting new one

**Routes**:
- `admin.settings.terms` - Main management page
- `admin.settings.terms.preview` - Preview route

### ✅ D) Member Acceptance Flow
**File**: `resources/views/pages/member/terms/accept.blade.php`

**Features**:
- Displays active terms version HTML in scrollable container
- Required checkbox: "I have read and agree to the NRAPA Membership Terms & Conditions"
- "Accept & Continue" button (disabled until checkbox checked)
- Stores: `accepted_at`, `accepted_ip`, `accepted_user_agent`
- Redirects to dashboard after acceptance

**Route**: `terms.accept` (accessible without terms middleware)

### ✅ E) Hard Gating Logic
1. **Middleware**: `EnsureTermsAccepted` (`app/Http/Middleware/EnsureTermsAccepted.php`)
   - Allows admins/owners/developers to bypass
   - Allows access to `terms.accept` route
   - Redirects to terms acceptance page if not accepted
   - Registered in `bootstrap/app.php` as `terms.accepted`

2. **Applied to Routes**:
   - All member routes requiring active membership (certificates, endorsements, activities, etc.)
   - Dashboard (except terms acceptance page itself)

### ✅ F) Membership Status Logic
**Updated**: `app/Services/MembershipStandingService.php`

- `isInGoodStanding()` now checks terms acceptance
- `getStandingReason()` returns "Pending Terms Acceptance" if terms not accepted
- Membership cannot be "Member in Good Standing" without terms acceptance

**Hook Added**: `Membership` model sends terms acceptance email when status changes to 'active'

### ✅ G) Document Generator Updates
**Updated**: `app/Services/CertificateIssueService.php`

- `issuePaidUpCertificate()` - checks terms acceptance before issuing
- `issueWelcomeLetter()` - checks terms acceptance before issuing
- `issueDedicatedHunterCertificate()` - already checks `isInGoodStanding()` (includes terms)
- `issueDedicatedSportCertificate()` - already checks `isInGoodStanding()` (includes terms)

**Updated**: `app/Models/EndorsementRequest.php`

- `submit()` method checks terms acceptance
- `getSubmissionErrors()` includes terms acceptance error message

**Updated**: `resources/views/pages/member/endorsements/create.blade.php`

- `submitRequest()` checks terms acceptance before submission
- Redirects to terms acceptance page if not accepted

### ✅ H) Email Flows
1. **Terms Acceptance Required Email** (`app/Mail/TermsAcceptanceRequiredMail.php`)
   - Subject: "Action Required: Accept NRAPA Terms & Conditions"
   - Includes link to acceptance page
   - Explains what happens after acceptance

2. **Email Template** (`resources/views/emails/terms-acceptance-required.blade.php`)
   - Professional HTML email template
   - Clear call-to-action button
   - Explains consequences of not accepting

3. **TermsHelper** (`app/Helpers/TermsHelper.php`)
   - `sendTermsAcceptanceEmail(User $user)` - sends email
   - `checkAndNotify(User $user)` - checks and sends if needed

4. **Welcome Letter Updated** (`resources/views/documents/letters/welcome.blade.php`)
   - Added "Terms & Conditions" section
   - Mentions version number
   - Notes that terms should be retained

5. **Membership Model Hook**
   - Sends terms acceptance email when membership status changes to 'active'

## Key Features

### Versioning System
- Terms are versioned (e.g., "2026-01")
- Only one active version at a time
- Users must accept the active version
- Historical acceptances are preserved

### Audit Trail
- Each acceptance records:
  - Exact version accepted
  - Timestamp (`accepted_at`)
  - IP address (`accepted_ip`)
  - User agent (`accepted_user_agent`)

### Hard Gating
- **Cannot become "Member in Good Standing"** without acceptance
- **Cannot issue certificates/letters** without acceptance
- **Cannot submit endorsement requests** without acceptance
- **Cannot access member portal features** without acceptance

### Admin Bypass
- Admins, owners, and developers can bypass terms acceptance
- Allows them to manage the system and test features

## Files Created

### Migrations
- `database/migrations/2026_01_29_100007_create_terms_versions_table.php`
- `database/migrations/2026_01_29_100008_create_terms_acceptances_table.php`

### Models
- `app/Models/TermsVersion.php`
- `app/Models/TermsAcceptance.php`

### Middleware
- `app/Http/Middleware/EnsureTermsAccepted.php`

### Helpers
- `app/Helpers/TermsHelper.php`

### Mail
- `app/Mail/TermsAcceptanceRequiredMail.php`

### Views
- `resources/views/pages/member/terms/accept.blade.php`
- `resources/views/pages/admin/settings/terms.blade.php`
- `resources/views/pages/admin/settings/terms-preview.blade.php`
- `resources/views/emails/terms-acceptance-required.blade.php`
- `resources/views/documents/terms/nrapa-terms.blade.php` (template)

### Seeders
- `database/seeders/TermsVersionSeeder.php`

## Files Modified

- `app/Models/User.php` - Added terms acceptance methods
- `app/Models/Membership.php` - Added hook to send email on activation
- `app/Models/EndorsementRequest.php` - Added terms check to submission
- `app/Services/MembershipStandingService.php` - Added terms check to good standing logic
- `app/Services/CertificateIssueService.php` - Added terms checks to certificate issuance
- `app/Services/FakeDocumentRenderer.php` - Updated welcome letter rendering
- `bootstrap/app.php` - Registered `terms.accepted` middleware
- `routes/web.php` - Added terms routes and applied middleware
- `resources/views/pages/member/endorsements/create.blade.php` - Added terms check
- `resources/views/documents/letters/welcome.blade.php` - Added terms reference

## Next Steps

1. **Run Migrations**: `php artisan migrate`
2. **Seed Initial Terms**: `php artisan db:seed --class=TermsVersionSeeder`
   - OR manually create first version via admin UI
3. **Upload Terms HTML**: Use admin UI to upload the provided HTML file or paste content
4. **Activate Version**: Mark the first version as active
5. **Test Flow**: 
   - Create a test member
   - Verify they're blocked from accessing features
   - Accept terms
   - Verify access is granted

## Important Notes

- **No active terms = no blocking**: If no active terms version exists, users can access features (admin should set one)
- **Email sending**: Terms acceptance emails are sent automatically when:
  - Membership status changes to 'active'
  - User registration (can be added via observer if needed)
- **Welcome letters**: Only issued AFTER terms acceptance (enforced in `issueWelcomeLetter()`)
- **Backward compatibility**: Existing members without acceptance will be blocked until they accept

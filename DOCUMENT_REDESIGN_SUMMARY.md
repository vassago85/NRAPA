# NRAPA Document Redesign - Implementation Summary

## Overview
Replaced all existing certificate/letter HTML templates with new "clean official" styling set, ensuring formatting consistency across ALL outputs.

## Completed Tasks

### ✅ A) Shared Layout Created
- **File**: `resources/views/documents/layouts/nrapa-official.blade.php`
- Single shared CSS system with consistent tokens (--blue, --orange, --text, etc.)
- A4 print-ready styling
- Consistent header, FAR block, title bar, card styles, and footer
- All documents extend this layout

### ✅ B) Document Templates Created/Updated
All 4 document templates created with identical styling:

1. **Proof of Membership in Good Standing** (A4 certificate)
   - **File**: `resources/views/documents/certificates/good-standing.blade.php`
   - Replaces "Paid-Up Membership" certificate
   - Uses "Member in Good Standing" wording

2. **Dedicated Status Certificate** (A4 certificate)
   - **File**: `resources/views/documents/certificates/dedicated-status.blade.php`
   - Supports both Dedicated Hunter and Dedicated Sport Shooter
   - Includes Commissioner of Oaths scan placeholder

3. **Welcome Letter** (A4 letter)
   - **File**: `resources/views/documents/letters/welcome.blade.php`
   - Includes QR verification section
   - Uses "Member in Good Standing" wording

4. **Endorsement Letter** (A4 letter)
   - **File**: `resources/views/documents/letters/endorsement.blade.php`
   - Includes QR verification
   - Uses "Member in Good Standing" wording
   - Supports Commissioner of Oaths scan (optional)

### ✅ C) Database Migration
- **File**: `database/migrations/2026_01_29_100006_add_certificate_assets_to_certificates_table.php`
- Added fields to `certificates` table:
  - `signatory_name` (nullable)
  - `signatory_title` (nullable)
  - `signatory_signature_path` (nullable)
  - `commissioner_oaths_scan_path` (nullable)

### ✅ D) Model Updates
- **Certificate Model**: Added accessor methods:
  - `getSignatureImageUrl()`
  - `getCommissionerScanUrl()`
- Updated `$fillable` array to include new asset fields

### ✅ E) Helper Classes Created
1. **DocumentDataHelper** (`app/Helpers/DocumentDataHelper.php`)
   - `getFarNumbers()` - Gets FAR accreditation numbers from system settings
   - `getLogoUrl()` - Gets NRAPA logo URL
   - `getQrCodeUrl()` - Generates QR code URLs for certificates
   - `getEndorsementQrCodeUrl()` - Generates QR code URLs for endorsements
   - `getSignatureImageHtml()` - Safe server-generated signature HTML (white placeholder if missing)
   - `getCommissionerScanHtml()` - Safe server-generated commissioner scan HTML (white placeholder if missing)
   - `getSignatoryInfo()` - Gets signatory name/title with fallbacks
   - `getContactInfo()` - Gets NRAPA contact information

### ✅ F) Document Rendering Updated
- **FakeDocumentRenderer**: Updated to use new templates with automatic mapping
- **CertificateIssueService**: Updated to use new template names
- **Routes**: All certificate preview routes updated with template mapping
- Template mapping handles backward compatibility with old template names

### ✅ G) QR Code Generation
- QR codes point to: `/verify/{qr_code}`
- QR codes appear on all documents:
  - Good Standing certificate
  - Dedicated Status certificate
  - Welcome letter
  - Endorsement letter
- Uses `QrCodeHelper::generateUrl()` for consistent generation

### ✅ H) Wording Updates
- Replaced "Paid-Up Membership" → "Membership in Good Standing"
- Replaced "paid-up member" → "member in good standing"
- Updated all templates, services, and UI text
- Verification page will show "Member in Good Standing" status

## Pending Tasks

### ⏳ I) Admin UI for Certificate Assets
**Status**: Not yet implemented

**Required**:
- Admin page: `Documents > Assets` or `Certificates > Assets`
- For each certificate/letter:
  - Upload commissioner scan (jpg/png/pdf, max 10MB)
  - Upload signature (PNG only, max 2MB, transparent recommended)
  - Edit signatory name/title
  - Show preview thumbnails
  - Save changes

**Storage**:
- Files stored on `public` disk (local) or `r2_public` (production)
- Generate URLs via `StorageHelper::getUrl()`

**Validation**:
- Commissioner scan: jpg/png/pdf, max 10MB
- Signature: png only, max 2MB

## Key Features

### White Placeholder Backgrounds
- Signature placeholder: **MUST be white** (#fff)
- Commissioner scan placeholder: **MUST be white** (#fff)
- Both use `.placeholder-white` class with `background: #fff !important`

### FAR Accreditation Numbers
- Prominently displayed near top of all documents
- Retrieved from system settings:
  - `far_sport_number`
  - `far_hunting_number`

### Server-Generated Image Tags
- **DO NOT** accept raw HTML from users
- All `<img>` tags generated server-side via helper methods
- Safe escaping with `e()` function

### Template Mapping
- Backward compatible with old template names
- Automatic mapping in routes and renderer
- Old slugs still work (e.g., `paid-up-certificate` → `good-standing-certificate`)

## Files Modified

### New Files Created
- `resources/views/documents/layouts/nrapa-official.blade.php`
- `resources/views/documents/certificates/good-standing.blade.php`
- `resources/views/documents/certificates/dedicated-status.blade.php`
- `resources/views/documents/letters/welcome.blade.php`
- `resources/views/documents/letters/endorsement.blade.php`
- `app/Helpers/DocumentDataHelper.php`
- `database/migrations/2026_01_29_100006_add_certificate_assets_to_certificates_table.php`

### Files Modified
- `app/Models/Certificate.php`
- `app/Services/FakeDocumentRenderer.php`
- `app/Services/CertificateIssueService.php`
- `app/Services/VerificationService.php`
- `routes/web.php` (all certificate preview routes)
- `resources/views/pages/admin/members/show.blade.php`

## Next Steps

1. **Run Migration**: `php artisan migrate`
2. **Set System Settings**: Configure FAR numbers and contact info in system settings
3. **Test Documents**: View all certificate types to verify styling
4. **Implement Admin UI**: Create Livewire component for uploading/managing certificate assets
5. **Upload Assets**: Add signature and commissioner scans via admin dashboard

## Notes

- All documents use the same CSS token system for consistency
- Print styles ensure clean A4 output
- QR codes are generated on-the-fly (can be cached later if needed)
- White placeholders ensure professional appearance even when assets are missing
- Template mapping ensures backward compatibility during transition

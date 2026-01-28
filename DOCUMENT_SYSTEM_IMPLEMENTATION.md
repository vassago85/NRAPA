# NRAPA Document & Certificate System Implementation

## Overview
This document describes the implementation of the official NRAPA certificate and document generation system, integrated with the existing platform structure.

## System Architecture

### Document Types (Generated Outputs Only)
1. **Dedicated Hunter Certificate** - Official certificate confirming Dedicated Hunter Status
2. **Dedicated Sport Shooter Certificate** - Official certificate confirming Dedicated Sport Shooter Status  
3. **Proof of Paid-Up Membership Certificate** - Confirms member is in good standing
4. **Membership Card** - Identity/convenience artefact (PDF now, wallet-ready later)
5. **Welcome Letter** - Informational onboarding document

### Data Collection Forms
- **Individual Firearm Technical Detail Form** - Structured data input for endorsement requests (stored in `endorsement_firearms` table)

## Database Schema

### New Tables
- `payments` - Tracks membership payments
- `member_status_history` - Audit trail for membership status changes
- `comments` - Polymorphic comments for endorsement requests and other entities
- `audit_logs` - System-wide audit trail

### Extended Tables
- `memberships` - Added `good_standing` and `last_payment_at` fields
- `certificates` - Added `checksum` field for tamper detection
- `certificate_types` - Extended with new document types via seeder

## Services

### MembershipStandingService
Determines if a member is in good standing based on:
- Active membership status
- Not expired (or lifetime)
- Not suspended/revoked
- `good_standing` flag is true
- Recent payment if required

### CertificateIssueService
Handles issuance of all certificate types:
- `issueDedicatedHunterCertificate()`
- `issueDedicatedSportCertificate()`
- `issuePaidUpCertificate()`
- `issueMembershipCard()`
- `issueWelcomeLetter()`

Each method:
1. Validates eligibility (dedicated status, good standing, etc.)
2. Creates Certificate record
3. Generates document via DocumentRenderer
4. Calculates checksum
5. Updates certificate with file path

### VerificationService
Public verification via QR code:
- Returns public-safe information (masked member details)
- Validates certificate status
- Checks membership good standing for relevant certificates
- Returns clear valid/invalid status with reason

### DocumentRenderer Interface
Abstract interface for document generation:
- `renderCertificate()` - Renders certificate to PDF
- `renderWelcomeLetter()` - Renders welcome letter to PDF

**Current Implementation:** `FakeDocumentRenderer`
- Generates HTML files as placeholders
- TODO: Replace with actual PDF generation (DomPDF, Snappy, etc.)

## Models

### New Models
- `Payment` - Membership payment tracking
- `MemberStatusHistory` - Membership status change audit
- `Comment` - Polymorphic comments system
- `AuditLog` - System audit trail

### Extended Models
- `User` - Added `payments()` and `statusHistory()` relationships
- `EndorsementRequest` - Added `comments()` relationship
- `Certificate` - Added `checksum` field support

## UI Components

### Admin Portal
**Member Show Page** (`pages/admin/members/show.blade.php`):
- Document Issuance section with buttons for each document type
- Eligibility checks (dedicated status, good standing)
- Already issued indicators
- Issues certificates via `CertificateIssueService`
- Logs actions to `AuditLog`

### Public Verification
**Verification Page** (`pages/verify.blade.php`):
- Public route: `/verify/{qr_code}`
- Shows document type, masked member info, validity status
- Professional NRAPA branding
- No sensitive data exposure

## Document Templates

All templates extend `documents/base.blade.php`:
- A4 print-ready layout
- NRAPA blue/orange color scheme
- Professional typography
- Watermark support
- QR code verification link in footer

Templates:
- `documents/dedicated-hunter.blade.php`
- `documents/dedicated-sport.blade.php`
- `documents/paid-up.blade.php`
- `documents/membership-card.blade.php`
- `documents/welcome-letter.blade.php`

## Routes

### Public
- `GET /verify/{qr_code}` - Certificate verification (public)

### Admin (existing routes extended)
- Member management routes already support document issuance
- Certificate viewing routes already support all certificate types

## Seeders

### Updated
- `MembershipConfigurationSeeder` - Added new certificate types:
  - `dedicated-hunter-certificate`
  - `dedicated-sport-certificate`
  - `paid-up-certificate`
  - `membership-card`
  - `welcome-letter`

## Security & Privacy

- Public verification masks sensitive data (ID numbers, full names)
- QR codes are unguessable (UUID + random)
- Document checksums detect tampering
- Audit logs track all certificate issuances
- Policies enforce admin-only issuance

## TODO Items

1. **PDF Generation**: Replace `FakeDocumentRenderer` with actual PDF engine
   - Recommended: DomPDF or Snappy (wkhtmltopdf)
   - Update `FakeDocumentRenderer` to generate actual PDFs

2. **Document Design**: Match exact layout from provided PDFs
   - Review "NRAPA Dedicated Hunter_LIFE.pdf" for exact styling
   - Review "NRAPA Dedicated Shooter_LIFE.pdf" for exact styling
   - Review "NRAPA Proof of Paid Up Membership Certificate_LIFE.pdf"
   - Review "NRAPA Membership Card_LIFE.pdf"
   - Review "PS Charsley_NRAPA Welcome Letter.pdf"

3. **Payment Integration**: Connect payment tracking to actual payment gateway
   - Update `Payment` model when payments are received
   - Auto-update `good_standing` based on payments

4. **Endorsement Firearm Form**: Implement printable PDF generation
   - Create template for "NRAPA Endorsement Application.docx" format
   - Generate from `endorsement_firearms` data

5. **Membership Card Wallet Support**: Future enhancement for digital wallet cards

## How to Run

1. **Run Migrations**:
   ```bash
   php artisan migrate
   ```

2. **Run Seeders** (includes new certificate types):
   ```bash
   php artisan db:seed --class=MembershipConfigurationSeeder
   ```

3. **Test Document Issuance**:
   - Login as admin
   - Navigate to a member's profile
   - Use "Document Issuance" section to issue certificates
   - Verify documents appear in member's certificate list

4. **Test Public Verification**:
   - Get a certificate's QR code
   - Visit `/verify/{qr_code}`
   - Verify public-safe information is displayed

## Integration Notes

- All new functionality integrates with existing:
  - `Certificate` model (extended, not replaced)
  - `CertificateType` model (extended via seeder)
  - `User` and `Membership` models (relationships added)
  - `EndorsementRequest` model (comments relationship added)
  - Admin member management UI (document issuance added)
  - Public verification route (updated to use VerificationService)

- No breaking changes to existing functionality
- All existing certificates continue to work
- New document types are additive only

# NRAPA Document System - Quick Start Guide

## 🚀 Getting Started

### 1. Run Migrations
```bash
php artisan migrate
```

This will create:
- `payments` table
- `member_status_history` table
- `comments` table
- `audit_logs` table
- Add `good_standing` and `last_payment_at` to `memberships`
- Add `checksum` to `certificates`

### 2. Seed Certificate Types
```bash
php artisan db:seed --class=MembershipConfigurationSeeder
```

This adds 5 new certificate types:
- Dedicated Hunter Certificate
- Dedicated Sport Shooter Certificate
- Proof of Paid-Up Membership Certificate
- Membership Card
- Welcome Letter

### 3. Test Document Issuance

**As Admin:**
1. Login as admin
2. Navigate to Admin → Members
3. Open a member's profile
4. Scroll to "Document Issuance" section
5. Click "Issue Certificate" for any document type
6. Verify certificate appears in "Issued Certificates & Documents" section

**Requirements:**
- Dedicated Hunter/Sport certificates require approved dedicated status
- All certificates require active membership
- Paid-Up and Membership Card require good standing

### 4. Test Public Verification

1. Get a certificate's QR code (from certificate details page)
2. Visit: `https://your-domain.com/verify/{qr_code}`
3. Verify public-safe information is displayed
4. Test with invalid QR code to see error handling

## 📝 Key Features

### Document Types
- **Generated Only** - No upload functionality
- **QR Code Verification** - Public verification route
- **Checksum Protection** - Tamper detection
- **Good Standing Validation** - Automatic validation

### Admin Features
- Issue certificates from member profile
- Eligibility checks before issuance
- Audit logging of all issuances
- View all issued certificates

### Member Features
- View all their certificates
- Download certificate files
- Access verification QR codes

## 🔧 Configuration

### Service Provider
The `DocumentRenderer` interface is registered in `AppServiceProvider`:
- Current: `FakeDocumentRenderer` (generates HTML)
- TODO: Replace with actual PDF renderer (DomPDF/Snappy)

### Storage
Documents are stored in:
- `storage/app/documents/` (local)
- Can be configured for S3/R2 in `config/filesystems.php`

## ⚠️ Important Notes

1. **PDF Generation**: Currently generates HTML files. Replace `FakeDocumentRenderer` with actual PDF engine for production.

2. **Document Design**: Templates are basic. Review provided PDFs and match exact layouts:
   - Dedicated Hunter Certificate
   - Dedicated Sport Shooter Certificate
   - Paid-Up Membership Certificate
   - Membership Card
   - Welcome Letter

3. **Payment Integration**: `Payment` model is ready but not connected to payment gateway. Update when payment system is integrated.

4. **Good Standing**: Currently manual. Auto-update when payment integration is complete.

## 🐛 Troubleshooting

### Certificate Not Issuing
- Check member has active membership
- Check dedicated status is approved (for dedicated certificates)
- Check `good_standing` flag is true
- Check error messages in session flash

### Verification Not Working
- Check QR code is correct
- Check certificate exists in database
- Check certificate is not revoked
- Check member is in good standing (for relevant certificates)

### Document Not Generating
- Check storage permissions
- Check `documents/` directory exists in storage
- Check template files exist in `resources/views/documents/`
- Check logs for rendering errors

## 📚 Documentation

- Full implementation details: `DOCUMENT_SYSTEM_IMPLEMENTATION.md`
- Implementation summary: `IMPLEMENTATION_SUMMARY.md`

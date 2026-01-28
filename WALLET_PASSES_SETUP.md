# Digital Wallet Passes Setup Guide

This guide explains how to set up Apple Wallet and Google Wallet passes for NRAPA membership cards.

## Overview

The platform supports generating digital wallet passes for membership cards:
- **Apple Wallet** (.pkpass files) - for iOS devices
- **Google Wallet** (JWT-based passes) - for Android devices

## Current Status

The infrastructure is in place, but wallet pass generation requires:
1. Installing a Laravel package (recommended: `spatie/laravel-mobile-pass` or `chiiya/laravel-passes`)
2. Configuring Apple Developer account (for Apple Wallet)
3. Configuring Google Wallet API (for Google Wallet)

## Installation Steps

### 1. Install Package

Choose one of these packages:

**Option A: Spatie Laravel Mobile Pass** (Recommended)
```bash
composer require spatie/laravel-mobile-pass
php artisan vendor:publish --tag="mobile-pass-config"
```

**Option B: Laravel Passes**
```bash
composer require chiiya/laravel-passes
php artisan vendor:publish --tag="passes-config"
```

### 2. Apple Wallet Setup

#### Requirements:
- Apple Developer account ($99/year)
- Pass Type ID certificate
- WWDR (Apple Worldwide Developer Relations) certificate

#### Steps:

1. **Create Pass Type ID:**
   - Go to [Apple Developer Portal](https://developer.apple.com/account/resources/identifiers/list/passTypeId)
   - Create a new Pass Type ID (e.g., `pass.com.nrapa.membership`)
   - Download the certificate

2. **Download WWDR Certificate:**
   - Download from [Apple Developer](https://www.apple.com/certificateauthority/)
   - Save as `wwdr.pem`

3. **Configure Environment:**
   ```env
   WALLET_APPLE_ENABLED=true
   WALLET_APPLE_PASS_TYPE_ID=pass.com.nrapa.membership
   WALLET_APPLE_TEAM_ID=YOUR_TEAM_ID
   WALLET_APPLE_CERTIFICATE_PATH=storage/wallet/apple/certificate.pem
   WALLET_APPLE_WWDR_PATH=storage/wallet/apple/wwdr.pem
   WALLET_APPLE_CERTIFICATE_PASSWORD=your_certificate_password
   ```

4. **Store Certificates:**
   - Place certificate files in `storage/wallet/apple/`
   - Ensure proper file permissions

### 3. Google Wallet Setup

#### Requirements:
- Google Cloud Platform account
- Google Wallet API enabled
- Service account with Wallet API access

#### Steps:

1. **Create Google Cloud Project:**
   - Go to [Google Cloud Console](https://console.cloud.google.com/)
   - Create a new project or select existing
   - Enable "Google Wallet API"

2. **Create Service Account:**
   - Go to IAM & Admin > Service Accounts
   - Create a new service account
   - Grant "Wallet Objects Issuer" role
   - Download JSON key file

3. **Create Pass Class:**
   - Use Google Wallet API to create a Loyalty Class
   - Note the Class ID

4. **Configure Environment:**
   ```env
   WALLET_GOOGLE_ENABLED=true
   WALLET_GOOGLE_ISSUER_ID=your_issuer_id
   WALLET_GOOGLE_SERVICE_ACCOUNT_PATH=storage/wallet/google/service-account.json
   WALLET_GOOGLE_APPLICATION_NAME=NRAPA Membership
   ```

5. **Store Service Account:**
   - Place JSON key file in `storage/wallet/google/`
   - Ensure proper file permissions

## Implementation

Once packages are installed, update `app/Services/WalletPassService.php`:

### Apple Wallet Example (using Spatie):
```php
use Spatie\LaravelMobilePass\Pass;

public function generateAppleWalletPass(Certificate $certificate): ?string
{
    $pass = Pass::create()
        ->setPassTypeIdentifier(config('wallet.apple.pass_type_id'))
        ->setTeamIdentifier(config('wallet.apple.team_id'))
        ->setSerialNumber($certificate->certificate_number ?? $certificate->uuid)
        ->setDescription('NRAPA Membership Card')
        ->setOrganizationName('National Rifle & Pistol Association')
        ->setLogoText('NRAPA')
        ->setForegroundColor('rgb(15, 76, 129)')
        ->setBackgroundColor('rgb(255, 255, 255)')
        ->addPrimaryField('name', $certificate->user->name)
        ->addSecondaryField('membership', $certificate->membership->membership_number ?? 'N/A')
        ->addAuxiliaryField('type', $certificate->membership->type->name ?? 'Member')
        ->addBarcode($certificate->qr_code, 'PKBarcodeFormatQR', 'iso-8859-1');
    
    if ($certificate->membership->expires_at) {
        $pass->setExpirationDate($certificate->membership->expires_at);
    }
    
    $filePath = "wallet-passes/apple/{$certificate->uuid}.pkpass";
    Storage::disk('local')->put($filePath, $pass->generate());
    
    return $filePath;
}
```

### Google Wallet Example:
```php
use Google\Client;
use Google\Service\Walletobjects;

public function generateGoogleWalletPass(Certificate $certificate): ?string
{
    $client = new Client();
    $client->setAuthConfig(config('wallet.google.service_account_path'));
    $client->addScope(Walletobjects::WALLET_OBJECTS_ISSUER);
    
    $service = new Walletobjects($client);
    
    $loyaltyObject = new \Google\Service\Walletobjects\LoyaltyObject([
        'id' => $certificate->certificate_number ?? $certificate->uuid,
        'classId' => config('wallet.google.class_id'),
        'state' => 'ACTIVE',
        'accountName' => $certificate->user->name,
        'accountId' => $certificate->membership->membership_number ?? 'N/A',
        'barcode' => [
            'type' => 'QR_CODE',
            'value' => $certificate->qr_code,
        ],
    ]);
    
    if ($certificate->membership->expires_at) {
        $loyaltyObject->setValidTimeInterval([
            'start' => ['date' => $certificate->valid_from->format('Y-m-d')],
            'end' => ['date' => $certificate->membership->expires_at->format('Y-m-d')],
        ]);
    }
    
    $object = $service->loyaltyobject->insert($loyaltyObject);
    
    return "https://pay.google.com/gp/v/save/{$object->getId()}";
}
```

## UI Integration

The wallet pass buttons are already integrated in:
- `resources/views/pages/member/certificates/show.blade.php`
- Only shown for membership cards
- Only shown when wallet is enabled in config

## Testing

1. **Test Apple Wallet:**
   - Generate a membership card certificate
   - Click "Apple Wallet" button
   - Download .pkpass file
   - Open on iOS device or simulator

2. **Test Google Wallet:**
   - Generate a membership card certificate
   - Click "Google Wallet" button
   - Should redirect to Google Wallet save URL
   - Add to Google Wallet on Android device

## Troubleshooting

- **503 Error:** Wallet is not configured. Check environment variables.
- **404 Error:** Certificate is not a membership card.
- **Certificate Errors:** Verify certificate paths and permissions.
- **API Errors:** Check service account permissions and API enablement.

## Security Notes

- Never commit certificate files or service account keys to git
- Store certificates in secure storage (not in public directories)
- Use environment variables for all sensitive configuration
- Rotate certificates and keys regularly

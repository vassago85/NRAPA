# QR Code Testing & Deployment Guide

## QR Code Configuration

QR codes are automatically generated for:
- **Certificates**: Points to `/verify/{qr_code}`
- **Endorsement Letters**: Points to `/verify/endorsement/{reference}`

### Environment Settings Required

The only environment variable needed for QR codes is `APP_URL`, which should be set to your domain:

**Test Environment:**
```env
APP_URL=https://nrapa.charsley.co.za
```

**Production (future):**
```env
APP_URL=https://members.nrapa.co.za
```

**Important**: QR codes encode the full verification URL using `APP_URL`. If this is incorrect, QR codes will point to the wrong domain and won't work.

### How QR Codes Work

1. **Generation**: Uses free qrserver.com API (no API key needed)
2. **URL Encoding**: Verification URLs are built using Laravel's `route()` and `url()` helpers
3. **Verification**: Public routes at `/verify/{qr_code}` and `/verify/endorsement/{reference}`

## Deployment Steps

### 1. Commit and Push Changes

```powershell
cd C:\laragon\www\NRAPA
git add -A
git commit -m "Remove admin fees from pricing and billing"
git push
```

### 2. Deploy to Server

SSH into your server and run:

```bash
cd /opt/nrapa
git pull
docker build -t nrapa-app:latest .
docker compose down
docker compose up -d
```

### 3. Run Migration

```bash
docker exec nrapa-app php artisan migrate --force
```

### 4. Verify Environment Settings

Check that `APP_URL` is set correctly in your Docker environment:

```bash
docker exec nrapa-app php artisan tinker
>>> config('app.url')
```

Should return: `https://nrapa.charsley.co.za` (test environment)

### 5. Clear Caches

```bash
docker exec nrapa-app php artisan config:clear
docker exec nrapa-app php artisan route:clear
docker exec nrapa-app php artisan view:clear
docker exec nrapa-app php artisan cache:clear
```

### 6. Test QR Codes

1. **Generate a test certificate** (if you have admin access)
2. **View the certificate** and check the QR code
3. **Scan the QR code** with your phone
4. **Verify it points to**: `https://nrapa.charsley.co.za/verify/{qr_code}`
5. **Test the verification page** loads correctly

## Quick Deployment Script

Create a file `deploy.sh` on your server:

```bash
#!/bin/bash
cd /opt/nrapa
git pull
docker build -t nrapa-app:latest .
docker compose down
docker compose up -d
docker exec nrapa-app php artisan migrate --force
docker exec nrapa-app php artisan config:clear
docker exec nrapa-app php artisan route:clear
docker exec nrapa-app php artisan view:clear
docker exec nrapa-app php artisan cache:clear
echo "Deployment complete!"
```

Make it executable:
```bash
chmod +x deploy.sh
```

Then run:
```bash
./deploy.sh
```

## Troubleshooting QR Codes

### QR Code Points to Wrong Domain

**Problem**: QR codes encode `http://localhost` or wrong domain

**Solution**: 
1. Check `APP_URL` in environment: `docker exec nrapa-app php artisan tinker` → `config('app.url')`
2. Update `APP_URL` in Docker environment variables (Portainer Stack)
3. Restart container: `docker compose restart nrapa-app`
4. Clear config cache: `docker exec nrapa-app php artisan config:clear`

### QR Code Not Generating

**Problem**: QR code image doesn't appear

**Solution**:
1. Check internet connectivity (qrserver.com API needs internet access)
2. Check browser console for errors
3. Verify the verification URL is valid: `route('certificates.verify', ['qr_code' => 'test'])`

### Verification Page Not Loading

**Problem**: `/verify/{qr_code}` returns 404

**Solution**:
1. Check routes: `docker exec nrapa-app php artisan route:list | grep verify`
2. Clear route cache: `docker exec nrapa-app php artisan route:clear`
3. Verify the route exists in `routes/web.php`

## Testing Checklist

- [ ] `APP_URL` is set correctly in production environment
- [ ] Migration has been run (removes admin_fee column)
- [ ] Config cache cleared
- [ ] Route cache cleared
- [ ] View cache cleared
- [ ] Test certificate QR code scans correctly
- [ ] Test endorsement QR code scans correctly
- [ ] Verification pages load correctly
- [ ] Prices display without admin fees
- [ ] Admin form no longer shows admin fee field

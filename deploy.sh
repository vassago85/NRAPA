#!/bin/bash
# NRAPA Deployment Script
# Usage: ./deploy.sh

set -e

echo "========================================"
echo "NRAPA Deployment"
echo "========================================"
echo ""

cd /opt/nrapa

echo "Step 1: Pulling latest changes..."
git pull

echo ""
echo "Step 2: Building Docker image..."
docker build -t nrapa-app:latest .

echo ""
echo "Step 3: Stopping containers..."
docker compose down

echo ""
echo "Step 4: Starting containers..."
docker compose up -d

echo ""
echo "Step 5: Waiting for services to be ready..."
sleep 10

echo ""
echo "Step 6: Running migrations..."
docker exec nrapa-app php artisan migrate --force

echo ""
echo "Step 7: Clearing caches..."
docker exec nrapa-app php artisan config:clear
docker exec nrapa-app php artisan route:clear
docker exec nrapa-app php artisan view:clear
docker exec nrapa-app php artisan cache:clear

echo ""
echo "Step 8: Verifying APP_URL configuration..."
APP_URL=$(docker exec nrapa-app php artisan tinker --execute="echo config('app.url');")
echo "APP_URL is set to: $APP_URL"
echo ""
if [[ "$APP_URL" != "https://nrapa.charsley.co.za"* ]] && [[ "$APP_URL" != "https://members.nrapa.co.za"* ]] && [[ "$APP_URL" != "http://localhost"* ]]; then
    echo "⚠️  WARNING: APP_URL may not be set correctly for QR codes!"
    echo "   Expected: https://nrapa.charsley.co.za (test) or https://members.nrapa.co.za (production)"
    echo "   Current:  $APP_URL"
    echo "   Update APP_URL in Docker environment variables if needed."
fi

echo ""
echo "========================================"
echo "Deployment complete!"
echo "========================================"
echo ""
echo "Recent logs:"
docker logs nrapa-app --tail 20

echo ""
echo "Next steps:"
echo "1. Test QR codes by generating a certificate"
echo "2. Verify prices display without admin fees"
echo "3. Check verification pages: https://nrapa.charsley.co.za/verify/{qr_code}"

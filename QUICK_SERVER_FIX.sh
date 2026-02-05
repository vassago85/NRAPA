#!/bin/bash
# Quick fix for migration error on server
# Run this on the server to fix the migration issue

cd /opt/nrapa

echo "Step 1: Pulling latest code..."
git pull

echo ""
echo "Step 2: Checking if migration file is updated..."
if grep -q "REFERENCED_TABLE_NAME = 'calibres'" database/migrations/2026_01_30_500000_update_calibre_requests_to_firearm_calibres_framework.php; then
    echo "✓ Migration file is already fixed"
else
    echo "⚠ Migration file needs to be fixed manually"
    echo "Please commit and push the fix from local first, then run:"
    echo "  git pull"
    exit 1
fi

echo ""
echo "Step 3: Rebuilding Docker image..."
docker build -t nrapa-app:latest .

echo ""
echo "Step 4: Restarting containers..."
docker compose down
docker compose up -d

echo ""
echo "Step 5: Waiting for services..."
sleep 10

echo ""
echo "Step 6: Running migrations..."
docker exec nrapa-app php artisan migrate --force

echo ""
echo "Step 7: Clearing caches..."
docker exec nrapa-app php artisan config:clear
docker exec nrapa-app php artisan route:clear
docker exec nrapa-app php artisan view:clear

echo ""
echo "✓ Done! Check logs if there are any errors:"
echo "  docker logs nrapa-app --tail 50"

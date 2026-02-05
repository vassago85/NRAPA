@echo off
echo 👤 Seeding admin users...
echo.

cd c:\laragon\www\NRAPA

echo 📦 Running DatabaseSeeder...
php artisan db:seed --class=DatabaseSeeder

echo.
echo ✅ Admin users seeded!
echo.
echo 📋 Admin Credentials:
echo ──────────────────────────────────────────────────────────────
echo Developer: paul@charsley.co.za / PaulCharsley2026!
echo Admin: admin@nrapa.co.za / NrapaAdmin2026!
echo ──────────────────────────────────────────────────────────────
echo.
echo 💡 You can now login with these credentials!
pause

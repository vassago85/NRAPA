# NRAPA Platform Rebuild Commands

## 🚀 Complete Rebuild & Deploy

### On Server (SSH into your server):

```bash
# Navigate to project directory
cd /opt/nrapa

# Pull latest changes from git
git pull

# Rebuild Docker image
docker build -t nrapa-app:latest .

# Stop containers
docker compose down

# Start containers (migrations run automatically)
docker compose up -d

# Check logs to verify
docker logs nrapa-app --tail 50
```

### One-Liner (Quick Rebuild):

```bash
cd /opt/nrapa && git pull && docker build -t nrapa-app:latest . && docker compose down && docker compose up -d && docker logs nrapa-app --tail 30
```

## 📋 After Rebuild - Run Migrations & Seeders

### Run New Migrations:
```bash
docker exec nrapa-app php artisan migrate
```

### Seed Certificate Types (if needed):
```bash
docker exec nrapa-app php artisan db:seed --class=MembershipConfigurationSeeder
```

### Verify Database:
```bash
# Check new tables exist
docker exec nrapa-db mysql -unrapa -p'Nrp@2026$Kz9mXvL!' nrapa -e "SHOW TABLES LIKE 'payments';"
docker exec nrapa-db mysql -unrapa -p'Nrp@2026$Kz9mXvL!' nrapa -e "SHOW TABLES LIKE 'member_status_history';"
docker exec nrapa-db mysql -unrapa -p'Nrp@2026$Kz9mXvL!' nrapa -e "SHOW TABLES LIKE 'comments';"
docker exec nrapa-db mysql -unrapa -p'Nrp@2026$Kz9mXvL!' nrapa -e "SHOW TABLES LIKE 'audit_logs';"
```

## ✅ Verification Checklist

After rebuild, verify:
- [ ] Application starts without errors
- [ ] New migrations ran successfully
- [ ] Certificate types seeded (check admin can issue documents)
- [ ] Endorsement form shows all 110 calibres
- [ ] Public verification route works: `/verify/{qr_code}`
- [ ] Admin member page shows "Document Issuance" section

## 🔍 Troubleshooting

### If migrations fail:
```bash
# Check migration status
docker exec nrapa-app php artisan migrate:status

# Rollback last batch (if needed)
docker exec nrapa-app php artisan migrate:rollback --step=1

# Run migrations again
docker exec nrapa-app php artisan migrate
```

### If containers won't start:
```bash
# Check container status
docker ps -a | grep nrapa

# View detailed logs
docker logs nrapa-app --tail 100

# Restart specific service
docker compose restart nrapa-app
```

### Clear caches (if needed):
```bash
docker exec nrapa-app php artisan config:clear
docker exec nrapa-app php artisan cache:clear
docker exec nrapa-app php artisan route:clear
docker exec nrapa-app php artisan view:clear
```

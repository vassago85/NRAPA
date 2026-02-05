# NRAPA Deployment Guide

## Deployment Architecture

- **Server**: Docker + Portainer
- **Reverse Proxy**: Nginx Proxy Manager
- **Domain**: `nrapa.charsley.co.za`
- **Repository**: `github.com/vassago85/NRAPA` (private)

---

## 🚀 Initial Deployment

### 1. Clone Repository on Server
```bash
cd /opt
sudo mkdir nrapa && sudo chown paul:paul nrapa
git clone git@github.com:vassago85/NRAPA.git /opt/nrapa
cd /opt/nrapa
```

### 2. Build Docker Image
```bash
docker build -t nrapa-app:latest .
```

### 3. Deploy Stack in Portainer
- Go to **Stacks** → **Add Stack** → Name: `nrapa`
- Use **Web editor** and paste the docker-compose content
- Or run: `docker compose up -d`

### 4. Configure Nginx Proxy Manager
- **Domain**: `nrapa.charsley.co.za` (test environment)
- **Forward Hostname**: **Host IP** (e.g. `41.72.157.26`) — **not** `localhost`
- **Forward Port**: `8085`
- **Forward Scheme**: `http`
- **SSL**: Request Let's Encrypt certificate

> **Important**: NPM runs in Docker. Inside its container, `localhost` refers to NPM itself, not the host. Use the **server's host IP** (from `hostname -I | awk '{print $1}'`) so NPM can reach the NRAPA app on port 8085.

---

## 🔄 Update Process (IMPORTANT)

### When code changes are made locally:

**Step 1: Push changes from local (Windows/Cursor)**
```powershell
cd C:\laragon\www\NRAPA
git add -A
git commit -m "Description of changes"
git push
```

**Step 2: Pull and rebuild on server (SSH)**
```bash
cd /opt/nrapa
git pull
docker build -t nrapa-app:latest .
docker compose down
docker compose up -d
```

**Step 3: Run migrations (if needed)**
```bash
docker exec nrapa-app php artisan migrate --force
```

**Step 4: Clear caches**
```bash
docker exec nrapa-app php artisan config:clear
docker exec nrapa-app php artisan route:clear
docker exec nrapa-app php artisan view:clear
docker exec nrapa-app php artisan cache:clear
```

**Step 5: Verify**
```bash
docker logs nrapa-app --tail 30
```

### Quick Update (one-liner):
```bash
cd /opt/nrapa && git pull && docker build -t nrapa-app:latest . && docker compose down && docker compose up -d && docker exec nrapa-app php artisan migrate --force && docker exec nrapa-app php artisan config:clear && docker exec nrapa-app php artisan route:clear && docker exec nrapa-app php artisan view:clear
```

### Full Update Script (recommended):
```bash
#!/bin/bash
cd /opt/nrapa
git pull
docker build -t nrapa-app:latest .
docker compose down
docker compose up -d
sleep 5  # Wait for containers to start
docker exec nrapa-app php artisan migrate --force
docker exec nrapa-app php artisan config:clear
docker exec nrapa-app php artisan route:clear
docker exec nrapa-app php artisan view:clear
docker exec nrapa-app php artisan cache:clear
echo "Deployment complete!"
docker logs nrapa-app --tail 20
```

---

## 🗄️ Database Operations

### Fresh Install (wipes all data):
```bash
docker volume rm nrapa_db-data
docker compose up -d
# Migrations run automatically on startup
```

### Run Migrations Only:
```bash
docker exec nrapa-app php artisan migrate
```

### Seed Data:
```bash
docker exec nrapa-app php artisan db:seed
```

### Access Database CLI:
```bash
docker exec -it nrapa-db mysql -unrapa -p'Nrp@2026$Kz9mXvL!' nrapa
```

---

## 🔧 Troubleshooting

### 502 Bad Gateway Error

If you see a 502 error, check:

1. **NPM Configuration**: NPM runs in Docker — use the **host IP** (e.g. `41.72.157.26`), not `localhost`. Forward Port: `8085`, Scheme: `http`.
2. **Test from inside NPM container**: `docker exec nginx-proxy-manager curl -v http://$(hostname -I | awk '{print $1}'):8085` — should return 200.
3. **Container Status**: Verify the container is running: `docker ps | grep nrapa-app`
4. **Port Access from host**: `curl http://localhost:8085` (from server SSH)
5. **Container Logs**: `docker logs nrapa-app --tail 50`

**Why use host IP instead of localhost?**
- NPM is in a Docker container; inside it, `localhost` is the NPM container, not the host.
- Use the server's IP (e.g. `41.72.157.26` or output of `hostname -I | awk '{print $1}'`) so NPM can reach the app on port 8085.

### Check Container Status:
```bash
docker ps -a | grep nrapa
```

### View Logs:
```bash
docker logs nrapa-app --tail 100
docker logs nrapa-db --tail 50
```

### Restart Containers:
```bash
docker compose restart
```

### Shell into Container:
```bash
docker exec -it nrapa-app sh
```

### Clear Laravel Caches:
```bash
docker exec nrapa-app php artisan cache:clear
docker exec nrapa-app php artisan config:clear
docker exec nrapa-app php artisan route:clear
docker exec nrapa-app php artisan view:clear
```

---

## 🔐 Credentials

### Database
- **Host**: `db` (internal) / `nrapa-db` (container name)
- **Database**: `nrapa`
- **Username**: `nrapa`
- **Password**: `Nrp@2026$Kz9mXvL!`
- **Root Password**: `R00t#Nrp@Db$2026Qw!`

### App Key
```
base64:k2j+RehijTAMeuojTYpp7+7VHbr9BlMLlVPaDBVgDqw=
```

### Developer Login
- **Email**: `paul@charsley.co.za`
- **Password**: `PaulCharsley2026!`

---

## 📁 Important Paths

| Location | Path |
|----------|------|
| Local Dev | `C:\laragon\www\NRAPA` |
| Server | `/opt/nrapa` |
| Docker Volumes | `/var/lib/docker/volumes/nrapa_*` |
| Container App | `/var/www/html` |

---

## 🌐 URLs

| Environment | URL |
|-------------|-----|
| Test/Staging | `https://nrapa.charsley.co.za` |
| Production | `https://members.nrapa.co.za` (future) |
| Local Dev | `http://nrapa.test` |
| Monitoring | `https://status.charsley.co.za` |
| Invoicing | `https://invoice.charsley.co.za` |

---

## 📱 QR Code Configuration

QR codes are automatically generated for certificates and endorsement letters. They point to verification URLs that use `APP_URL`.

### Environment Setting Required

**Critical**: Set `APP_URL` to your test domain in Docker environment variables:

```env
APP_URL=https://nrapa.charsley.co.za
```

### Verify QR Code Configuration

After deployment, verify `APP_URL` is set correctly:

```bash
docker exec nrapa-app php artisan tinker
>>> config('app.url')
```

Should return: `https://nrapa.charsley.co.za` (test environment)

### Testing QR Codes

1. Generate a test certificate (admin access required)
2. View the certificate and scan the QR code
3. Verify it points to: `https://nrapa.charsley.co.za/verify/{qr_code}`
4. Test the verification page loads correctly

**See `DEPLOY_QR_CODE_SETUP.md` for detailed QR code testing instructions.**

---

## 📊 Additional Services

### Uptime Kuma (Monitoring)

See `docker/uptime-kuma/README.md` for full setup instructions.

**Quick Deploy:**
```bash
mkdir -p /opt/uptime-kuma
cp docker/uptime-kuma/docker-compose.yml /opt/uptime-kuma/
cd /opt/uptime-kuma
docker compose up -d
```

**NPM Proxy Setup:**
- Domain: `status.charsley.co.za`
- Forward Hostname: `localhost`
- Forward Port: `3001` (or the exposed port if different)
- Enable Websockets Support

---

### Invoice Ninja (Billing)

See `docker/invoice-ninja/README.md` for full setup instructions.

**Quick Deploy:**
```bash
mkdir -p /opt/invoice-ninja
cp docker/invoice-ninja/docker-compose.yml /opt/invoice-ninja/
cp docker/invoice-ninja/env.template /opt/invoice-ninja/.env
cd /opt/invoice-ninja

# Edit .env with your passwords
nano .env

# Generate APP_KEY
docker compose run --rm invoiceninja php artisan key:generate --show
# Add output to .env as IN_APP_KEY

# Start
docker compose up -d
```

**NPM Proxy Setup:**
- Domain: `invoice.charsley.co.za`
- Forward Hostname: `localhost`
- Forward Port: `8082` (check invoice-ninja docker-compose for actual port)
- Enable SSL

---

## 🐳 Docker Compose (for Portainer Web Editor)

```yaml
services:
  app:
    image: nrapa-app:latest
    container_name: nrapa-app
    restart: unless-stopped
    environment:
      - APP_NAME=NRAPA
      - APP_ENV=production
      - APP_DEBUG=false
      - APP_URL=https://nrapa.charsley.co.za  # Test environment
      - APP_KEY=base64:k2j+RehijTAMeuojTYpp7+7VHbr9BlMLlVPaDBVgDqw=
      - DB_CONNECTION=mysql
      - DB_HOST=db
      - DB_PORT=3306
      - DB_DATABASE=nrapa
      - DB_USERNAME=nrapa
      - DB_PASSWORD=Nrp@2026$Kz9mXvL!
      - CACHE_DRIVER=redis
      - SESSION_DRIVER=redis
      - REDIS_HOST=redis
      - MAIL_MAILER=log
    ports:
      - "8085:80"
    volumes:
      - app-storage:/var/www/html/storage/app
      - app-logs:/var/www/html/storage/logs
    networks:
      - nrapa-network
    depends_on:
      db:
        condition: service_healthy

  db:
    image: mariadb:11
    container_name: nrapa-db
    restart: unless-stopped
    environment:
      - MYSQL_ROOT_PASSWORD=R00t#Nrp@Db$2026Qw!
      - MYSQL_DATABASE=nrapa
      - MYSQL_USER=nrapa
      - MYSQL_PASSWORD=Nrp@2026$Kz9mXvL!
    volumes:
      - db-data:/var/lib/mysql
    networks:
      - nrapa-network
    healthcheck:
      test: ["CMD", "healthcheck.sh", "--connect", "--innodb_initialized"]
      interval: 10s
      timeout: 5s
      retries: 5

  redis:
    image: redis:alpine
    container_name: nrapa-redis
    restart: unless-stopped
    volumes:
      - redis-data:/data
    networks:
      - nrapa-network

networks:
  nrapa-network:
    driver: bridge

volumes:
  app-storage:
  app-logs:
  db-data:
  redis-data:
```

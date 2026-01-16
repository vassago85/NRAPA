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
- **Domain**: `nrapa.charsley.co.za`
- **Forward Hostname**: `nrapa-app`
- **Forward Port**: `80`
- **SSL**: Request Let's Encrypt certificate

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

**Step 3: Verify**
```bash
docker logs nrapa-app --tail 30
```

### Quick Update (one-liner):
```bash
cd /opt/nrapa && git pull && docker build -t nrapa-app:latest . && docker compose down && docker compose up -d
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
| Production | `https://nrapa.charsley.co.za` |
| Local Dev | `http://nrapa.test` |

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
      - APP_URL=https://nrapa.charsley.co.za
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
    volumes:
      - app-storage:/var/www/html/storage/app
      - app-logs:/var/www/html/storage/logs
    networks:
      - nrapa-network
      - nginx-proxy-manager_default
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
  nginx-proxy-manager_default:
    external: true

volumes:
  app-storage:
  app-logs:
  db-data:
  redis-data:
```

# Invoice Ninja v5 - BC Safaris

Self-hosted invoicing for BC Safaris.

## Deployment

1. **Copy files to server:**
   ```bash
   sudo mkdir -p /opt/invoice-ninja-bcsafaris
   sudo chown paul:paul /opt/invoice-ninja-bcsafaris
   cp /opt/nrapa/docker/invoice-ninja-bcsafaris/docker-compose.yml /opt/invoice-ninja-bcsafaris/
   cp /opt/nrapa/docker/invoice-ninja-bcsafaris/env.template /opt/invoice-ninja-bcsafaris/.env
   ```

2. **Configure environment:**
   ```bash
   cd /opt/invoice-ninja-bcsafaris
   nano .env
   ```

   Fill in:
   - `IN_DB_ROOT_PASSWORD` - Strong password for MariaDB root
   - `IN_DB_PASSWORD` - Strong password for app database
   - Mail settings (for sending invoices)

3. **Generate APP_KEY:**
   ```bash
   docker compose run --rm invoiceninja php artisan key:generate --show
   ```
   Copy the output to `IN_APP_KEY` in `.env`

4. **Start the containers:**
   ```bash
   docker compose up -d
   ```

5. **Configure Nginx Proxy Manager:**
   - Add Proxy Host: `invoice.bcsafaris.africa`
   - Forward Hostname: `bcsafaris-invoiceninja-app`
   - Forward Port: `80`
   - Enable SSL (Let's Encrypt)

6. **DNS Setup:**
   - Add A record for `invoice.bcsafaris.africa` pointing to server IP (41.72.157.26)

7. **Initial Setup:**
   - Visit https://invoice.bcsafaris.africa
   - Complete the setup wizard
   - Create admin account for BC Safaris

## Container Names

All containers are prefixed with `bcsafaris-` to avoid conflicts:
- `bcsafaris-invoiceninja-app` - Main application
- `bcsafaris-invoiceninja-db` - Database
- `bcsafaris-invoiceninja-cron` - Scheduled tasks

## Backup

```bash
cd /opt/invoice-ninja-bcsafaris
docker compose stop
docker run --rm -v bcsafaris-in-db-data:/data -v $(pwd):/backup alpine tar czf /backup/bcsafaris-db-backup.tar.gz /data
docker run --rm -v bcsafaris-in-storage:/data -v $(pwd):/backup alpine tar czf /backup/bcsafaris-storage-backup.tar.gz /data
docker compose up -d
```

# Invoice Ninja v5 Setup

Self-hosted invoicing and billing platform.

## Deployment

1. **Copy files to server:**
   ```bash
   mkdir -p /opt/invoice-ninja
   cd /opt/invoice-ninja
   # Copy docker-compose.yml and env.template to this directory
   ```

2. **Configure environment:**
   ```bash
   cp env.template .env
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
   - Add Proxy Host: `invoice.charsley.co.za`
   - Forward Hostname: `invoiceninja-app`
   - Forward Port: `80`
   - Enable SSL (Let's Encrypt)

6. **Initial Setup:**
   - Visit https://invoice.charsley.co.za
   - Complete the setup wizard
   - Create your admin account
   - Configure company details

## API Access (for NRAPA Integration)

To enable API access for automated invoice creation:

1. Login to Invoice Ninja
2. Go to Settings > Account Management > API Tokens
3. Create a new token with permissions:
   - `create_invoice`
   - `edit_invoice` (needed to email the invoice)
   - `view_client`
4. Copy the token into NRAPA under Owner Settings → Invoice Ninja:
   - API URL: `https://invoice.charsley.co.za`
   - API token
   - Client ID of **Ranyati** (the client the monthly NRAPA invoice is billed to)

NRAPA raises one invoice on the 1st of each month for the previous month: R1,500 hosting, plus R100 for every new member and renewal whose payment was confirmed in that month.

## Creating Ranyati as a Client

1. Go to Clients > New Client
2. Enter Ranyati's name, email, and billing address. The invoice email goes to this contact.
3. Open the client and copy the id from the URL into NRAPA's Invoice Ninja client ID.

## Backup

Data is stored in Docker volumes:
- `in-public` - Public assets
- `in-storage` - Uploaded files, PDFs
- `in-db-data` - Database

To backup:
```bash
# Stop containers first for consistent backup
docker compose stop

# Backup database
docker run --rm -v in-db-data:/data -v $(pwd):/backup alpine tar czf /backup/invoiceninja-db-backup.tar.gz /data

# Backup storage
docker run --rm -v in-storage:/data -v $(pwd):/backup alpine tar czf /backup/invoiceninja-storage-backup.tar.gz /data

# Restart
docker compose up -d
```

## Troubleshooting

**Container won't start:**
```bash
docker compose logs invoiceninja
```

**Database connection issues:**
```bash
docker compose exec invoiceninja php artisan db:monitor
```

**Clear cache:**
```bash
docker compose exec invoiceninja php artisan optimize:clear
```

**Run migrations (after updates):**
```bash
docker compose exec invoiceninja php artisan migrate --force
```

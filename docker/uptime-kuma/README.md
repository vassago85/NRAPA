# Uptime Kuma Setup

Self-hosted monitoring tool for tracking website/service uptime.

## Deployment

1. **Copy files to server:**
   ```bash
   mkdir -p /opt/uptime-kuma
   cd /opt/uptime-kuma
   # Copy docker-compose.yml to this directory
   ```

2. **Start the container:**
   ```bash
   docker compose up -d
   ```

3. **Initial setup:**
   - Access http://your-server-ip:3001
   - Create admin account on first visit
   - Add monitors for your services

4. **Configure Nginx Proxy Manager:**
   - Add Proxy Host: `status.charsley.co.za`
   - Forward Hostname: `uptime-kuma`
   - Forward Port: `3001`
   - Enable SSL (Let's Encrypt)
   - Enable Websockets Support (required for real-time updates)

## Monitors to Add

After setup, add these monitors:

| Name | URL | Interval |
|------|-----|----------|
| NRAPA Members Portal | https://members.nrapa.co.za | 60s |
| Invoice Ninja | https://invoice.charsley.co.za | 60s |
| Nginx Proxy Manager | http://npm:81 (internal) | 60s |

## Status Page

You can create a public status page at:
`https://status.charsley.co.za/status/main`

## Notifications

Configure notifications in Settings > Notifications:
- Email (SMTP)
- Ntfy (push notifications)
- Slack/Discord webhooks

## Backup

Data is stored in Docker volume `uptime-kuma-data`.

To backup:
```bash
docker run --rm -v uptime-kuma-data:/data -v $(pwd):/backup alpine tar czf /backup/uptime-kuma-backup.tar.gz /data
```

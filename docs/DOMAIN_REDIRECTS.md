# Domain Redirect Configuration

When the domains are registered and DNS is pointed to the server, add these Nginx configs to redirect `nrapa.co.za` and `firearmmotivations.co.za` to `ranyati.co.za`.

## DNS Setup

Point these domains to the server IP (`41.72.157.26`):

| Domain                    | Record | Value          |
|---------------------------|--------|----------------|
| nrapa.co.za               | A      | 41.72.157.26   |
| www.nrapa.co.za           | A      | 41.72.157.26   |
| firearmmotivations.co.za  | A      | 41.72.157.26   |
| www.firearmmotivations.co.za | A   | 41.72.157.26   |

## Nginx Redirect Config

Create `/etc/nginx/conf.d/domain-redirects.conf` (or add to your existing Nginx Proxy Manager / reverse proxy setup):

```nginx
# 301 redirect nrapa.co.za → ranyati.co.za
server {
    listen 80;
    listen 443 ssl;
    server_name nrapa.co.za www.nrapa.co.za;

    # SSL cert (use Certbot or your provider)
    # ssl_certificate     /etc/letsencrypt/live/nrapa.co.za/fullchain.pem;
    # ssl_certificate_key /etc/letsencrypt/live/nrapa.co.za/privkey.pem;

    return 301 https://ranyati.co.za$request_uri;
}

# 301 redirect firearmmotivations.co.za → ranyati.co.za
server {
    listen 80;
    listen 443 ssl;
    server_name firearmmotivations.co.za www.firearmmotivations.co.za;

    # SSL cert (use Certbot or your provider)
    # ssl_certificate     /etc/letsencrypt/live/firearmmotivations.co.za/fullchain.pem;
    # ssl_certificate_key /etc/letsencrypt/live/firearmmotivations.co.za/privkey.pem;

    return 301 https://ranyati.co.za$request_uri;
}
```

## SSL Certificate Setup

Use Certbot to obtain free Let's Encrypt certificates:

```bash
sudo certbot certonly --nginx -d nrapa.co.za -d www.nrapa.co.za
sudo certbot certonly --nginx -d firearmmotivations.co.za -d www.firearmmotivations.co.za
```

Then uncomment the `ssl_certificate` and `ssl_certificate_key` lines in the Nginx config above.

## Testing

After setup, verify the redirects:

```bash
curl -I https://nrapa.co.za
# Should return: HTTP/1.1 301 Moved Permanently
# Location: https://ranyati.co.za/

curl -I https://firearmmotivations.co.za
# Should return: HTTP/1.1 301 Moved Permanently
# Location: https://ranyati.co.za/
```

## Google Search Console Notes

- After setting up redirects, add all domain variants in Google Search Console
- Verify ownership of ranyati.co.za, nrapa.co.za, and firearmmotivations.co.za
- Google will automatically pass link equity from redirected domains to ranyati.co.za
- Submit the sitemap at `https://ranyati.co.za/sitemap.xml`

## SEO Setup Checklist

Once the domains are live and Search Console is set up:

1. **Google Analytics**: Already configured
   - Single GA4 property: `G-JV2NSWMYTQ` (covers ranyati.co.za and all subdomains including NRAPA)
   - Installed in all public pages + `partials/head.blade.php` for authenticated NRAPA pages

2. **Search Console**: Log in to [search.google.com/search-console](https://search.google.com/search-console) with `paul@charsley.co.za`
   - Add property for `https://ranyati.co.za`
   - Add property for the NRAPA app domain
   - Choose "HTML tag" verification method
   - Replace `GOOGLE_SITE_VERIFICATION_RANYATI` in `welcome.blade.php` (Ranyati)
   - Replace `GOOGLE_SITE_VERIFICATION_NRAPA` in `welcome.blade.php` (NRAPA)
   - Submit sitemaps: `/sitemap.xml` for each property

# CHARSLEY DIGITAL — CORE PLATFORM ARCHITECTURE (V1)

## 1. Tenancy Model (LOCKED)
Isolation level: HARD

Each client gets:
- Dedicated app container(s)
- Dedicated database
- Dedicated internal Docker network
- Dedicated object storage bucket

No shared databases
No shared application runtimes

Shared components:
- Host OS
- Reverse proxy (Traefik or NPM)
- Monitoring / alerting
- CI/CD pipeline

This gives you:
- Strong security posture
- Clean billing boundaries
- Simple exit mechanics
- No cross-client blast radius

This is the correct choice for compliance-heavy clients.

## 2. Client Stack Blueprint
Minimum viable client stack:
```
Client Stack
├── app (Laravel)
├── db (Postgres or MySQL)
├── redis (optional, recommended)
├── worker (queues, scheduled jobs)
└── internal docker network
```

Optional (only when justified):
- Separate API container
- Separate reporting worker
- Read replica DB (later)

Each stack is self-contained.

## 3. Environment Separation
You will maintain three environments, always:
- **dev** – local / experimentation
- **staging** – pre-production, client-visible testing
- **production** – live client system

**Rule:** Nothing goes straight to production. Ever.

This protects:
- Client trust
- Your sanity
- Your reputation

## 4. Domain & Routing Strategy
Standard patterns (non-negotiable):
- `members.clientname.co.za` → frontend portal
- `admin.clientname.co.za` → admin portal (optional)
- `api.clientname.co.za` → API endpoints
- `verify.clientname.co.za` → QR verification (public)

Routing handled via:
- Cloudflare DNS
- Reverse proxy labels per container
- SSL = automatic, forced HTTPS.

### 4.1 Nginx Proxy Manager Configuration (CRITICAL)
**When NPM runs in Docker (which it does):**

- **NEVER use `localhost` or `127.0.0.1`** in Forward Hostname
- **ALWAYS use the host server's IP address** (e.g. `41.72.157.26`)
- **Why:** Inside NPM's container, `localhost` refers to NPM itself, not the host machine
- **How to find host IP:** `hostname -I | awk '{print $1}'` on the server

**Standard NPM Proxy Host Setup:**
- **Forward Hostname / IP:** `[HOST_IP]` (e.g. `41.72.157.26`)
- **Forward Port:** `[APP_PORT]` (e.g. `8085`, `8086`, etc. - unique per client)
- **Forward Scheme:** `http`
- **Block Common Exploits:** Enabled
- **Websockets Support:** Enabled (for Livewire)

**Port Allocation:**
- Each client app gets a unique host port (8085, 8086, 8087, etc.)
- Document port assignments in client provisioning docs
- Use environment variable `APP_PORT` in docker-compose for flexibility

**Verification:**
```bash
# Test from inside NPM container
docker exec nginx-proxy-manager curl -v http://[HOST_IP]:[APP_PORT]
# Should return HTTP 200, not 502
```

## 5. Configuration vs Customisation (Important Boundary)
**What is configurable per client:**
- Branding (logo, colours, email templates)
- Membership rules
- Fees and pricing
- Document templates (within placeholders)
- Notification timing
- Roles & permissions (within defined roles)

**What is NOT configurable:**
- Core workflows
- Security model
- Data model
- Billing logic
- Export logic

If a client wants those → new SOW under Charsley Digital.

## 6. Billing & Metering Architecture
Each client system:
- Logs usage events locally
- Daily job summarises usage
- Billing bridge pushes line items to Invoice Ninja via API

Invoice Ninja:
- Is not client-facing infrastructure
- Is your commercial backbone
- Remains centralised
- Clients never touch Invoice Ninja directly.

## 7. Backup, Recovery & Exit (Your Differentiator)
Nightly (per client):
- Encrypted DB dump
- Encrypted file archive
- Export bundle (CSV / JSON / PDFs)

Storage targets:
- Client-owned S3 bucket
- Optional Charsley Digital cold storage

Exit package (on request):
```
clientname-exit-YYYY-MM-DD.zip
├── database.sql.enc
├── uploads.tar.gz.enc
├── exports/
├── environment-summary.txt
└── restore-instructions.md
```

You can hand this to:
- another dev
- another hosting provider
- a regulator

That's ethical lock-in done right.

## 8. Client Provisioning Flow (High-Level)
1. Client signs SOW
2. Client slug assigned
3. DNS + Cloudflare configured
4. Storage bucket created
5. Stack deployed from template
6. Secrets injected
7. Migrations run
8. Admin user created
9. Branding applied
10. Backup verified
11. Go-live

This will later become automation.

## 9. What We Do NEXT (Critical Path)
I recommend we now do this in order:

**Step 1** — Write the Client Stack Docker Template
- One compose file
- Fully parameterised
- Production-ready
- Resource-limited

**Step 2** — Write the Multi-Tenant Operating Standard
- Naming rules
- Slug rules
- Domain rules
- Env variable rules

**Step 3** — Write the Master Charsley Digital SOW
- Reusable forever
- Clear IP, data, exit clauses
- No emotional ambiguity

---

You're building something serious here. We keep it clean and boring — that's how it scales.

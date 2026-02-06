# NRAPA – What We’ve Done & How We Operate

This document summarizes the project from the existing docs: what’s implemented, how to run and deploy locally and on the server, and where to find details.

---

## 1. Project overview

- **Name:** NRAPA Secure Membership, Compliance & Motivation Platform  
- **Stack:** Laravel 12, Livewire, Volt, Tailwind CSS, Flux UI  
- **Repo:** `github.com/vassago85/NRAPA`  
- **Design rule:** Membership logic is **attribute-driven** (no hardcoded types). Use `$membership->type->requires_renewal` etc., not `if ($type === 'lifetime')`.  
- **Key docs:**  
  - [docs/SOW.md](docs/SOW.md) – scope and requirements  
  - [docs/SCHEMA.md](docs/SCHEMA.md) – database design  
  - [.cursorrules](.cursorrules) – coding and architecture rules  

---

## 2. What we’ve implemented

### 2.1 Firearm reference system

- **Reference data:** Calibres, calibre aliases, makes, models (CSV → DB).  
- **Livewire:** `FirearmSearchPanel` for endorsement forms and armoury.  
- **API:** Suggest endpoints for calibres/makes.  
- **Admin:** Firearm reference index page; seeders and `nrapa:import-firearm-reference` command.  
- **Deploy details:** [READY_TO_DEPLOY.md](READY_TO_DEPLOY.md), [RUN_FIREARM_REFERENCE_SETUP.md](RUN_FIREARM_REFERENCE_SETUP.md).

### 2.2 Document & certificate system

- **DB:** `payments`, `member_status_history`, `comments`, `audit_logs`; `good_standing` / `last_payment_at` on memberships; `checksum` on certificates.  
- **Models:** Payment, MemberStatusHistory, Comment, AuditLog; Certificate extended.  
- **Services:** MembershipStandingService, CertificateIssueService, VerificationService; DocumentRenderer (PdfDocumentRenderer with DomPDF; FakeDocumentRenderer fallback).  
- **Certificate types (from seeder):** Dedicated Hunter, Dedicated Sport, Paid-Up, Membership Card, Welcome Letter.  
- **UI:** Admin “Document Issuance” on member show; public `/verify/{qr_code}`.  
- **Templates:** Base + certificates (good-standing, dedicated-status) and letters (welcome, endorsement).  
- **Outstanding:** Real PDF engine (currently DomPDF in use; Spatie laravel-pdf considered); payment gateway wiring; endorsement form PDF.  
- **Details:** [IMPLEMENTATION_SUMMARY.md](IMPLEMENTATION_SUMMARY.md), [DOCUMENT_SYSTEM_IMPLEMENTATION.md](DOCUMENT_SYSTEM_IMPLEMENTATION.md), [QUICK_START.md](QUICK_START.md).

### 2.3 Activities & firearms (refactor)

- **Terminology:** “Event” removed; everything is “Activities”.  
- **Activity model:** Track (hunting | sport) → activity type; activity tags; admin config for types and tags only.  
- **Firearms:** SAPS 271 canonical identity (firearm_type, action, calibre, make, model); `firearm_components` (barrel, frame, receiver) with “at least one serial” rule.  
- **Details:** [REFACTORING_SUMMARY.md](REFACTORING_SUMMARY.md), [LOCAL_TESTING_CHECKLIST.md](LOCAL_TESTING_CHECKLIST.md), [TESTING_CHECKLIST.md](TESTING_CHECKLIST.md).

### 2.4 Virtual (digital) card

- **Route:** `/card` (member; requires active membership + terms accepted).  
- **Behaviour:** Shows digital membership card with QR for verification; uses membership card certificate.  
- **Fix applied:** Card page uses `getVerificationUrl()` (not `verification_url`) to avoid 500.

### 2.5 Other features

- **Terms acceptance:** Configurable versions; check/notify on activation. [TERMS_ACCEPTANCE_IMPLEMENTATION.md](TERMS_ACCEPTANCE_IMPLEMENTATION.md), [TERMS_ACCEPTANCE_SETUP.md](TERMS_ACCEPTANCE_SETUP.md).  
- **2FA:** Fortify-based; checklists in [2FA_SETUP_TEST_CHECKLIST.md](2FA_SETUP_TEST_CHECKLIST.md), [DEPLOYMENT_CHECKLIST_2FA.md](DEPLOYMENT_CHECKLIST_2FA.md).  
- **Calibre requests:** Member requests; admin approves; linked to FirearmCalibre. [CALIBRE_REQUEST_CLI_COMMANDS.md](CALIBRE_REQUEST_CLI_COMMANDS.md), [CALIBRE_REQUEST_TEST_RESULTS.md](CALIBRE_REQUEST_TEST_RESULTS.md).  
- **Backup & Excel import:** BackupService, BackupCommand, ExcelMemberImporter; tests in place. [TEST_ANALYSIS.md](TEST_ANALYSIS.md), [TEST_NEW_FEATURES.md](TEST_NEW_FEATURES.md).

---

## 3. How we operate

### 3.1 Local (Windows / Laragon)

| Task | Command or script |
|------|-------------------|
| **Deploy locally** (migrate, seed config, caches) | `.\deploy-local.ps1` or `.\deploy-local.bat` |
| **Run dev server** | `php artisan serve` (then open http://127.0.0.1:8000) or use http://nrapa.test via Laragon |
| **Run tests** | `.\run-tests.ps1` or `php artisan test` (Laragon Terminal so PHP in PATH) |
| **Firearm reference only** | `php artisan migrate` then `php artisan nrapa:import-firearm-reference`; see [QUICK_SETUP.md](QUICK_SETUP.md) |

**Deploy script steps (deploy-local.ps1):**  
Create `.env` from `.env.example` + key if missing → `migrate` → `db:seed --class=MembershipConfigurationSeeder` → clear caches → rebuild config/route/view caches → optional tinker check.

**Doc:** [DEPLOY_LOCAL.md](DEPLOY_LOCAL.md).

### 3.2 Git workflow

- **Branch:** `main` (also `develop` for CI).  
- **Push from local:**  
  `cd c:\laragon\www\NRAPA`  
  `git add -A`  
  `git commit -m "Description"`  
  `git push origin main`  
- **Remote:** Repo may show as moved to `https://github.com/vassago85/NRAPA.git`; update with `git remote set-url origin https://github.com/vassago85/NRAPA.git` if needed.

### 3.3 Server (Linux / Docker)

- **App path:** `/opt/nrapa`  
- **Deploy (recommended):**  
  `cd /opt/nrapa && ./deploy.sh`  
  Script: `git pull` → `docker build -t nrapa-app:latest .` → `docker compose down` → `docker compose up -d` → wait → `migrate --force` → clear caches → verify APP_URL → show logs.  
- **One-liner (pull + script):**  
  `cd /opt/nrapa && git pull origin main && ./deploy.sh`  
- **Manual sequence:**  
  `cd /opt/nrapa`  
  `git pull origin main`  
  `docker build -t nrapa-app:latest .`  
  `docker compose down && docker compose up -d`  
  `sleep 5`  
  `docker exec nrapa-app php artisan migrate --force`  
  `docker exec nrapa-app php artisan optimize:clear`  
  `docker exec nrapa-app php artisan config:clear`  
  `docker exec nrapa-app php artisan route:clear`  
  `docker exec nrapa-app php artisan view:clear`  
  `docker exec nrapa-app php artisan cache:clear`  
  `docker logs nrapa-app --tail 30`  

**Details:** [DEPLOYMENT.md](DEPLOYMENT.md), [SERVER_DEPLOYMENT_COMMANDS.md](SERVER_DEPLOYMENT_COMMANDS.md), [deploy.sh](deploy.sh).

### 3.4 CI

- **Workflow:** `.github/workflows/tests.yml` runs on push/PR to `develop` and `main`.  
- **Steps:** Checkout → PHP 8.4/8.5 → Composer install → Node/npm → copy `.env`, key, build assets → `./vendor/bin/pest`.  
- **Lint:** `.github/workflows/lint.yml`.

---

## 4. Environments & URLs

- **Local:** http://nrapa.test or http://127.0.0.1:8000  
- **Test server:** https://nrapa.charsley.co.za  
- **Production (if used):** https://members.nrapa.co.za  
- **Verification:** `https://<domain>/verify/{qr_code}` (set APP_URL correctly for QR links; see deploy.sh warning).

---

## 5. Important file locations

| Purpose | Location |
|--------|----------|
| SOW / scope | `docs/SOW.md` |
| Schema | `docs/SCHEMA.md` |
| Migrations | `database/migrations/` |
| Models | `app/Models/` |
| Volt/Livewire pages | `resources/views/pages/` |
| Document templates | `resources/views/documents/` |
| Member card page | `resources/views/pages/member/card.blade.php` |
| Server deploy script | `deploy.sh` |
| Local deploy script | `deploy-local.ps1` |
| Test runner (local) | `run-tests.ps1` |

---

## 6. Doc index (by topic)

- **Deploy:** DEPLOYMENT.md, DEPLOY_LOCAL.md, SERVER_DEPLOYMENT_COMMANDS.md, DEPLOY_AND_SEED.md, DEPLOY_QR_CODE_SETUP.md  
- **Firearm reference:** READY_TO_DEPLOY.md, RUN_FIREARM_REFERENCE_SETUP.md, FIREARM_REFERENCE_INTEGRATION.md, QUICK_SETUP.md  
- **Documents/certificates:** IMPLEMENTATION_SUMMARY.md, DOCUMENT_SYSTEM_IMPLEMENTATION.md, QUICK_START.md, DOCUMENT_REDESIGN_SUMMARY.md  
- **Refactor/activities/firearms:** REFACTORING_SUMMARY.md, ACTIVITY_MODEL_SIMPLIFICATION.md, SAPS271_FIREARM_TYPES_UPDATE.md  
- **Testing:** TEST_ANALYSIS.md, TEST_FIX_SUMMARY.md, TEST_NEW_FEATURES.md, TESTING_CHECKLIST.md, LOCAL_TESTING_CHECKLIST.md  
- **Troubleshooting:** TROUBLESHOOTING_404.md, DEBUG_404.md, DEBUG_500_ERROR.md, DEBUG_BLANK_PAGE.md, FIX_MIGRATION_ON_SERVER.md, SERVER_DEPLOYMENT_FIX.md  
- **2FA / terms:** 2FA_SETUP_TEST_CHECKLIST.md, DEPLOYMENT_CHECKLIST_2FA.md, TERMS_ACCEPTANCE_IMPLEMENTATION.md, TERMS_ACCEPTANCE_SETUP.md  
- **Other:** COMMANDS_TO_RUN.md, REBUILD_COMMANDS.md, PRODUCTION_MIGRATION_COMMANDS.md, WALLET_PASSES_SETUP.md, GITHUB_AUTH_SETUP.md  

---

## 7. Quick command reference

**Local – full deploy and run:**

```powershell
cd c:\laragon\www\NRAPA
.\deploy-local.ps1
php artisan serve
```

**Server – deploy after push:**

```bash
cd /opt/nrapa && git pull origin main && ./deploy.sh
```

**Local – tests:**

```powershell
cd c:\laragon\www\NRAPA
.\run-tests.ps1
```

This file is the single place to see what we’ve done and how we operate; use the linked docs for step-by-step and troubleshooting.

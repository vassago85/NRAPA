# NRAPA Members Portal — Pre-Launch Task List (Developer / Agency)

Tasks for **you** (developer/agency) before and around go-live. Tick each when done. If something does not apply, note “N/A” and why.

---

## 1. Technical & infrastructure

- [ ] **Domain & URL** — Live URL (e.g. members.nrapa.co.za) is set; DNS points to the correct server (or handover to NRAPA with clear instructions).
- [ ] **SSL** — HTTPS is working; certificate is valid and not expiring soon.
- [ ] **Production environment** — App, database, Redis, queue, and scheduler are running (e.g. Docker services up); deploy command has been run successfully.
- [ ] **Backups** — Database and important files (e.g. storage) are backed up; restore has been tested at least once.
- [ ] **Email** — Outgoing email (e.g. Mailgun) is configured; “From” address and domain are correct; test emails (welcome, password reset, etc.) are received and not spam.
- [ ] **File storage** — Document storage (e.g. S3/MinIO) is configured; certificates and uploads are saved and retrievable.
- [ ] **Firearm reference data** — Calibres, makes, and models are imported (`nrapa:import-firearm-reference` or equivalent) so search and endorsement forms work.

---

## 2. Member import

- [ ] **Import process** — Import script/command or tool is ready; run import using NRAPA-provided data.
- [ ] **Handover for verification** — After import, provide NRAPA with a way to double-check (e.g. sample list, counts, access to staging) so they can sign off.

---

## 3. Payments (if applicable)

- [ ] **Gateway** — Payment provider (e.g. PayFast) is configured for **live** mode (not test only).
- [ ] **Products** — Membership products and prices in the gateway match the portal (sign-up and renewal).
- [ ] **Webhooks / callbacks** — Payment success/failure notifications reach the app and update membership status; test with a real small payment if possible.
- [ ] **Receipts / invoices** — Members can see or receive confirmation of payment where required.

---

## 4. Security & access

- [ ] **Admin accounts** — Only authorised people have admin access; default or test admin passwords are changed.
- [ ] **2FA** — If two-factor is enabled for admins (or members), verify it works (enable, login, backup codes).
- [ ] **Member login** — Registration, login, and “Forgot password” work; new members receive welcome/activation emails as expected.

---

## 5. Key member flows (test before launch)

- [ ] **New member sign-up** — Choose membership type → pay (if applicable) → account created → can log in.
- [ ] **Certificate generation** — For a test member in good standing, generate membership and (if applicable) dedicated status certificate; download and open PDF; QR code scans and verification page shows correct info.
- [ ] **Endorsement request** — Submit an endorsement request through the member flow; admin can process it; member can download the letter (and QR works if applicable).
- [ ] **Digital membership card** — Member can open the card page; QR is visible and verification URL works.
- [ ] **Virtual Safe** — Add a firearm; details save; expiry reminder (if applicable) is clear.
- [ ] **Activity submission** — Log at least one activity; it appears in the member’s activity history and (if applicable) in admin.

---

## 6. Post-launch (first 24–48 hours)

- [ ] **Monitor** — Check error logs, queue jobs, and payment notifications for failures.
- [ ] **Support** — Fix any critical sign-up or login issues NRAPA reports; respond to technical queries.

---

*Use the NRAPA staff checklist ([PRE_LAUNCH_CHECKLIST_NRAPA_STAFF.md](PRE_LAUNCH_CHECKLIST_NRAPA_STAFF.md)) for tasks that only the customer can do (domain access, content sign-off, member import verification, communications).*

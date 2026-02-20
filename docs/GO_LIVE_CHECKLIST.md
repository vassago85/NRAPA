# NRAPA Portal — Simple Go-Live Checklist

Quick pre-launch checklist. Tick each item when done.

---

## R2 Storage

- [ ] **R2 bucket created** — Cloudflare R2 bucket exists (e.g. `nrapa-storage`)
- [ ] **R2 credentials configured** — Owner/Developer has set Access Key, Secret Key, Endpoint, Bucket name in Owner Settings → Storage
- [ ] **Test connection** — Save storage settings and verify “Successfully connected to Cloudflare R2!” shows
- [ ] **Test upload** — Upload a test document (e.g. member document or activity evidence) and confirm it saves and displays

---

## Test & Check

- [ ] **Member login** — Can log in with test member
- [ ] **Document upload** — Upload ID or Proof of Address; verify it appears and admin can view
- [ ] **Activity submission** — Submit a test activity; verify admin can approve
- [ ] **Knowledge test** — Take a test; verify results show correctly
- [ ] **Endorsement request** — Submit an endorsement; verify documents and flow work
- [ ] **Certificate generation** — Generate a membership certificate; verify PDF downloads and looks correct
- [ ] **QR verification** — Scan a membership card QR and confirm the public verification page works

---

## Final

- [ ] **DNS** — Members portal domain points to live server
- [ ] **Backups** — Daily R2 backup is enabled (Owner/Developer settings)
- [ ] **Support** — Someone is ready to handle member queries from launch

---

*When all items are ticked, NRAPA is ready to go live.*

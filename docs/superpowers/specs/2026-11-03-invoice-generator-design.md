# Invoice Generator — Phase 1 (Admin Download)

**Date:** 2026-11-03
**Status:** Approved, in build

## Goal

Admins can download a PDF tax-invoice for any transaction NRAPA has recorded. The layout matches the Sage sample `INV0004666`:
headed "INVOICE", `FROM` block for NRAPA, `TO` block for the member, line-item table, bank footer, and a `BALANCE DUE` row showing the full amount even when the payment has already cleared. Numbers follow a new NRAPA sequence `I-NRAPA-000001`, assigned once per transaction and reprinted identically on every later download.

Members do **not** see or receive the PDFs in this phase. Member self-service and emailing are later work.

## Scope

In scope:

- A first-class `invoices` table that owns the number, the amount at issue, and a frozen snapshot of what was invoiced.
- Backfill of every existing billable membership whose `payment_confirmed_at` is set, in payment-date order, starting at `I-NRAPA-000001`.
- Issue-on-approval for every new billable membership from today forward (same `payment_confirmed_at` event).
- One admin route to stream the PDF, linked from each row of **Admin → Billing → Payments Received**.
- Pest feature tests for authorisation, numbering stability, and layout wiring.

Out of scope (later phases):

- Member download, email delivery, Mailable, visual email template.
- Non-membership transactions (shop, event fees). The table already carries polymorphic `source_type`/`source_id` columns so the sequence can extend to them without a schema change.
- Credit notes, refunds, VAT reversal. NRAPA is non-VAT; a 0% invoice is sufficient for now.
- Admin editing of invoice contents. Once issued, invoices are immutable.

## Data model

```sql
invoices
  id                bigint pk
  uuid              uuid unique
  number            string unique            -- 'I-NRAPA-000001'
  user_id           fk users
  source_type       string                   -- Membership::class (future: Order, EventRegistration, ...)
  source_id         bigint
  issued_at         datetime                 -- = payment_confirmed_at for membership invoices
  currency          char(3) default 'ZAR'
  subtotal          decimal(10,2)
  vat_rate          decimal(5,2) default 0
  vat_amount        decimal(10,2) default 0
  total             decimal(10,2)
  reference         string nullable          -- member's NRAPA-… bank reference
  line_items        json                     -- [{sku, description, qty, unit_price, discount_pct, vat_pct, excl_total, incl_total}]
  payload           json                     -- snapshot: from, to, bank, notes at issue
  pdf_path          string nullable          -- cached PDF on private disk once first rendered
  issued_by         fk users nullable        -- the admin who first materialised it (null = backfill/system)
  created_at, updated_at
  unique(source_type, source_id)             -- one invoice per transaction
  index(user_id), index(number), index(issued_at)
```

`line_items` and `payload` carry the frozen snapshot so later downloads reprint what was invoiced even if the member later changes address or NRAPA changes its bank details.

## Numbering

- Prefix and width stored in `SystemSettings`: `invoice_number_prefix = 'I-NRAPA-'`, `invoice_number_width = 6`, `invoice_number_next = 1` (editable in Owner → Settings before the first invoice).
- `App\Services\Billing\InvoiceNumberSequence::next()` performs an atomic increment inside a transaction with a row-level lock on the settings row (`DB::transaction` + `lockForUpdate` on the row), returning the formatted number.
- Once an invoice row is written, its `number` is immutable.

## Services

```
App\Services\Billing\
  InvoiceNumberSequence    - atomic next()
  InvoiceIssuer            - forMembership(Membership $m, ?User $actor = null): Invoice
                             idempotent — returns existing if sourceType+sourceId already invoiced
  InvoiceRenderer          - render(Invoice $i): string  -- returns pdf disk path (uses PdfDocumentRenderer)
```

`InvoiceIssuer::forMembership` is the single mint-point. It:

1. Short-circuits if an `Invoice` already exists for `(Membership::class, $m->id)`.
2. Guards: membership must be billable AND have `payment_confirmed_at`. Otherwise throws `InvoiceNotIssuableException`.
3. Builds the snapshot payload from `User`, `Membership`, `MembershipType`, `SystemSetting::getBankAccount()`.
4. Builds a single line item: `{sku: 'RENEWMEM' or 'NEWMEM', description: "{SKU} — Annual {Renewal|Membership} ({type name})", qty: 1, unit_price: amount_due, discount_pct: 0, vat_pct: 0}`.
5. Calls `InvoiceNumberSequence::next()`.
6. Writes the `Invoice` row inside a transaction with the sequence update.

Called by:

- A one-off command `php artisan nrapa:backfill-invoices` (loops confirmed billable memberships ordered by `payment_confirmed_at`, idempotent so re-runs are safe).
- The admin download controller (lazy issue on first click).
- `ApproveMembership::approveApplication` and `::approveChange`, after the `payment_confirmed_at` update, inside the same approval event so new memberships from today forward get a number at the business event rather than at download time.

## PDF layout

Template: `resources/views/documents/invoices/standard.blade.php`.

Matches `INV0004666` field-for-field:

- Header: `INVOICE` + `NUMBER / REFERENCE / DATE / DUE DATE / SALES REP / OVERALL DISCOUNT % / PAGE`. `DATE` and `DUE DATE` both = `issued_at` (already paid). `SALES REP` blank. `REFERENCE` = member's NRAPA bank reference. `OVERALL DISCOUNT %` = 0.
- `FROM` block: NRAPA name, VAT no (blank — non-VAT), physical + postal address from a new `invoice.physical_address` / `invoice.postal_address` setting (defaulting to `702 Esther Street, Garsfontein, Pretoria 0042`).
- `TO` block: `{id_number} - {NAME IN CAPS}`, `Joining Date {User::joinedOn()}`, physical + postal address from `User`.
- Line-item table: columns `Description / Quantity / Unit Price / Disc % / VAT % / Excl. Total / Incl. Total`.
- Bank footer: pulls from `SystemSetting::getBankAccount()` — currently FNB + account number.
- Totals block: `Total Discount / Total Exclusive / Total VAT / Sub Total / Grand Total / BALANCE DUE`. **BALANCE DUE prints the full amount** even on paid invoices (per sample).

Rendered via existing `PdfDocumentRenderer::generatePdf` (Gotenberg first, DomPDF fallback). The first render for an invoice writes to the private disk at `invoices/{YYYY}/{number}.pdf` and stores the path on the Invoice row; later downloads re-stream the cached file.

## Routes & UI

```
GET /admin/invoices/{invoice:number}/download    name: admin.invoices.download
```

Controller `App\Http\Controllers\Admin\InvoiceDownloadController`:

- `auth`, `verified`, `admin` middleware (same stack as the billing page).
- Resolves by `number`, 404 otherwise.
- If `pdf_path` is missing or missing on disk, calls `InvoiceRenderer::render($invoice)` to produce it.
- Streams the file with a sensible download name: `{number}.pdf`.
- Audit-logs `invoice.downloaded` against the Invoice with the actor.

**Admin → Billing → Payments Received** gets a new last column, `Invoice`, with a download link per row. The link calls a Livewire action that resolves the invoice via `InvoiceIssuer::forMembership($m)` and then redirects to the download route. Rows for memberships that aren't invoicable (shouldn't happen because the list is already filtered to `payment_confirmed_at + billable`) show "—".

## Owner settings additions

A small block on **Owner → Settings** labelled "Invoicing":

- Invoice number prefix (default `I-NRAPA-`).
- Invoice number width (default `6`).
- Next invoice number (default `1`, editable **only** while `Invoice::count() === 0`; once invoices exist, the field becomes read-only and shows `Last issued: I-NRAPA-NNNNNN`).
- Business physical address (default `702 Esther Street, Garsfontein, Pretoria 0042`).
- Business postal address (same default).
- Business VAT number (blank, free-form).

## Tests (Pest, feature)

1. Admin can download an invoice; non-admin gets 403.
2. First download for a membership mints an invoice; second download reprints the same number and the same bytes.
3. Backfill command assigns numbers in `payment_confirmed_at` order and is idempotent (re-running produces zero new rows).
4. `InvoiceIssuer::forMembership` is a no-op for a non-billable membership (import) and for a billable membership that hasn't been paid.
5. Numbering uses the configured prefix/width and advances the stored `invoice_number_next`.
6. New-membership approval (`ApproveMembership::approveApplication`) issues an invoice as part of the approval event.
7. Rendered PDF contains the member name, membership number, bank reference, and `BALANCE DUE R{amount}`.

Rendering tests stub `PdfDocumentRenderer` the same way existing dedicated-certificate tests do (asserted against the invoke, not the bytes).

## Rollout plan

1. Ship migration + model + services + settings + blade + route + button.
2. On deploy, migrations run, no backfill yet.
3. Admin runs `php artisan nrapa:backfill-invoices` once from the server; invoices appear in payment-date order.
4. From that point every new membership approval mints its own invoice.

Rebuild still goes via the normal `/opt/nrapa` flow.

@php
    /** @var \App\Models\Invoice $invoice */
    $payload = $invoice->payload ?? [];
    $from = $payload['from'] ?? [];
    $to = $payload['to'] ?? [];
    $bank = $payload['bank'] ?? [];
    $membership = $payload['membership'] ?? [];
    $lineItems = $invoice->line_items ?? [];

    $fmt = fn ($amount) => 'R'.number_format((float) $amount, 2, '.', ',');
    $pct = fn ($p) => number_format((float) $p, 2, '.', '').'%';

    // DomPDF has no `white-space: pre-line`, so emulate it by replacing \n with <br>.
    $multiline = fn ($text) => nl2br(e((string) $text));
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>{{ $invoice->number }}</title>
    <style>
        @page { size: A4; margin: 14mm 12mm; }

        * { box-sizing: border-box; }

        body {
            font-family: Helvetica, Arial, sans-serif;
            font-size: 10pt;
            color: #1f2937;
            margin: 0;
        }

        .header {
            width: 100%;
            margin-bottom: 10mm;
        }

        .header-table { width: 100%; border-collapse: collapse; }

        .header h1 {
            font-size: 22pt;
            margin: 0 0 4mm 0;
            letter-spacing: 1px;
            color: #0f172a;
        }

        .meta-table { width: 100%; border-collapse: collapse; margin-top: 2mm; }
        .meta-table td { padding: 1mm 2mm; vertical-align: top; }
        .meta-label { color: #6b7280; font-size: 8pt; text-transform: uppercase; letter-spacing: 0.4px; }
        .meta-value { color: #0f172a; font-weight: 600; font-size: 9.5pt; }

        .party-table { width: 100%; border-collapse: collapse; margin-top: 6mm; }
        .party-table td {
            width: 50%;
            vertical-align: top;
            padding: 3mm 4mm;
            border: 1px solid #e5e7eb;
            background: #f9fafb;
        }
        .party-label {
            display: block;
            font-size: 8pt;
            text-transform: uppercase;
            color: #6b7280;
            letter-spacing: 0.4px;
            margin-bottom: 1mm;
        }
        .party-title {
            font-size: 11pt;
            font-weight: 700;
            color: #0f172a;
            margin-bottom: 2mm;
        }
        .party-grid { width: 100%; border-collapse: collapse; margin-top: 2mm; }
        .party-grid td { padding: 0.6mm 0; vertical-align: top; }
        .party-grid td.k { color: #6b7280; font-size: 8pt; width: 38%; text-transform: uppercase; letter-spacing: 0.3px; }
        .party-grid td.v { color: #111827; font-size: 9pt; }

        table.items {
            width: 100%;
            border-collapse: collapse;
            margin-top: 8mm;
            font-size: 9pt;
        }
        table.items thead th {
            background: #0f172a;
            color: #fff;
            padding: 2.5mm;
            text-align: left;
            font-weight: 600;
            font-size: 8.5pt;
            text-transform: uppercase;
            letter-spacing: 0.4px;
        }
        table.items thead th.num,
        table.items tbody td.num { text-align: right; }
        table.items tbody td {
            padding: 2.5mm;
            border-bottom: 1px solid #e5e7eb;
        }

        .footer-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 8mm;
        }
        .footer-table td {
            vertical-align: top;
            padding: 3mm 4mm;
        }
        .bank-cell {
            width: 55%;
            border: 1px solid #e5e7eb;
            background: #f9fafb;
        }
        .totals-cell {
            width: 45%;
        }
        .totals-table {
            width: 100%;
            border-collapse: collapse;
        }
        .totals-table td {
            padding: 1.5mm 2mm;
            font-size: 9pt;
        }
        .totals-table td.k { color: #6b7280; }
        .totals-table td.v {
            text-align: right;
            font-weight: 600;
            color: #0f172a;
        }
        .totals-table tr.sep td { border-top: 1px solid #e5e7eb; }
        .totals-table tr.grand td {
            padding-top: 2.5mm;
            font-size: 11pt;
            font-weight: 700;
            color: #0f172a;
            border-top: 2px solid #0f172a;
        }
        .balance-row td {
            background: #0f172a;
            color: #fff;
            padding: 3mm 4mm;
            font-size: 11pt;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.4px;
        }
        .balance-row td.v { text-align: right; }

        .bank-title {
            font-size: 10pt;
            font-weight: 700;
            color: #0f172a;
            margin-bottom: 2mm;
            text-transform: uppercase;
            letter-spacing: 0.4px;
        }
        .bank-grid td { padding: 0.6mm 0; }
        .bank-grid td.k { color: #6b7280; font-size: 8.5pt; width: 42%; }
        .bank-grid td.v { color: #111827; font-size: 9pt; font-weight: 600; }
    </style>
</head>
<body>

<div class="header">
    <h1>INVOICE</h1>

    <table class="meta-table">
        <tr>
            <td style="width:16%;"><div class="meta-label">Number</div><div class="meta-value">{{ $invoice->number }}</div></td>
            <td style="width:20%;"><div class="meta-label">Reference</div><div class="meta-value">{{ $invoice->reference ?? '—' }}</div></td>
            <td style="width:14%;"><div class="meta-label">Date</div><div class="meta-value">{{ optional($invoice->issued_at)->format('d/m/Y') }}</div></td>
            <td style="width:14%;"><div class="meta-label">Due Date</div><div class="meta-value">{{ optional($invoice->issued_at)->format('d/m/Y') }}</div></td>
            <td style="width:14%;"><div class="meta-label">Sales Rep</div><div class="meta-value">—</div></td>
            <td style="width:14%;"><div class="meta-label">Overall Discount %</div><div class="meta-value">0.00%</div></td>
            <td style="width:8%;"><div class="meta-label">Page</div><div class="meta-value">1/1</div></td>
        </tr>
    </table>
</div>

<table class="party-table">
    <tr>
        <td>
            <span class="party-label">From</span>
            <div class="party-title">{{ $from['name'] ?? 'NRAPA' }}</div>
            <table class="party-grid">
                <tr>
                    <td class="k">VAT No</td>
                    <td class="v">{{ $from['vat_number'] ?: '—' }}</td>
                </tr>
                <tr>
                    <td class="k">Physical Address</td>
                    <td class="v">{!! $multiline($from['physical_address'] ?? '') !!}</td>
                </tr>
                <tr>
                    <td class="k">Postal Address</td>
                    <td class="v">{!! $multiline($from['postal_address'] ?? '') !!}</td>
                </tr>
            </table>
        </td>
        <td>
            <span class="party-label">To</span>
            <div class="party-title">
                @if (! empty($to['id_number']))
                    {{ $to['id_number'] }} - {{ $to['name'] ?? '' }}
                @else
                    {{ $to['name'] ?? '' }}
                @endif
            </div>
            <table class="party-grid">
                <tr>
                    <td class="k">Customer VAT No</td>
                    <td class="v">{{ $to['customer_vat_number'] ?: '—' }}</td>
                </tr>
                @if (! empty($to['joining_date']))
                <tr>
                    <td class="k">Joining Date</td>
                    <td class="v">{{ $to['joining_date'] }}</td>
                </tr>
                @endif
                @if (! empty($membership['number']))
                <tr>
                    <td class="k">Membership No</td>
                    <td class="v">{{ $membership['number'] }}</td>
                </tr>
                @endif
                <tr>
                    <td class="k">Physical Address</td>
                    <td class="v">{!! $multiline($to['physical_address'] ?? '') !!}</td>
                </tr>
                <tr>
                    <td class="k">Postal Address</td>
                    <td class="v">{!! $multiline($to['postal_address'] ?? '') !!}</td>
                </tr>
            </table>
        </td>
    </tr>
</table>

<table class="items">
    <thead>
        <tr>
            <th>Description</th>
            <th class="num">Quantity</th>
            <th class="num">Unit Price</th>
            <th class="num">Disc %</th>
            <th class="num">VAT %</th>
            <th class="num">Excl. Total</th>
            <th class="num">Incl. Total</th>
        </tr>
    </thead>
    <tbody>
        @foreach ($lineItems as $line)
            <tr>
                <td>{{ $line['description'] ?? '' }}</td>
                <td class="num">{{ $line['qty'] ?? 1 }}</td>
                <td class="num">{{ $fmt($line['unit_price'] ?? 0) }}</td>
                <td class="num">{{ $pct($line['discount_pct'] ?? 0) }}</td>
                <td class="num">{{ $pct($line['vat_pct'] ?? 0) }}</td>
                <td class="num">{{ $fmt($line['excl_total'] ?? 0) }}</td>
                <td class="num">{{ $fmt($line['incl_total'] ?? 0) }}</td>
            </tr>
        @endforeach
    </tbody>
</table>

<table class="footer-table">
    <tr>
        <td class="bank-cell">
            <div class="bank-title">{{ $bank['bank_name'] ?: 'Bank details to be confirmed' }}</div>
            <table class="bank-grid">
                @if (! empty($bank['account_name']))
                    <tr><td class="k">Account Name</td><td class="v">{{ $bank['account_name'] }}</td></tr>
                @endif
                @if (! empty($bank['account_number']))
                    <tr><td class="k">Account Number</td><td class="v">{{ $bank['account_number'] }}</td></tr>
                @endif
                @if (! empty($bank['branch_code']))
                    <tr><td class="k">Branch Code</td><td class="v">{{ $bank['branch_code'] }}</td></tr>
                @endif
                @if (! empty($bank['account_type']))
                    <tr><td class="k">Account Type</td><td class="v">{{ $bank['account_type'] }}</td></tr>
                @endif
                @if (! empty($invoice->reference))
                    <tr><td class="k">Use Reference</td><td class="v">{{ $invoice->reference }}</td></tr>
                @endif
            </table>
        </td>
        <td class="totals-cell">
            <table class="totals-table">
                <tr><td class="k">Total Discount</td><td class="v">{{ $fmt(0) }}</td></tr>
                <tr><td class="k">Total Exclusive</td><td class="v">{{ $fmt($invoice->subtotal) }}</td></tr>
                <tr><td class="k">Total VAT</td><td class="v">{{ $fmt($invoice->vat_amount) }}</td></tr>
                <tr class="sep"><td class="k">Sub Total</td><td class="v">{{ $fmt($invoice->subtotal) }}</td></tr>
                <tr class="grand"><td class="k">Grand Total</td><td class="v">{{ $fmt($invoice->total) }}</td></tr>
                <tr class="balance-row"><td>Balance Due</td><td class="v">{{ $fmt($invoice->total) }}</td></tr>
            </table>
        </td>
    </tr>
</table>

</body>
</html>

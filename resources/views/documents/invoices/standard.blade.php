@php
    /** @var \App\Models\Invoice $invoice */
    $payload = $invoice->payload ?? [];
    $to = $payload['to'] ?? [];
    $bank = $payload['bank'] ?? [];
    $membership = $payload['membership'] ?? [];
    $lineItems = $invoice->line_items ?? [];

    $fmt = fn ($amount) => 'R'.number_format((float) $amount, 2, '.', ',');
    $multiline = fn ($text) => nl2br(e((string) $text));

    $address = trim((string) ($to['physical_address'] ?? ''))
        ?: trim((string) ($to['postal_address'] ?? ''));

    $logoPath = public_path('logo-nrapa-blue-text.png');
    $logoData = is_file($logoPath)
        ? 'data:image/png;base64,'.base64_encode((string) file_get_contents($logoPath))
        : '';
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>{{ $invoice->number }}</title>
    <style>
        @page { size: A4; margin: 16mm 16mm 18mm 16mm; }

        * { box-sizing: border-box; }

        body {
            font-family: Helvetica, Arial, sans-serif;
            font-size: 11pt;
            color: #1a2332;
            margin: 0;
        }

        .top {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 12mm;
        }
        .top td { vertical-align: top; }
        .logo { max-height: 22mm; width: auto; }
        .title {
            text-align: right;
        }
        .title h1 {
            margin: 0 0 2mm 0;
            font-size: 22pt;
            letter-spacing: 2px;
            color: #0b4ea2;
        }
        .title .number {
            font-size: 12pt;
            font-weight: 700;
            color: #1a2332;
        }
        .title .date {
            margin-top: 1mm;
            font-size: 10pt;
            color: #5b6573;
        }

        .parties {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 10mm;
        }
        .parties td {
            width: 50%;
            vertical-align: top;
            padding: 0 8mm 0 0;
        }
        .parties td + td { padding: 0 0 0 8mm; }
        .label {
            font-size: 8pt;
            letter-spacing: 0.8px;
            text-transform: uppercase;
            color: #0b4ea2;
            margin-bottom: 2mm;
        }
        .name {
            font-size: 13pt;
            font-weight: 700;
            margin-bottom: 1.5mm;
        }
        .muted { color: #5b6573; font-size: 10pt; line-height: 1.45; }

        table.items {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 8mm;
        }
        table.items th {
            text-align: left;
            font-size: 8pt;
            letter-spacing: 0.8px;
            text-transform: uppercase;
            color: #0b4ea2;
            padding: 0 0 2.5mm 0;
            border-bottom: 1.5px solid #0b4ea2;
        }
        table.items th.amt,
        table.items td.amt { text-align: right; }
        table.items td {
            padding: 4mm 0;
            border-bottom: 1px solid #e6e9ee;
            vertical-align: top;
        }
        table.items tr.total td {
            border-bottom: none;
            padding-top: 5mm;
            font-weight: 700;
            font-size: 13pt;
        }

        .pay {
            margin-top: 10mm;
            padding: 5mm 6mm;
            background: #f4f7fb;
            border-left: 3px solid #0b4ea2;
        }
        .pay .label { margin-bottom: 2mm; }
        .pay .bank { font-weight: 700; margin-bottom: 1mm; }
        .pay .row { font-size: 10pt; color: #1a2332; line-height: 1.55; }
    </style>
</head>
<body>

<table class="top">
    <tr>
        <td style="width:62%;">
            @if ($logoData)
                <img class="logo" src="{{ $logoData }}" alt="NRAPA">
            @else
                <div class="name" style="color:#0b4ea2;">NRAPA</div>
            @endif
        </td>
        <td class="title">
            <h1>INVOICE</h1>
            <div class="number">{{ $invoice->number }}</div>
            <div class="date">{{ optional($invoice->issued_at)->format('d F Y') }}</div>
        </td>
    </tr>
</table>

<table class="parties">
    <tr>
        <td>
            <div class="label">Bill to</div>
            <div class="name">{{ $to['name'] ?? '' }}</div>
            @if (! empty($to['id_number']))
                <div class="muted">ID {{ $to['id_number'] }}</div>
            @endif
            @if ($address !== '')
                <div class="muted" style="margin-top:2mm;">{!! $multiline($address) !!}</div>
            @endif
        </td>
        <td>
            <div class="label">Details</div>
            @if (! empty($membership['number']))
                <div class="muted">Membership {{ $membership['number'] }}</div>
            @endif
            @if (! empty($invoice->reference))
                <div class="muted">Reference {{ $invoice->reference }}</div>
            @endif
        </td>
    </tr>
</table>

<table class="items">
    <thead>
        <tr>
            <th>Description</th>
            <th class="amt">Amount</th>
        </tr>
    </thead>
    <tbody>
        @foreach ($lineItems as $line)
            @php
                $desc = (string) ($line['description'] ?? '');
                $sku = (string) ($line['sku'] ?? '');
                if ($sku !== '' && str_starts_with($desc, $sku.' - ')) {
                    $desc = substr($desc, strlen($sku) + 3);
                }
            @endphp
            <tr>
                <td>{{ $desc }}</td>
                <td class="amt">{{ $fmt($line['incl_total'] ?? $line['unit_price'] ?? 0) }}</td>
            </tr>
        @endforeach
        <tr class="total">
            <td>Total</td>
            <td class="amt">{{ $fmt($invoice->total) }}</td>
        </tr>
    </tbody>
</table>

@if (! empty($bank['bank_name']) || ! empty($bank['account_number']))
    <div class="pay">
        <div class="label">Payment details</div>
        @if (! empty($bank['bank_name']))
            <div class="bank">{{ $bank['bank_name'] }}</div>
        @endif
        @if (! empty($bank['account_name']))
            <div class="row">{{ $bank['account_name'] }}</div>
        @endif
        @if (! empty($bank['account_number']))
            <div class="row">Account {{ $bank['account_number'] }}</div>
        @endif
        @if (! empty($bank['branch_code']))
            <div class="row">Branch {{ $bank['branch_code'] }}</div>
        @endif
        @if (! empty($invoice->reference))
            <div class="row">Use reference {{ $invoice->reference }}</div>
        @endif
    </div>
@endif

</body>
</html>

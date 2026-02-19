<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: Helvetica, Arial, sans-serif;
            font-size: 9px;
            color: #111827;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }

        @page { size: A4 portrait; margin: 8mm 12mm; }
        @media print { body { background: #fff !important; } }

        .page {
            width: 186mm;
            margin: 0 auto;
            padding: 0;
        }

        .grid-2up {
            width: 100%;
            border-collapse: collapse;
        }
        .grid-2up td {
            width: 50%;
            vertical-align: top;
            padding: 1.5mm;
        }

        /* ── Compact label card ── */
        .label-card {
            border: 1px solid #d1d5db;
            border-radius: 6px;
            overflow: hidden;
            page-break-inside: avoid;
        }

        .lbl-header {
            background-color: #0B4EA2;
            min-height: 8.4mm; /* 20% more than logo height (7mm * 1.2) */
            padding: 0.7mm 2.5mm;
        }
        .lbl-header-tbl { width: 100%; }
        .lbl-header-tbl td { vertical-align: middle; font-weight: 700; }
        .lbl-logo-td { width: 9mm; }
        .lbl-logo {
            width: 7mm;
            height: 7mm;
            background: #fff;
            border-radius: 2px;
            text-align: center;
            padding: 0.5mm;
        }
        .lbl-logo img { width: 6mm; height: 6mm; }
        .lbl-name {
            font-size: 14px;
            font-weight: 900;
            color: #fff;
            line-height: 1.15;
        }
        .lbl-calibre {
            font-size: 12px;
            font-weight: 900;
            color: #F58220;
            line-height: 1.2;
        }

        .lbl-accent { height: 2px; background-color: #F58220; }

        .lbl-header-tbl td.lbl-badge-td { text-align: right; white-space: nowrap; }
        .badge-dev {
            padding: 0.8mm 2.5mm;
            border-radius: 3px;
            font-size: 8px;
            font-weight: 900;
            text-transform: uppercase;
            color: #92400e;
            background-color: #fef3c7;
            border: 0.5px solid #f59e0b;
        }
        .badge-tested {
            padding: 0.8mm 2.5mm;
            border-radius: 3px;
            font-size: 8px;
            font-weight: 900;
            text-transform: uppercase;
            color: #1e40af;
            background-color: #dbeafe;
            border: 0.5px solid #3b82f6;
        }
        .badge-approved {
            padding: 0.8mm 2.5mm;
            border-radius: 3px;
            font-size: 8px;
            font-weight: 900;
            text-transform: uppercase;
            color: #065f46;
            background-color: #d1fae5;
            border: 0.5px solid #10b981;
        }
        .badge-fav {
            font-size: 10px;
            color: #F58220;
            font-weight: 900;
        }

        .lbl-body { padding: 1.5mm 2.5mm 1mm 2.5mm; }

        .lbl-fields { width: 100%; border-collapse: collapse; }
        .lbl-fields td {
            width: 50%;
            padding: 0.3mm 0;
            vertical-align: top;
        }
        .lbl-fields td.left-cell { padding-right: 1.5mm; }
        .lbl-fields td.right-cell { padding-left: 1.5mm; }
        .lbl-k {
            font-size: 5px;
            text-transform: uppercase;
            letter-spacing: 0.3px;
            color: #0B4EA2;
            font-weight: bold;
        }
        .lbl-v {
            font-size: 7px;
            color: #111827;
            font-weight: bold;
            line-height: 1.3;
        }

        .lbl-footer {
            background-color: #0B4EA2;
            padding: 0.8mm 2.5mm;
        }
        .lbl-footer-tbl { width: 100%; }
        .lbl-footer-tbl td { color: #fff; font-size: 9px; font-weight: 900; vertical-align: middle; }
        .lbl-footer-tbl .right { text-align: right; color: #fff; font-style: italic; font-weight: 900; }

        /* ══════════════════════════════════════ */
        /* LARGE LABEL                            */
        /* ══════════════════════════════════════ */
        .label-large {
            border: 1.5px solid #d1d5db;
            border-radius: 8px;
            overflow: hidden;
            page-break-inside: avoid;
        }

        .lg-header {
            background-color: #0B4EA2;
            min-height: 13.2mm; /* 20% more than logo height (11mm * 1.2) */
            padding: 1.1mm 5mm;
        }
        .lg-header-tbl { width: 100%; }
        .lg-header-tbl td { vertical-align: middle; font-weight: 700; }
        .lg-logo-td { width: 14mm; }
        .lg-logo {
            width: 11mm;
            height: 11mm;
            background: #fff;
            border-radius: 3px;
            text-align: center;
            padding: 0.5mm;
        }
        .lg-logo img { width: 10mm; height: 10mm; }
        .lg-name {
            font-size: 22px;
            font-weight: 900;
            color: #fff;
            line-height: 1.15;
        }
        .lg-calibre {
            font-size: 16px;
            font-weight: 900;
            color: #F58220;
            line-height: 1.3;
        }

        .lg-accent { height: 3px; background-color: #F58220; }

        .lg-header-tbl td.lg-badge-td { text-align: right; white-space: nowrap; }
        .lg-badge-dev {
            padding: 1.2mm 4mm;
            border-radius: 4px;
            font-size: 12px;
            font-weight: 900;
            text-transform: uppercase;
            color: #92400e;
            background-color: #fef3c7;
            border: 1px solid #f59e0b;
        }
        .lg-badge-tested {
            padding: 1.2mm 4mm;
            border-radius: 4px;
            font-size: 12px;
            font-weight: 900;
            text-transform: uppercase;
            color: #1e40af;
            background-color: #dbeafe;
            border: 1px solid #3b82f6;
        }
        .lg-badge-approved {
            padding: 1.2mm 4mm;
            border-radius: 4px;
            font-size: 12px;
            font-weight: 900;
            text-transform: uppercase;
            color: #065f46;
            background-color: #d1fae5;
            border: 1px solid #10b981;
        }
        .lg-badge-fav {
            font-size: 14px;
            color: #F58220;
            font-weight: 900;
        }

        .lg-body { padding: 3mm 5mm 2mm 5mm; }

        .lg-fields { width: 100%; border-collapse: collapse; }
        .lg-fields td {
            width: 50%;
            padding: 1mm 0;
            vertical-align: top;
            border-bottom: 1px solid #f3f4f6;
        }
        .lg-fields td.left-cell { padding-right: 2.5mm; }
        .lg-fields td.right-cell { padding-left: 2.5mm; }
        .lg-k {
            font-size: 6.5px;
            text-transform: uppercase;
            letter-spacing: 0.4px;
            color: #0B4EA2;
            font-weight: bold;
        }
        .lg-v {
            font-size: 10px;
            color: #111827;
            font-weight: bold;
            line-height: 1.3;
        }

        .lg-footer {
            background-color: #0B4EA2;
            padding: 1.5mm 5mm;
        }
        .lg-footer-tbl { width: 100%; }
        .lg-footer-tbl td { color: #fff; font-size: 12px; font-weight: 900; vertical-align: middle; }
        .lg-footer-tbl .right { text-align: right; color: #fff; font-style: italic; font-weight: 900; }
    </style>
</head>
<body>
@php
    $logoPath = public_path('nrapa-logo.png');
    $logoDataUri = null;
    if (file_exists($logoPath)) {
        $logoDataUri = 'data:image/png;base64,' . base64_encode(file_get_contents($logoPath));
    }

    $statusClass = match($load->status) {
        'development' => 'dev',
        'tested' => 'tested',
        'approved' => 'approved',
        default => 'dev',
    };
    $statusLabel = match($load->status) {
        'development' => 'Development',
        'tested' => 'Tested',
        'approved' => 'Approved',
        default => ucfirst($load->status ?? 'Development'),
    };

    // Build a flat list of [label, value] fields, then pack them into 2-col rows
    $fields = [];
    if ($load->bullet_make || $load->bullet_model || $load->bullet_weight) {
        $fields[] = ['Bullet', trim($load->bullet_make . ' ' . $load->bullet_model . ' ' . ($load->bullet_weight ? $load->bullet_weight . 'gr' : ''))];
    }
    if ($load->powder_type || $load->powder_charge) {
        $v = trim(($load->powder_make ?? '') . ' ' . ($load->powder_type ?? '') . ' ' . ($load->powder_charge ? $load->powder_charge . 'gr' : ''));
        $fields[] = ['Powder', $v];
    }
    if ($load->primer_make || $load->primer_type) {
        $fields[] = ['Primer', trim($load->primer_make . ' ' . $load->primer_type)];
    }
    if ($load->brass_make) {
        $brassVal = $load->brass_make;
        if ($load->brass_firings) $brassVal .= ' (' . $load->brass_firings . 'x)';
        $fields[] = ['Brass', $brassVal];
    }
    if ($load->coal) {
        $fields[] = ['COAL', $load->coal . '"'];
    }
    if ($load->cbto) {
        $fields[] = ['CBTO', $load->cbto . '"'];
    }
    if ($load->muzzle_velocity) {
        $fields[] = ['Velocity', $load->muzzle_velocity . ' fps'];
    }
    if ($load->bullet_bc) {
        $bcLabel = 'BC' . ($load->bullet_bc_type ? ' (' . strtoupper($load->bullet_bc_type) . ')' : '');
        $fields[] = [$bcLabel, $load->bullet_bc];
    }
    if ($load->velocity_sd) {
        $fields[] = ['SD', $load->velocity_sd];
    }
    if ($load->velocity_es) {
        $fields[] = ['ES', $load->velocity_es];
    }
    if ($load->group_size) {
        $fields[] = ['Group Size', $load->group_size . ' ' . ($load->group_size_unit === 'moa' ? 'MOA' : '"')];
    }

    // Chunk into pairs for 2-column rows
    $fieldRows = array_chunk($fields, 2);
@endphp

@if($label_layout === '2x7')
    {{-- 2x7 GRID — 14 compact labels per page --}}
    @php $labelsPerPage = 14; $totalPages = ceil($label_count / $labelsPerPage); @endphp
    @for($page = 0; $page < $totalPages; $page++)
        <div class="page" @if($page < $totalPages - 1) style="page-break-after: always;" @endif>
            <table class="grid-2up">
                @for($row = 0; $row < 7; $row++)
                    @php $idx = $page * $labelsPerPage + $row * 2; @endphp
                    @if($idx < $label_count)
                        <tr>
                            @for($col = 0; $col < 2; $col++)
                                @php $labelIdx = $idx + $col; @endphp
                                <td>
                                    @if($labelIdx < $label_count)
                                        <div class="label-card">
                                            <div class="lbl-header">
                                                <table class="lbl-header-tbl" cellpadding="0" cellspacing="0"><tr>
                                                    <td class="lbl-logo-td">
                                                        <div class="lbl-logo">
                                                            @if($logoDataUri)
                                                                <img src="{{ $logoDataUri }}" alt="NRAPA">
                                                            @else
                                                                <span style="font-weight:bold;font-size:5px;color:#0B4EA2;">NRAPA</span>
                                                            @endif
                                                        </div>
                                                    </td>
                                                    <td>
                                                        <div class="lbl-name">{{ $load->name }}</div>
                                                        @if($load->calibre_name)
                                                            <div class="lbl-calibre">{{ $load->calibre_name }}</div>
                                                        @endif
                                                    </td>
                                                    <td style="text-align:right;white-space:nowrap;">
                                                        <span class="badge-{{ $statusClass }}">{{ $statusLabel }}</span>
                                                        @if($load->is_favorite)
                                                            <span class="badge-fav">&#9733;</span>
                                                        @endif
                                                        @if($load->is_max_load)
                                                            <br><span style="color:#fca5a5;font-size:5px;font-weight:bold;">&#9888; MAX</span>
                                                        @endif
                                                    </td>
                                                </tr></table>
                                            </div>
                                            <div class="lbl-accent"></div>
                                            <div class="lbl-body">
                                                <table class="lbl-fields" cellpadding="0" cellspacing="0">
                                                    @foreach($fieldRows as $pair)
                                                        <tr>
                                                            <td class="left-cell">
                                                                <div class="lbl-k">{{ $pair[0][0] }}</div>
                                                                <div class="lbl-v">{{ $pair[0][1] }}</div>
                                                            </td>
                                                            <td class="right-cell">
                                                                @if(isset($pair[1]))
                                                                    <div class="lbl-k">{{ $pair[1][0] }}</div>
                                                                    <div class="lbl-v">{{ $pair[1][1] }}</div>
                                                                @endif
                                                            </td>
                                                        </tr>
                                                    @endforeach
                                                </table>
                                            </div>
                                            <div class="lbl-footer">
                                                <table class="lbl-footer-tbl" cellpadding="0" cellspacing="0"><tr>
                                                    <td>{{ now()->format('d M Y') }}</td>
                                                    <td class="right">NRAPA Virtual Loading Bench</td>
                                                </tr></table>
                                            </div>
                                        </div>
                                    @endif
                                </td>
                            @endfor
                        </tr>
                    @endif
                @endfor
            </table>
        </div>
    @endfor
@else
    {{-- 2-UP LARGE LABELS (2 per row on A4) --}}
    @php $labelsPerPage = 2; $totalPages = ceil($label_count / $labelsPerPage); @endphp
    @for($page = 0; $page < $totalPages; $page++)
        <div class="page" @if($page < $totalPages - 1) style="page-break-after: always;" @endif>
            <table class="grid-2up">
                <tr>
                    @for($col = 0; $col < 2; $col++)
                        @php $labelIdx = $page * $labelsPerPage + $col; @endphp
                        <td>
                            @if($labelIdx < $label_count)
                                <div class="label-large">
                                    <div class="lg-header">
                                        <table class="lg-header-tbl" cellpadding="0" cellspacing="0"><tr>
                                            <td class="lg-logo-td">
                                                <div class="lg-logo">
                                                    @if($logoDataUri)
                                                        <img src="{{ $logoDataUri }}" alt="NRAPA">
                                                    @else
                                                        <span style="font-weight:bold;font-size:8px;color:#0B4EA2;">NRAPA</span>
                                                    @endif
                                                </div>
                                            </td>
                                            <td>
                                                <div class="lg-name">{{ $load->name }}</div>
                                                @if($load->calibre_name)
                                                    <div class="lg-calibre">{{ $load->calibre_name }}</div>
                                                @endif
                                            </td>
                                            <td style="text-align:right;white-space:nowrap;">
                                                <span class="lg-badge-{{ $statusClass }}">{{ $statusLabel }}</span>
                                                @if($load->is_favorite)
                                                    <span class="lg-badge-fav">&nbsp;&#9733;</span>
                                                @endif
                                                @if($load->is_max_load)
                                                    <br><span style="color:#fca5a5;font-size:7px;font-weight:bold;">&#9888; MAX LOAD</span>
                                                @endif
                                            </td>
                                        </tr></table>
                                    </div>
                                    <div class="lg-accent"></div>
                                    <div class="lg-body">
                                        <table class="lg-fields" cellpadding="0" cellspacing="0">
                                            @foreach($fieldRows as $pair)
                                                <tr>
                                                    <td class="left-cell">
                                                        <div class="lg-k">{{ $pair[0][0] }}</div>
                                                        <div class="lg-v">{{ $pair[0][1] }}</div>
                                                    </td>
                                                    <td class="right-cell">
                                                        @if(isset($pair[1]))
                                                            <div class="lg-k">{{ $pair[1][0] }}</div>
                                                            <div class="lg-v">{{ $pair[1][1] }}</div>
                                                        @endif
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </table>
                                    </div>
                                    <div class="lg-footer">
                                        <table class="lg-footer-tbl" cellpadding="0" cellspacing="0"><tr>
                                            <td>{{ now()->format('d M Y') }}</td>
                                            <td class="right">NRAPA Virtual Loading Bench</td>
                                        </tr></table>
                                    </div>
                                </div>
                            @endif
                        </td>
                    @endfor
                </tr>
            </table>
        </div>
    @endfor
@endif
</body>
</html>

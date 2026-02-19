<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: ui-sans-serif, system-ui, -apple-system, Segoe UI, Roboto, Helvetica, Arial, sans-serif;
            font-size: 9px;
            color: #111827;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }

        @page { size: A4 portrait; margin: 0; }
        @media print { body { background: #fff !important; } }

        .page { width: 210mm; min-height: 297mm; padding: 6mm; }

        .grid-2up {
            display: table;
            width: 100%;
            border-collapse: separate;
            border-spacing: 2mm;
        }
        .grid-2up-row { display: table-row; }
        .grid-2up-cell { display: table-cell; width: 50%; vertical-align: top; }

        .label-card {
            border: 1px solid #E5E7EB;
            border-radius: 6px;
            overflow: hidden;
            height: 36mm;
            position: relative;
            page-break-inside: avoid;
        }

        .lbl-header {
            background-color: #0B4EA2;
            padding: 1.5mm 2.5mm;
        }
        .lbl-header-inner {
            display: table;
            width: 100%;
        }
        .lbl-logo-cell {
            display: table-cell;
            width: 6mm;
            vertical-align: middle;
            padding-right: 1.5mm;
        }
        .lbl-logo {
            width: 6mm;
            height: 6mm;
            background: #fff;
            border-radius: 2px;
            text-align: center;
            overflow: hidden;
        }
        .lbl-logo img { width: 5mm; height: 5mm; }
        .lbl-logo-fallback { font-weight: 800; font-size: 4px; color: #0B4EA2; line-height: 6mm; }
        .lbl-title-cell {
            display: table-cell;
            vertical-align: middle;
        }
        .lbl-name {
            font-size: 7.5px;
            font-weight: 700;
            color: #fff;
            line-height: 1.15;
        }
        .lbl-step {
            font-size: 10px;
            font-weight: 800;
            color: #F58220;
            line-height: 1.2;
        }

        .lbl-accent {
            height: 1.5px;
            background-color: #F58220;
        }

        .lbl-body { padding: 1.5mm 2.5mm 1mm 2.5mm; }

        .lbl-detail {
            font-size: 7px;
            margin-bottom: 0.5mm;
        }
        .lbl-detail-k {
            font-size: 5.5px;
            text-transform: uppercase;
            letter-spacing: 0.3px;
            color: #0B4EA2;
            font-weight: 700;
        }
        .lbl-detail-v {
            font-size: 7px;
            font-weight: 600;
            color: #111827;
        }

        .vel-lines {
            margin-top: 1mm;
            border-top: 1px solid #E5E7EB;
            padding-top: 0.5mm;
        }
        .vel-line {
            border-bottom: 1px dotted #d1d5db;
            height: 3.5mm;
            font-size: 6px;
            color: #6B7280;
            line-height: 3.5mm;
        }

        .lbl-footer {
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            background-color: #0B4EA2;
            padding: 0.5mm 2.5mm;
            text-align: center;
            color: #ccc;
            font-size: 4.5px;
            font-weight: 600;
            font-style: italic;
        }
    </style>
</head>
<body>
@php
    $labels = $steps;
    $labelsPerPage = 14;
    $totalPages = ceil($labels->count() / $labelsPerPage);
    $logoUrl = \App\Helpers\DocumentDataHelper::getLogoUrl();
@endphp

@for($page = 0; $page < $totalPages; $page++)
    <div class="page" @if($page < $totalPages - 1) style="page-break-after: always;" @endif>
        <div class="grid-2up">
            @for($row = 0; $row < 7; $row++)
                @php $idx = $page * $labelsPerPage + $row * 2; @endphp
                @if($idx < $labels->count())
                    <div class="grid-2up-row">
                        @for($col = 0; $col < 2; $col++)
                            @php $labelIdx = $idx + $col; $step = $labels->values()->get($labelIdx); @endphp
                            <div class="grid-2up-cell">
                                @if($step)
                                    <div class="label-card">
                                        <div class="lbl-header">
                                            <div class="lbl-header-inner">
                                                <div class="lbl-logo-cell">
                                                    <div class="lbl-logo">
                                                        @if($logoUrl)
                                                            <img src="{{ $logoUrl }}" alt="NRAPA">
                                                        @else
                                                            <span class="lbl-logo-fallback">NRAPA</span>
                                                        @endif
                                                    </div>
                                                </div>
                                                <div class="lbl-title-cell">
                                                    <div class="lbl-name">{{ $test->name }}</div>
                                                    <div class="lbl-step">Step {{ $step->step_number }} &mdash; {{ rtrim(rtrim($step->charge_weight, '0'), '.') }}{{ $test->unit_label }}</div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="lbl-accent"></div>
                                        <div class="lbl-body">
                                            @if($test->bullet_make || $test->bullet_weight)
                                                <div class="lbl-detail">
                                                    <span class="lbl-detail-k">Bullet: </span>
                                                    <span class="lbl-detail-v">{{ $test->bullet_make }} {{ $test->bullet_weight ? $test->bullet_weight . 'gr' : '' }} {{ $test->bullet_type }}</span>
                                                </div>
                                            @endif
                                            @if($test->primer_type)
                                                <div class="lbl-detail">
                                                    <span class="lbl-detail-k">Primer: </span>
                                                    <span class="lbl-detail-v">{{ $test->primer_type }}</span>
                                                </div>
                                            @endif

                                            <div class="vel-lines">
                                                @for($v = 0; $v < min($test->rounds_per_step, 5); $v++)
                                                    <div class="vel-line">V{{ $v + 1 }}: ________</div>
                                                @endfor
                                            </div>
                                        </div>
                                        <div class="lbl-footer">NRAPA Virtual Loading Bench</div>
                                    </div>
                                @endif
                            </div>
                        @endfor
                    </div>
                @endif
            @endfor
        </div>
    </div>
@endfor
</body>
</html>

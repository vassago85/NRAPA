<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; font-size: 9px; color: #1a1a1a; }

        @page { margin: 5mm; }
        @media print {
            body { background: #fff !important; }
        }

        .page { width: 210mm; min-height: 297mm; padding: 5mm; }

        /* ── 2x7 grid layout ── */
        .grid-2x7 { display: table; width: 100%; border-collapse: separate; border-spacing: 2mm; }
        .grid-row { display: table-row; }
        .label-cell { display: table-cell; width: 50%; vertical-align: top; }

        .label {
            border: 1px solid #d1d5db;
            border-radius: 4px;
            padding: 2mm 2.5mm 1.5mm 2.5mm;
            height: 36mm;
            overflow: hidden;
            position: relative;
            page-break-inside: avoid;
        }

        /* Small label: header row */
        .sm-header {
            display: flex;
            align-items: center;
            gap: 1.5mm;
            margin-bottom: 1mm;
            padding-bottom: 1mm;
            border-bottom: 0.5px solid #e5e7eb;
        }
        .sm-logo { height: 5mm; width: auto; }
        .sm-title-block { flex: 1; min-width: 0; }
        .sm-load-name { font-size: 9px; font-weight: 800; color: #0B4EA2; line-height: 1.15; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .sm-calibre { font-size: 8px; font-weight: 700; color: #F58220; line-height: 1.15; }

        /* Small label: 2-col data grid */
        .sm-data { display: table; width: 100%; border-collapse: collapse; }
        .sm-data-row { display: table-row; }
        .sm-data-cell { display: table-cell; width: 50%; padding: 0.2mm 0; vertical-align: top; }
        .sm-data-cell:first-child { padding-right: 1.5mm; }
        .sm-data-cell:last-child { padding-left: 1.5mm; }
        .sm-lbl { font-weight: 700; font-size: 6.5px; color: #6b7280; }
        .sm-val { font-size: 7.5px; color: #111827; font-weight: 500; line-height: 1.3; }

        /* Small label: footer */
        .sm-footer {
            position: absolute;
            bottom: 1.5mm;
            left: 2.5mm;
            right: 2.5mm;
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
        }
        .sm-date { font-size: 5.5px; color: #9ca3af; }
        .sm-branding { font-size: 5px; color: #9ca3af; text-align: right; font-style: italic; }

        /* ── Single large label ── */
        .label-single {
            border: 1.5px solid #d1d5db;
            border-radius: 8px;
            padding: 7mm 8mm 6mm 8mm;
            margin: 0 auto 5mm auto;
            max-width: 140mm;
            page-break-inside: avoid;
            overflow: hidden;
        }

        .single-header {
            display: flex;
            align-items: center;
            gap: 4mm;
            margin-bottom: 3mm;
            padding-bottom: 3mm;
            border-bottom: 1.5px solid #e5e7eb;
        }
        .single-logo { height: 12mm; width: auto; }
        .single-title-block { flex: 1; }
        .single-load-name { font-size: 18px; font-weight: 800; color: #0B4EA2; line-height: 1.15; }
        .single-calibre { font-size: 14px; font-weight: 700; color: #F58220; line-height: 1.3; }

        /* Single label: 2-col data grid */
        .data-grid { display: table; width: 100%; border-collapse: collapse; margin-top: 1mm; }
        .data-row { display: table-row; }
        .data-cell {
            display: table-cell;
            width: 50%;
            padding: 1.5mm 0;
            vertical-align: top;
            border-bottom: 0.5px solid #f3f4f6;
        }
        .data-cell:first-child { padding-right: 3mm; }
        .data-cell:last-child { padding-left: 3mm; }
        .data-lbl { font-size: 8px; font-weight: 700; color: #6b7280; text-transform: uppercase; letter-spacing: 0.3px; margin-bottom: 0.3mm; }
        .data-val { font-size: 11px; color: #111827; font-weight: 600; line-height: 1.3; }

        .single-footer {
            margin-top: 4mm;
            padding-top: 3mm;
            border-top: 1.5px solid #e5e7eb;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .single-date { font-size: 9px; color: #9ca3af; }
        .single-branding { font-size: 8px; color: #0B4EA2; font-weight: 600; font-style: italic; }

        .max-load-warning {
            margin-top: 3mm;
            padding: 2mm 3mm;
            border: 1.5px solid #dc2626;
            border-radius: 4px;
            color: #dc2626;
            font-size: 11px;
            font-weight: 700;
            text-align: center;
        }
    </style>
</head>
<body>
@if($label_layout === '2x7')
    {{-- ═══════════════════════════════════════ --}}
    {{--  2x7 GRID LAYOUT — 14 labels per page  --}}
    {{-- ═══════════════════════════════════════ --}}
    @php $labelsPerPage = 14; $totalPages = ceil($label_count / $labelsPerPage); @endphp
    @for($page = 0; $page < $totalPages; $page++)
        <div class="page" @if($page < $totalPages - 1) style="page-break-after: always;" @endif>
            <div class="grid-2x7">
                @for($row = 0; $row < 7; $row++)
                    @php $idx = $page * $labelsPerPage + $row * 2; @endphp
                    @if($idx < $label_count)
                        <div class="grid-row">
                            @for($col = 0; $col < 2; $col++)
                                @php $labelIdx = $idx + $col; @endphp
                                <div class="label-cell">
                                    @if($labelIdx < $label_count)
                                        <div class="label">
                                            {{-- Header: logo + name + calibre --}}
                                            <div class="sm-header">
                                                @if(file_exists(public_path('nrapa-logo.png')))
                                                    <img src="{{ public_path('nrapa-logo.png') }}" class="sm-logo">
                                                @else
                                                    <span style="font-size: 7px; font-weight: 800; color: #0B4EA2;">NRAPA</span>
                                                @endif
                                                <div class="sm-title-block">
                                                    <div class="sm-load-name">{{ $load->name }}</div>
                                                    @if($load->calibre_name)
                                                        <div class="sm-calibre">{{ $load->calibre_name }}</div>
                                                    @endif
                                                </div>
                                            </div>

                                            {{-- Data: 2-column grid --}}
                                            <div class="sm-data">
                                                @if($load->bullet_make || $load->bullet_weight)
                                                <div class="sm-data-row">
                                                    <div class="sm-data-cell">
                                                        <span class="sm-lbl">Bullet: </span>
                                                        <span class="sm-val">{{ $load->bullet_make }} {{ $load->bullet_weight ? $load->bullet_weight . 'gr' : '' }} {{ $load->bullet_type }}</span>
                                                    </div>
                                                    <div class="sm-data-cell">
                                                        @if($load->powder_type || $load->powder_charge)
                                                        <span class="sm-lbl">Powder: </span>
                                                        <span class="sm-val">{{ $load->powder_type }} {{ $load->powder_charge ? $load->powder_charge . 'gr' : '' }}</span>
                                                        @endif
                                                    </div>
                                                </div>
                                                @endif

                                                <div class="sm-data-row">
                                                    <div class="sm-data-cell">
                                                        @if($load->primer_make || $load->primer_type)
                                                        <span class="sm-lbl">Primer: </span>
                                                        <span class="sm-val">{{ $load->primer_make }} {{ $load->primer_type }}</span>
                                                        @endif
                                                    </div>
                                                    <div class="sm-data-cell">
                                                        @if($load->brass_make)
                                                        <span class="sm-lbl">Brass: </span>
                                                        <span class="sm-val">{{ $load->brass_make }}@if($load->brass_firings) ({{ $load->brass_firings }}x)@endif</span>
                                                        @endif
                                                    </div>
                                                </div>

                                                <div class="sm-data-row">
                                                    <div class="sm-data-cell">
                                                        @if($load->coal)
                                                        <span class="sm-lbl">COAL: </span>
                                                        <span class="sm-val">{{ $load->coal }}"</span>
                                                        @endif
                                                    </div>
                                                    <div class="sm-data-cell">
                                                        @if($load->cbto)
                                                        <span class="sm-lbl">CBTO: </span>
                                                        <span class="sm-val">{{ $load->cbto }}"</span>
                                                        @elseif($load->muzzle_velocity)
                                                        <span class="sm-lbl">Vel: </span>
                                                        <span class="sm-val">{{ $load->muzzle_velocity }}fps</span>
                                                        @endif
                                                    </div>
                                                </div>

                                                @if($load->cbto && $load->muzzle_velocity)
                                                <div class="sm-data-row">
                                                    <div class="sm-data-cell">
                                                        <span class="sm-lbl">Vel: </span>
                                                        <span class="sm-val">{{ $load->muzzle_velocity }}fps</span>
                                                    </div>
                                                    <div class="sm-data-cell">
                                                        @if($load->velocity_sd)
                                                        <span class="sm-lbl">SD: </span>
                                                        <span class="sm-val">{{ $load->velocity_sd }}</span>
                                                        @endif
                                                    </div>
                                                </div>
                                                @endif
                                            </div>

                                            {{-- Footer --}}
                                            <div class="sm-footer">
                                                <div class="sm-date">{{ now()->format('d M Y') }}</div>
                                                <div class="sm-branding">NRAPA Virtual Loading Bench</div>
                                            </div>
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
@else
    {{-- ════════════════════════════════ --}}
    {{--  SINGLE LARGE LABEL layout      --}}
    {{-- ════════════════════════════════ --}}
    @for($i = 0; $i < $label_count; $i++)
        <div class="page" @if($i < $label_count - 1) style="page-break-after: always;" @endif>
            <div class="label-single">
                {{-- Header: Logo + Load name + Calibre --}}
                <div class="single-header">
                    @if(file_exists(public_path('nrapa-logo.png')))
                        <img src="{{ public_path('nrapa-logo.png') }}" class="single-logo">
                    @else
                        <span style="font-size: 18px; font-weight: 800; color: #0B4EA2;">NRAPA</span>
                    @endif
                    <div class="single-title-block">
                        <div class="single-load-name">{{ $load->name }}</div>
                        @if($load->calibre_name)
                            <div class="single-calibre">{{ $load->calibre_name }}</div>
                        @endif
                    </div>
                </div>

                {{-- Data: 2-column grid --}}
                <div class="data-grid">
                    {{-- Row 1: Bullet | Powder --}}
                    @if($load->bullet_make || $load->bullet_weight || $load->powder_type || $load->powder_charge)
                    <div class="data-row">
                        <div class="data-cell">
                            <div class="data-lbl">Bullet</div>
                            <div class="data-val">{{ $load->bullet_make }} {{ $load->bullet_model }} {{ $load->bullet_weight ? $load->bullet_weight . 'gr' : '' }} {{ $load->bullet_type }}</div>
                        </div>
                        <div class="data-cell">
                            <div class="data-lbl">Powder</div>
                            <div class="data-val">{{ $load->powder_make }} {{ $load->powder_type }} {{ $load->powder_charge ? $load->powder_charge . 'gr' : '' }}</div>
                        </div>
                    </div>
                    @endif

                    {{-- Row 2: Primer | Brass --}}
                    @if($load->primer_make || $load->primer_type || $load->brass_make)
                    <div class="data-row">
                        <div class="data-cell">
                            <div class="data-lbl">Primer</div>
                            <div class="data-val">{{ $load->primer_make }} {{ $load->primer_type }}</div>
                        </div>
                        <div class="data-cell">
                            @if($load->brass_make)
                            <div class="data-lbl">Brass</div>
                            <div class="data-val">{{ $load->brass_make }}@if($load->brass_firings) ({{ $load->brass_firings }}x fired)@endif</div>
                            @endif
                        </div>
                    </div>
                    @endif

                    {{-- Row 3: COAL | CBTO --}}
                    @if($load->coal || $load->cbto)
                    <div class="data-row">
                        <div class="data-cell">
                            @if($load->coal)
                            <div class="data-lbl">COAL</div>
                            <div class="data-val">{{ $load->coal }}"</div>
                            @endif
                        </div>
                        <div class="data-cell">
                            @if($load->cbto)
                            <div class="data-lbl">CBTO</div>
                            <div class="data-val">{{ $load->cbto }}"</div>
                            @endif
                        </div>
                    </div>
                    @endif

                    {{-- Row 4: Velocity | SD / ES --}}
                    @if($load->muzzle_velocity || $load->velocity_sd || $load->velocity_es)
                    <div class="data-row">
                        <div class="data-cell">
                            @if($load->muzzle_velocity)
                            <div class="data-lbl">Velocity</div>
                            <div class="data-val">{{ $load->muzzle_velocity }} fps</div>
                            @endif
                        </div>
                        <div class="data-cell">
                            @if($load->velocity_sd || $load->velocity_es)
                            <div class="data-lbl">SD / ES</div>
                            <div class="data-val">
                                @if($load->velocity_sd){{ $load->velocity_sd }}@endif
                                @if($load->velocity_sd && $load->velocity_es) / @endif
                                @if($load->velocity_es){{ $load->velocity_es }}@endif
                            </div>
                            @endif
                        </div>
                    </div>
                    @endif

                    {{-- Row 5: Group Size (optional, spans if alone) --}}
                    @if($load->group_size)
                    <div class="data-row">
                        <div class="data-cell">
                            <div class="data-lbl">Group Size</div>
                            <div class="data-val">{{ $load->group_size }} {{ $load->group_size_unit === 'moa' ? 'MOA' : '"' }}</div>
                        </div>
                        <div class="data-cell"></div>
                    </div>
                    @endif
                </div>

                {{-- Max load warning --}}
                @if($load->is_max_load)
                    <div class="max-load-warning">&#9888; WARNING: MAXIMUM LOAD</div>
                @endif

                {{-- Footer: date + branding --}}
                <div class="single-footer">
                    <div class="single-date">{{ now()->format('d M Y') }}</div>
                    <div class="single-branding">Generated by NRAPA Virtual Loading Bench</div>
                </div>
            </div>
        </div>
    @endfor
@endif
</body>
</html>

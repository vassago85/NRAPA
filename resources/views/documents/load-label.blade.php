<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; font-size: 9px; color: #1a1a1a; }

        .page { width: 210mm; min-height: 297mm; padding: 5mm; }

        /* 2x7 grid layout */
        .grid-2x7 { display: table; width: 100%; border-collapse: separate; border-spacing: 2mm; }
        .grid-row { display: table-row; }
        .label-cell { display: table-cell; width: 50%; vertical-align: top; }

        .label {
            border: 1px solid #ccc;
            border-radius: 3px;
            padding: 3mm;
            height: 36mm;
            position: relative;
            page-break-inside: avoid;
        }

        .label-single {
            border: 1px solid #ccc;
            border-radius: 5px;
            padding: 8mm;
            margin-bottom: 5mm;
            page-break-inside: avoid;
        }

        .logo { height: 10mm; }
        .logo-small { height: 6mm; }
        .label-header { display: flex; align-items: center; margin-bottom: 2mm; }

        .load-name { font-size: 11px; font-weight: bold; color: #0B4EA2; margin-bottom: 1mm; }
        .calibre { font-size: 10px; font-weight: bold; color: #F58220; margin-bottom: 1mm; }

        .detail-row { margin-bottom: 0.5mm; }
        .detail-label { font-weight: bold; font-size: 8px; color: #555; display: inline; }
        .detail-value { font-size: 8px; display: inline; }

        .footer-text { font-size: 6px; color: #999; text-align: center; margin-top: 1mm; }
        .date-text { font-size: 7px; color: #777; }

        /* Single large label */
        .single-load-name { font-size: 16px; font-weight: bold; color: #0B4EA2; margin-bottom: 3mm; }
        .single-calibre { font-size: 14px; font-weight: bold; color: #F58220; margin-bottom: 3mm; }
        .single-detail { font-size: 11px; margin-bottom: 2mm; }
        .single-footer { font-size: 8px; color: #999; text-align: center; margin-top: 5mm; padding-top: 3mm; border-top: 1px solid #eee; }
    </style>
</head>
<body>
@if($label_layout === '2x7')
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
                                            <div style="text-align: center; margin-bottom: 1mm;">
                                                @if(file_exists(public_path('nrapa-logo.png')))
                                                    <img src="{{ public_path('nrapa-logo.png') }}" class="logo-small">
                                                @else
                                                    <span style="font-size: 8px; font-weight: bold; color: #0B4EA2;">NRAPA</span>
                                                @endif
                                            </div>
                                            <div class="load-name">{{ $load->name }}</div>
                                            @if($load->calibre_name)
                                                <div class="calibre">{{ $load->calibre_name }}</div>
                                            @endif
                                            @if($load->bullet_make || $load->bullet_weight)
                                                <div class="detail-row">
                                                    <span class="detail-label">Bullet:</span>
                                                    <span class="detail-value">{{ $load->bullet_make }} {{ $load->bullet_weight ? $load->bullet_weight . 'gr' : '' }} {{ $load->bullet_type }}</span>
                                                </div>
                                            @endif
                                            @if($load->powder_type || $load->powder_charge)
                                                <div class="detail-row">
                                                    <span class="detail-label">Powder:</span>
                                                    <span class="detail-value">{{ $load->powder_type }} {{ $load->powder_charge ? $load->powder_charge . 'gr' : '' }}</span>
                                                </div>
                                            @endif
                                            @if($load->primer_make || $load->primer_type)
                                                <div class="detail-row">
                                                    <span class="detail-label">Primer:</span>
                                                    <span class="detail-value">{{ $load->primer_make }} {{ $load->primer_type }}</span>
                                                </div>
                                            @endif
                                            @if($load->coal)
                                                <div class="detail-row">
                                                    <span class="detail-label">COAL:</span>
                                                    <span class="detail-value">{{ $load->coal }}"</span>
                                                    @if($load->cbto) <span class="detail-label" style="margin-left: 2mm;">CBTO:</span> <span class="detail-value">{{ $load->cbto }}"</span>@endif
                                                </div>
                                            @endif
                                            @if($load->muzzle_velocity || $load->velocity_sd)
                                                <div class="detail-row">
                                                    @if($load->muzzle_velocity)<span class="detail-label">Vel:</span> <span class="detail-value">{{ $load->muzzle_velocity }}fps</span>@endif
                                                    @if($load->velocity_sd) <span class="detail-label" style="margin-left: 2mm;">SD:</span> <span class="detail-value">{{ $load->velocity_sd }}</span>@endif
                                                </div>
                                            @endif
                                            <div class="date-text">{{ now()->format('d M Y') }}</div>
                                            <div class="footer-text">Generated by NRAPA Virtual Loading Bench</div>
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
    {{-- Single large label layout --}}
    @for($i = 0; $i < $label_count; $i++)
        <div class="page" @if($i < $label_count - 1) style="page-break-after: always;" @endif>
            <div class="label-single">
                <div style="text-align: center; margin-bottom: 5mm;">
                    @if(file_exists(public_path('nrapa-logo.png')))
                        <img src="{{ public_path('nrapa-logo.png') }}" class="logo">
                    @else
                        <span style="font-size: 16px; font-weight: bold; color: #0B4EA2;">NRAPA</span>
                    @endif
                </div>

                <div class="single-load-name">{{ $load->name }}</div>
                @if($load->calibre_name)
                    <div class="single-calibre">{{ $load->calibre_name }}</div>
                @endif

                @if($load->bullet_make || $load->bullet_weight)
                    <div class="single-detail"><strong>Bullet:</strong> {{ $load->bullet_make }} {{ $load->bullet_model }} {{ $load->bullet_weight ? $load->bullet_weight . 'gr' : '' }} {{ $load->bullet_type }}</div>
                @endif
                @if($load->powder_type || $load->powder_charge)
                    <div class="single-detail"><strong>Powder:</strong> {{ $load->powder_make }} {{ $load->powder_type }} {{ $load->powder_charge ? $load->powder_charge . 'gr' : '' }}</div>
                @endif
                @if($load->primer_make || $load->primer_type)
                    <div class="single-detail"><strong>Primer:</strong> {{ $load->primer_make }} {{ $load->primer_type }}</div>
                @endif
                @if($load->brass_make)
                    <div class="single-detail"><strong>Brass:</strong> {{ $load->brass_make }}@if($load->brass_firings) ({{ $load->brass_firings }}x fired)@endif</div>
                @endif
                @if($load->coal)
                    <div class="single-detail"><strong>COAL:</strong> {{ $load->coal }}"@if($load->cbto) &nbsp; <strong>CBTO:</strong> {{ $load->cbto }}"@endif</div>
                @endif
                @if($load->muzzle_velocity)
                    <div class="single-detail"><strong>Velocity:</strong> {{ $load->muzzle_velocity }} fps @if($load->velocity_sd) &nbsp; <strong>SD:</strong> {{ $load->velocity_sd }} @endif @if($load->velocity_es) &nbsp; <strong>ES:</strong> {{ $load->velocity_es }} @endif</div>
                @endif
                @if($load->group_size)
                    <div class="single-detail"><strong>Group:</strong> {{ $load->group_size }} {{ $load->group_size_unit === 'moa' ? 'MOA' : '"' }}</div>
                @endif

                <div class="single-detail" style="margin-top: 5mm;"><strong>Date:</strong> {{ now()->format('d M Y') }}</div>

                @if($load->is_max_load)
                    <div style="margin-top: 3mm; padding: 2mm; border: 1px solid #dc2626; border-radius: 3px; color: #dc2626; font-size: 10px; font-weight: bold; text-align: center;">
                        WARNING: MAXIMUM LOAD
                    </div>
                @endif

                <div class="single-footer">Generated by NRAPA Virtual Loading Bench</div>
            </div>
        </div>
    @endfor
@endif
</body>
</html>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SAPS 271 Firearm Description</title>
    <style>
        @page {
            margin: 20mm;
            size: A4;
        }
        body {
            font-family: 'Times New Roman', serif;
            font-size: 11pt;
            line-height: 1.4;
            color: #000;
        }
        .saps-form {
            border: 2px solid #000;
            padding: 15mm;
        }
        .section-header {
            font-weight: bold;
            font-size: 12pt;
            margin-bottom: 8mm;
            border-bottom: 1px solid #000;
            padding-bottom: 2mm;
        }
        .field-row {
            display: flex;
            margin-bottom: 4mm;
            align-items: flex-start;
        }
        .field-label {
            width: 50mm;
            font-weight: bold;
            flex-shrink: 0;
        }
        .field-value {
            flex: 1;
            border-bottom: 1px solid #000;
            min-height: 5mm;
            padding-left: 2mm;
        }
        .field-value-full {
            width: 100%;
            border-bottom: 1px solid #000;
            min-height: 5mm;
            margin-top: 2mm;
        }
        .serial-section {
            margin-top: 6mm;
            border-top: 1px solid #ccc;
            padding-top: 4mm;
        }
        .serial-item {
            margin-bottom: 3mm;
        }
        @media print {
            body {
                margin: 0;
            }
            .no-print {
                display: none;
            }
        }
    </style>
</head>
<body>
    <div class="saps-form">
        <div class="section-header">SECTION E: DESCRIPTION OF FIREARM</div>

        {{-- 1. Type of Firearm --}}
        <div class="field-row">
            <div class="field-label">1. Type of Firearm:</div>
            <div class="field-value">
                @if($firearm->firearm_type === 'other')
                    {{ $firearm->firearm_type_other }}
                @else
                    {{ ucfirst(str_replace('_', ' ', $firearm->firearm_type)) }}
                @endif
            </div>
        </div>

        {{-- 1.1 Action --}}
        <div class="field-row">
            <div class="field-label">1.1 Action:</div>
            <div class="field-value">
                @if($firearm->action_type === 'other')
                    {{ $firearm->action_type_other }}
                @else
                    {{ ucfirst(str_replace('_', ' ', $firearm->action_type)) }}
                @endif
            </div>
        </div>

        {{-- 1.2 Names and addresses engraved --}}
        <div style="margin-top: 4mm;">
            <div class="field-label" style="font-weight: bold;">1.2 Names and addresses engraved in the metal:</div>
            <div class="field-value-full">
                {{ $firearm->engraved_text ?? 'N/A' }}
            </div>
        </div>

        {{-- 1.3 Calibre --}}
        <div class="field-row">
            <div class="field-label">1.3 Calibre:</div>
            <div class="field-value">
                @if($firearm->firearmCalibre)
                    {{ $firearm->firearmCalibre->name }}
                @elseif($firearm->calibre_text_override)
                    {{ $firearm->calibre_text_override }}
                @elseif($firearm->calibre_manual)
                    {{ $firearm->calibre_manual }}
                @else
                    N/A
                @endif
            </div>
        </div>

        {{-- 1.4 Calibre Code --}}
        <div class="field-row">
            <div class="field-label">1.4 Calibre Code:</div>
            <div class="field-value">
                {{ $firearm->calibre_code ?? 'N/A' }}
            </div>
        </div>

        {{-- 1.5 Make --}}
        <div class="field-row">
            <div class="field-label">1.5 Make:</div>
            <div class="field-value">
                @if($firearm->firearmMake)
                    {{ $firearm->firearmMake->name }}
                @elseif($firearm->make_text_override)
                    {{ $firearm->make_text_override }}
                @elseif($firearm->make)
                    {{ $firearm->make }}
                @else
                    N/A
                @endif
            </div>
        </div>

        {{-- 1.6 Model --}}
        <div class="field-row">
            <div class="field-label">1.6 Model:</div>
            <div class="field-value">
                @if($firearm->firearmModel)
                    {{ $firearm->firearmModel->name }}
                @elseif($firearm->model_text_override)
                    {{ $firearm->model_text_override }}
                @elseif($firearm->model)
                    {{ $firearm->model }}
                @else
                    N/A
                @endif
            </div>
        </div>

        {{-- Serial Numbers Section --}}
        <div class="serial-section">
            <div style="font-weight: bold; margin-bottom: 3mm;">Serial Numbers:</div>

            {{-- 1.7 Barrel Serial Number --}}
            @if($firearm->barrel_serial_number)
                <div class="serial-item">
                    <div class="field-row">
                        <div class="field-label">1.7 Barrel Serial Number:</div>
                        <div class="field-value">{{ $firearm->barrel_serial_number }}</div>
                    </div>
                    @if($firearm->barrel_make_text)
                        <div class="field-row">
                            <div class="field-label">1.8 Barrel Make:</div>
                            <div class="field-value">{{ $firearm->barrel_make_text }}</div>
                        </div>
                    @endif
                </div>
            @endif

            {{-- 1.9 Frame Serial Number --}}
            @if($firearm->frame_serial_number)
                <div class="serial-item">
                    <div class="field-row">
                        <div class="field-label">1.9 Frame Serial Number:</div>
                        <div class="field-value">{{ $firearm->frame_serial_number }}</div>
                    </div>
                    @if($firearm->frame_make_text)
                        <div class="field-row">
                            <div class="field-label">1.10 Frame Make:</div>
                            <div class="field-value">{{ $firearm->frame_make_text }}</div>
                        </div>
                    @endif
                </div>
            @endif

            {{-- 1.11 Receiver Serial Number --}}
            @if($firearm->receiver_serial_number)
                <div class="serial-item">
                    <div class="field-row">
                        <div class="field-label">1.11 Receiver Serial Number:</div>
                        <div class="field-value">{{ $firearm->receiver_serial_number }}</div>
                    </div>
                    @if($firearm->receiver_make_text)
                        <div class="field-row">
                            <div class="field-label">1.12 Receiver Make:</div>
                            <div class="field-value">{{ $firearm->receiver_make_text }}</div>
                        </div>
                    @endif
                </div>
            @endif

            {{-- Legacy Serial Number (if no component serials) --}}
            @if(!$firearm->barrel_serial_number && !$firearm->frame_serial_number && !$firearm->receiver_serial_number && $firearm->serial_number)
                <div class="serial-item">
                    <div class="field-row">
                        <div class="field-label">Serial Number:</div>
                        <div class="field-value">{{ $firearm->serial_number }}</div>
                    </div>
                </div>
            @endif
        </div>
    </div>

    <div class="no-print" style="margin-top: 20px; text-align: center;">
        <button onclick="window.print()" class="px-4 py-2 bg-emerald-600 text-white rounded-lg">
            Print SAPS 271 Form
        </button>
    </div>
</body>
</html>

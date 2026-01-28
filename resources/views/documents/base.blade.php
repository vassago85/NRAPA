<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title ?? 'NRAPA Document' }}</title>
    <style>
        @include('documents.css')
    </style>
    @stack('document-styles')
</head>
<body>
    {{-- Optional preview controls (hide on print) --}}
    @if(!request()->has('print'))
    <div class="doc-controls">
        <button class="doc-btn" onclick="window.print()">Print</button>
    </div>
    @endif

    <div class="doc-sheet {{ $variant ?? '' }}">
        {{-- Background layer (optional) --}}
        @if(isset($background_image))
        <div class="doc-bg" style="--doc-bg: url('{{ $background_image }}');"></div>
        @else
        <div class="doc-bg"></div>
        @endif

        {{-- Watermark logo overlay --}}
        <div class="doc-watermark">
            @if(isset($logo_url))
                <img src="{{ $logo_url }}" alt="NRAPA Logo">
            @else
                {{-- Placeholder for NRAPA logo --}}
                <div style="width:140mm; height:140mm; display:flex; align-items:center; justify-content:center; color:rgba(11,19,32,.08); font-size:80pt; font-weight:bold;">NRAPA</div>
            @endif
        </div>

        {{-- Foreground content --}}
        <div class="doc-page">
            @yield('content')
        </div>
    </div>
</body>
</html>

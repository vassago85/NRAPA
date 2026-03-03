<meta charset="utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0" />

<title>{{ $title ?? config('app.name') }}</title>

<link rel="icon" href="/nrapa-icon.png" type="image/png">
<link rel="apple-touch-icon" href="/nrapa-icon.png">

<link rel="preconnect" href="https://fonts.bunny.net">
<link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600" rel="stylesheet" />

{{-- Alpine.js cloak style to prevent flash of unstyled content --}}
<style>[x-cloak] { display: none !important; }</style>

@vite(['resources/css/app.css', 'resources/js/app.js'])
@livewireStyles

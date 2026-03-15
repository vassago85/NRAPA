<!DOCTYPE html>
<html lang="en" class="antialiased">
<head>
    <meta charset="utf-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1"/>
    <title>Document Preview Gallery — NRAPA Developer</title>
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            font-family: 'Inter', ui-sans-serif, system-ui, -apple-system, sans-serif;
            background: #f4f5f7;
            color: #1e293b;
            min-height: 100vh;
            padding: 48px 24px;
        }

        .container {
            max-width: 880px;
            margin: 0 auto;
        }

        .back-link {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            font-size: 13px;
            color: #6b7280;
            text-decoration: none;
            margin-bottom: 24px;
            transition: color .15s;
        }
        .back-link:hover { color: #1f3f73; }
        .back-link svg { width: 16px; height: 16px; }

        .page-header {
            margin-bottom: 32px;
        }
        .badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 4px 10px;
            background: #fef2f2;
            border: 1px solid #fecaca;
            border-radius: 9999px;
            font-size: 11px;
            font-weight: 600;
            color: #dc2626;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            margin-bottom: 12px;
        }
        .badge svg { width: 14px; height: 14px; }

        h1 {
            font-size: 28px;
            font-weight: 800;
            color: #0f172a;
            letter-spacing: -0.02em;
        }
        .subtitle {
            margin-top: 6px;
            font-size: 15px;
            color: #64748b;
            line-height: 1.5;
        }

        .grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(260px, 1fr));
            gap: 16px;
        }

        .card {
            background: #fff;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            padding: 20px;
            text-decoration: none;
            color: inherit;
            transition: border-color .15s, box-shadow .15s, transform .1s;
            display: flex;
            flex-direction: column;
        }
        .card:hover {
            border-color: #1f3f73;
            box-shadow: 0 4px 12px rgba(31,63,115,.08);
            transform: translateY(-1px);
        }

        .card-icon {
            width: 40px;
            height: 40px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 14px;
            flex-shrink: 0;
        }
        .card-icon svg { width: 20px; height: 20px; }

        .card-icon.blue   { background: rgba(31,63,115,.08); color: #1f3f73; }
        .card-icon.orange { background: rgba(245,130,32,.08); color: #f58220; }
        .card-icon.green  { background: rgba(5,150,105,.08);  color: #059669; }

        .card-label {
            font-size: 15px;
            font-weight: 700;
            color: #0f172a;
            margin-bottom: 6px;
        }
        .card-desc {
            font-size: 13px;
            color: #64748b;
            line-height: 1.5;
            flex: 1;
        }
        .card-action {
            margin-top: 14px;
            font-size: 12px;
            font-weight: 600;
            color: #1f3f73;
            display: flex;
            align-items: center;
            gap: 4px;
        }
        .card-action svg { width: 14px; height: 14px; }
    </style>
</head>
<body>
    <div class="container">
        <a href="{{ route('developer.dashboard') }}" class="back-link">
            <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5 8.25 12l7.5-7.5"/></svg>
            Developer Dashboard
        </a>

        <div class="page-header">
            <div class="badge">
                <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9.75 3.104v5.714a2.25 2.25 0 0 1-.659 1.591L5 14.5M9.75 3.104c-.251.023-.501.05-.75.082m.75-.082a24.301 24.301 0 0 1 4.5 0m0 0v5.714c0 .597.237 1.17.659 1.591L19.8 15.3M14.25 3.104c.251.023.501.05.75.082M19.8 15.3l-1.57.393A9.065 9.065 0 0 1 12 15a9.065 9.065 0 0 0-6.23.693L5 14.5m14.8.8 1.402 1.402c1.232 1.232.65 3.318-1.067 3.611A48.309 48.309 0 0 1 12 21c-2.773 0-5.491-.235-8.135-.687-1.718-.293-2.3-2.379-1.067-3.61L5 14.5"/></svg>
                Developer Only
            </div>
            <h1>Document Preview Gallery</h1>
            <p class="subtitle">Preview all NRAPA document templates with sample data. Each link renders the document as it would appear when generated as a PDF.</p>
        </div>

        <div class="grid">
            @foreach ($types as $slug => $type)
            <a href="{{ route('developer.document-preview.show', $slug) }}" class="card" target="_blank">
                <div class="card-icon {{ match($slug) { 'endorsement' => 'orange', 'membership-card' => 'green', default => 'blue' } }}">
                    @switch($slug)
                        @case('endorsement')
                            <svg fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m2.25 0H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z"/></svg>
                            @break
                        @case('good-standing')
                            <svg fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75m-3-7.036A11.959 11.959 0 0 1 3.598 6 11.99 11.99 0 0 0 3 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285Z"/></svg>
                            @break
                        @case('dedicated-status')
                            <svg fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M11.48 3.499a.562.562 0 0 1 1.04 0l2.125 5.111a.563.563 0 0 0 .475.345l5.518.442c.499.04.701.663.321.988l-4.204 3.602a.563.563 0 0 0-.182.557l1.285 5.385a.562.562 0 0 1-.84.61l-4.725-2.885a.562.562 0 0 0-.586 0L6.982 20.54a.562.562 0 0 1-.84-.61l1.285-5.386a.562.562 0 0 0-.182-.557l-4.204-3.602a.562.562 0 0 1 .321-.988l5.518-.442a.563.563 0 0 0 .475-.345L11.48 3.5Z"/></svg>
                            @break
                        @case('welcome')
                            <svg fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 0 1-2.25 2.25h-15a2.25 2.25 0 0 1-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25m19.5 0v.243a2.25 2.25 0 0 1-1.07 1.916l-7.5 4.615a2.25 2.25 0 0 1-2.36 0L3.32 8.91a2.25 2.25 0 0 1-1.07-1.916V6.75"/></svg>
                            @break
                        @case('membership-card')
                            <svg fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 8.25h19.5M2.25 9h19.5m-16.5 5.25h6m-6 2.25h3m-3.75 3h15a2.25 2.25 0 0 0 2.25-2.25V6.75A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25v10.5A2.25 2.25 0 0 0 4.5 19.5Z"/></svg>
                            @break
                    @endswitch
                </div>
                <div class="card-label">{{ $type['label'] }}</div>
                <div class="card-desc">{{ $type['description'] }}</div>
                <div class="card-action">
                    Open preview
                    <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 6H5.25A2.25 2.25 0 0 0 3 8.25v10.5A2.25 2.25 0 0 0 5.25 21h10.5A2.25 2.25 0 0 0 18 18.75V10.5m-10.5 6L21 3m0 0h-5.25M21 3v5.25"/></svg>
                </div>
            </a>
            @endforeach
        </div>
    </div>
</body>
</html>

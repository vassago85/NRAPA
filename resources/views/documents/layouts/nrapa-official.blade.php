<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>{{ $title ?? 'NRAPA Document' }}</title>
  <link rel="icon" href="/favicon.ico" sizes="any">
  <link rel="icon" href="/favicon.svg" type="image/svg+xml">
  <link rel="apple-touch-icon" href="/apple-touch-icon.png">
  <style>
:root {
  --blue: #0B4EA2;
  --orange: #F58220;
  --text: #111827;
  --muted: #6B7280;
  --line: #E5E7EB;
  --paper: #FFFFFF;
  --soft: #F5F7FA;
  --font: ui-sans-serif, system-ui, -apple-system, Segoe UI, Roboto, Helvetica, Arial, "Apple Color Emoji", "Segoe UI Emoji";
}
* { box-sizing: border-box; }
html, body { height: 100%; }
body {
  margin: 0;
  background: var(--soft);
  color: var(--text);
  font-family: var(--font);
  -webkit-print-color-adjust: exact;
  print-color-adjust: exact;
}
a { color: var(--blue); text-decoration: none; }
.small { font-size: 12px; color: var(--muted); }
.h1 { font-size: 22px; font-weight: 800; letter-spacing: .02em; margin: 0; }
.h2 { font-size: 14px; font-weight: 700; margin: 0; }
hr.sep { border: 0; border-top: 1px solid var(--line); margin: 14px 0; }

.page {
  width: 210mm;
  min-height: 297mm;
  margin: 10mm auto;
  background: var(--paper);
  border: 1px solid var(--line);
  border-radius: 14px;
  box-shadow: 0 10px 30px rgba(0,0,0,.10);
  overflow: hidden;
}
.page-inner { padding: 18mm 18mm 16mm 18mm; }

.header {
  display: grid;
  grid-template-columns: 64px 1fr;
  gap: 14px;
  align-items: center;
}
.logo {
  width: 64px;
  height: 64px;
  object-fit: contain;
}
.org { display: grid; gap: 4px; }
.org-title { font-weight: 900; font-size: 16px; letter-spacing: .02em; }
.org-sub { font-size: 12px; color: var(--muted); }

.accreditation-badge {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  background: #f3f4f6;
  padding: 6px 12px;
  border-radius: 20px;
  font-size: 11px;
  color: var(--text);
  margin-top: 4px;
}
.accreditation-dot {
  width: 8px;
  height: 8px;
  border-radius: 50%;
  background: var(--blue);
  display: inline-block;
}

.badge {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  border: 1px solid var(--line);
  background: #fff;
  padding: 8px 10px;
  border-radius: 10px;
  font-size: 12px;
  color: var(--text);
}
.badge b { color: var(--blue); }

.far-block {
  margin-top: 10px;
  border-left: 4px solid var(--blue);
  background: #fff;
  padding: 10px 12px;
  border: 1px solid var(--line);
  border-radius: 12px;
}
.far-block .row {
  display: flex;
  flex-wrap: wrap;
  gap: 12px 18px;
  font-size: 12px;
}
.far-block b { color: var(--blue); }

.titlebar {
  margin-top: 16px;
  padding: 12px 14px;
  border-radius: 12px;
  background: linear-gradient(90deg, rgba(11,78,162,.10), rgba(245,130,32,.10));
  border: 1px solid var(--line);
}

.grid { display: grid; grid-template-columns: 1fr 1fr; gap: 14px; }
.card {
  border: 1px solid var(--line);
  border-radius: 12px;
  padding: 12px 14px;
  background: #fff;
}
.kv {
  display: grid;
  grid-template-columns: 170px 1fr;
  gap: 8px 12px;
  align-items: baseline;
}
.kv .k { color: var(--muted); font-size: 12px; }
.kv .v { font-size: 13px; font-weight: 600; }

.notice {
  border: 1px solid var(--line);
  border-radius: 12px;
  padding: 12px 14px;
  background: #fff;
  font-size: 12px;
  line-height: 1.55;
}

.sig-grid {
  display: grid;
  grid-template-columns: 1.1fr .9fr;
  gap: 14px;
  align-items: start;
}
.sig {
  border: 1px solid var(--line);
  border-radius: 12px;
  padding: 12px 14px;
  background: #fff;
}
.sig .line { height: 1px; background: var(--line); margin: 10px 0 8px 0; }

.qr {
  width: 96px;
  height: 96px;
  border: 1px solid var(--line);
  border-radius: 10px;
  background: #fff;
  overflow: hidden;
}
.qr img { width: 100%; height: 100%; object-fit: contain; }

.placeholder-white {
  background: #fff !important; /* MUST be white */
  border: 1px dashed var(--line);
  border-radius: 12px;
  overflow: hidden;
  display: grid;
  place-items: center;
  color: var(--muted);
  font-size: 11px;
}

.signature-box {
  height: 52px;
}
.signature-box img {
  max-height: 46px;
  max-width: 100%;
  object-fit: contain;
}

.oaths-scan {
  height: 150px;
}
.oaths-scan img {
  width: 100%;
  height: 100%;
  object-fit: contain;
}

.footer {
  margin-top: 14px;
  padding-top: 12px;
  border-top: 1px solid var(--line);
  display: flex;
  justify-content: space-between;
  gap: 12px;
  font-size: 11px;
  color: var(--muted);
}

.footer-contact {
  display: flex;
  flex-wrap: wrap;
  gap: 8px 16px;
  align-items: center;
  font-size: 11px;
  color: var(--muted);
}

.footer-contact-item {
  display: flex;
  align-items: center;
  gap: 4px;
}

.footer-contact-item::before {
  content: '•';
  color: var(--blue);
  margin-right: 4px;
}

.footer-contact-item:first-child::before {
  display: none;
}

.footer-address {
  text-align: right;
  font-size: 11px;
  color: var(--muted);
  font-weight: 700;
}

.letterhead {
  display:flex;
  justify-content: space-between;
  gap: 18px;
  align-items:flex-start;
}
.addr {
  text-align:right;
  font-size:12px;
  color: var(--muted);
  line-height: 1.45;
}
.body {
  font-size: 13px;
  line-height: 1.65;
}
.body p { margin: 0 0 10px 0; }
.ul { margin: 8px 0 12px 20px; }
.ul li { margin: 4px 0; }
.meta {
  display:flex;
  justify-content: space-between;
  gap: 10px;
  font-size: 12px;
  color: var(--muted);
}
.callout {
  border: 1px solid var(--line);
  border-radius: 12px;
  padding: 12px 14px;
  background: #fff;
}
.qrline {
  display:flex;
  gap: 12px;
  align-items:center;
}

@media print {
  body { background: #fff; }
  .page {
    margin: 0;
    border: 0;
    border-radius: 0;
    box-shadow: none;
    width: 210mm;
    min-height: 297mm;
  }
  .page-inner { padding: 16mm; }
  .footer-contact-item::before {
    color: var(--blue);
  }
}
  </style>
  @stack('document-styles')
</head>
<body>
  <main class="page">
    <div class="page-inner">
      @yield('content')
    </div>
  </main>
</body>
</html>

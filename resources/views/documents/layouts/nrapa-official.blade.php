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
/* ============================================
   NRAPA OFFICIAL DOCUMENT STYLES
   Optimized for single A4 page PDF output
   ============================================ */

:root {
  --blue: #0B4EA2;
  --orange: #F58220;
  --emerald: #059669;
  --red: #DC2626;
  --text: #111827;
  --muted: #6B7280;
  --line: #E5E7EB;
  --paper: #FFFFFF;
  --soft: #F5F7FA;
  --font: ui-sans-serif, system-ui, -apple-system, Segoe UI, Roboto, Helvetica, Arial, "Apple Color Emoji", "Segoe UI Emoji";
}

/* Base reset and page setup */
* { 
  box-sizing: border-box; 
  margin: 0;
  padding: 0;
}
html, body { 
  height: 100%; 
  font-size: 11px; /* Base font size for PDF */
  line-height: 1.25;
}
body {
  background: var(--soft);
  color: var(--text);
  font-family: var(--font);
  -webkit-print-color-adjust: exact;
  print-color-adjust: exact;
}

/* Page break prevention - CRITICAL for single page PDF */
*, *::before, *::after {
  page-break-before: avoid !important;
  page-break-after: avoid !important;
  page-break-inside: avoid !important;
  break-before: avoid !important;
  break-after: avoid !important;
  break-inside: avoid !important;
}

a { color: var(--blue); text-decoration: none; font-size: 10px; word-break: break-all; }
.small { font-size: 10px; color: var(--muted); line-height: 1.2; }
.h1 { font-size: 16px; font-weight: 800; letter-spacing: .02em; margin: 0; }
.h2 { font-size: 12px; font-weight: 700; margin: 0 0 4px 0; }
hr.sep { border: 0; border-top: 1px solid var(--line); margin: 8px 0; }

/* Page container */
.page {
  width: 210mm;
  height: 297mm;
  max-height: 297mm;
  margin: 0 auto;
  background: var(--paper);
  border: 1px solid var(--line);
  border-radius: 8px;
  box-shadow: 0 10px 30px rgba(0,0,0,.10);
  overflow: hidden;
  position: relative;
}
.page-inner { 
  padding: 14mm 16mm 12mm 16mm;
  max-height: calc(297mm - 26mm);
  overflow: hidden;
}

/* Header - Compact */
.header {
  display: grid;
  grid-template-columns: 48px 1fr;
  gap: 10px;
  align-items: center;
}
.logo {
  width: 48px;
  height: 48px;
  object-fit: contain;
}
.org { display: grid; gap: 2px; }
.org-title { font-weight: 900; font-size: 13px; letter-spacing: .02em; }
.org-sub { font-size: 10px; color: var(--muted); }

.accreditation-badge {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  background: #f3f4f6;
  padding: 4px 8px;
  border-radius: 12px;
  font-size: 9px;
  color: var(--text);
  margin-top: 2px;
}
.accreditation-dot {
  width: 6px;
  height: 6px;
  border-radius: 50%;
  background: var(--blue);
  display: inline-block;
}

.badge {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  border: 1px solid var(--line);
  background: #fff;
  padding: 4px 8px;
  border-radius: 6px;
  font-size: 10px;
  color: var(--text);
}
.badge b { color: var(--blue); }

.far-block {
  margin-top: 6px;
  border-left: 3px solid var(--blue);
  background: #fff;
  padding: 6px 10px;
  border: 1px solid var(--line);
  border-radius: 8px;
}
.far-block .row {
  display: flex;
  flex-wrap: wrap;
  gap: 6px 14px;
  font-size: 10px;
}
.far-block b { color: var(--blue); }

.far-numbers {
  font-size: 9px !important;
  margin-top: 4px !important;
}

/* Title bar - Compact */
.titlebar {
  margin-top: 10px;
  padding: 8px 12px;
  border-radius: 8px;
  background: linear-gradient(90deg, rgba(11,78,162,.10), rgba(245,130,32,.10));
  border: 1px solid var(--line);
}

/* Grid layout - Compact */
.grid { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; }

/* Cards - Compact */
.card {
  border: 1px solid var(--line);
  border-radius: 8px;
  padding: 8px 10px;
  background: #fff;
}

/* Key-value pairs - Compact */
.kv {
  display: grid;
  grid-template-columns: 110px 1fr;
  gap: 3px 8px;
  align-items: baseline;
}
.kv .k { color: var(--muted); font-size: 10px; }
.kv .v { font-size: 11px; font-weight: 600; word-break: break-word; }

/* Notice block - Compact */
.notice {
  border: 1px solid var(--line);
  border-radius: 8px;
  padding: 8px 10px;
  background: #fff;
  font-size: 10px;
  line-height: 1.35;
}

/* Signature grid - Compact */
.sig-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 10px;
  align-items: start;
}
.sig {
  border: 1px solid var(--line);
  border-radius: 8px;
  padding: 8px 10px;
  background: #fff;
}
.sig .line { height: 1px; background: var(--line); margin: 6px 0; }

/* QR Code - Compact */
.qr {
  width: 70px;
  height: 70px;
  border: 1px solid var(--line);
  border-radius: 6px;
  background: #fff;
  overflow: hidden;
  flex-shrink: 0;
}
.qr img { width: 100%; height: 100%; object-fit: contain; }

/* Placeholder boxes */
.placeholder-white {
  background: #fff !important;
  border: 1px dashed var(--line);
  border-radius: 8px;
  overflow: hidden;
  display: grid;
  place-items: center;
  color: var(--muted);
  font-size: 9px;
}

/* Signature box - Compact */
.signature-box {
  height: 36px;
}
.signature-box img {
  max-height: 32px;
  max-width: 100%;
  object-fit: contain;
}

/* Oaths scan - Compact */
.oaths-scan {
  height: 100px;
}
.oaths-scan img {
  width: 100%;
  height: 100%;
  object-fit: contain;
}

/* Footer - Compact */
.footer {
  margin-top: 8px;
  padding-top: 8px;
  border-top: 1px solid var(--line);
  display: flex;
  justify-content: space-between;
  gap: 8px;
  font-size: 9px;
  color: var(--muted);
  line-height: 1.2;
}

.footer-contact {
  display: flex;
  flex-wrap: wrap;
  gap: 4px 10px;
  align-items: center;
  font-size: 9px;
  color: var(--muted);
}

.footer-contact-item {
  display: flex;
  align-items: center;
  gap: 2px;
}

.footer-contact-item::before {
  content: '•';
  color: var(--blue);
  margin-right: 2px;
}

.footer-contact-item:first-child::before {
  display: none;
}

.footer-address {
  text-align: right;
  font-size: 9px;
  color: var(--muted);
  font-weight: 700;
}

/* Letter styles - Compact */
.letterhead {
  display: flex;
  justify-content: space-between;
  gap: 12px;
  align-items: flex-start;
}
.addr {
  text-align: right;
  font-size: 10px;
  color: var(--muted);
  line-height: 1.3;
}
.body {
  font-size: 11px;
  line-height: 1.4;
}
.body p { margin: 0 0 6px 0; }
.ul { margin: 4px 0 8px 16px; }
.ul li { margin: 2px 0; }
.meta {
  display: flex;
  justify-content: space-between;
  gap: 8px;
  font-size: 10px;
  color: var(--muted);
}
.callout {
  border: 1px solid var(--line);
  border-radius: 8px;
  padding: 8px 10px;
  background: #fff;
}
.qrline {
  display: flex;
  gap: 10px;
  align-items: center;
}

/* Spacing utilities */
div[style*="height:14px"] { height: 8px !important; }
div[style*="height:10px"] { height: 6px !important; }
div[style*="height:8px"] { height: 4px !important; }

/* Print styles */
@media print {
  html, body {
    width: 210mm;
    height: 297mm;
  }
  body { 
    background: #fff; 
  }
  .page {
    margin: 0;
    border: 0;
    border-radius: 0;
    box-shadow: none;
    width: 210mm;
    height: 297mm;
    max-height: 297mm;
    overflow: hidden;
  }
  .page-inner { 
    padding: 14mm 16mm 12mm 16mm;
    max-height: calc(297mm - 26mm);
    overflow: hidden;
  }
  .footer-contact-item::before {
    color: var(--blue);
  }
  
  /* Force single page */
  @page {
    size: A4 portrait;
    margin: 0;
  }
}

/* PDF engine specific (wkhtmltopdf, Dompdf, Snappy) */
@media all {
  .page {
    page-break-after: avoid;
    page-break-before: avoid;
    page-break-inside: avoid;
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

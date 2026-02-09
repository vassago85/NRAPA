<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>{{ $title ?? 'NRAPA Document' }}</title>
  <link rel="icon" href="/nrapa-logo.png" type="image/png">
  <link rel="apple-touch-icon" href="/nrapa-logo.png">
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
  background: #fff; /* Clean white background for PDF */
  color: var(--text);
  font-family: var(--font);
  -webkit-print-color-adjust: exact;
  print-color-adjust: exact;
  display: flex;
  justify-content: center;
  align-items: flex-start;
  min-height: 100%;
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
  min-height: 297mm;
  max-height: 297mm;
  margin: 0 auto;
  background: #fff;
  border: none;
  border-radius: 0;
  box-shadow: none;
  overflow: hidden;
  position: relative;
}
.page-inner { 
  padding: 18mm 20mm 15mm 20mm;
  max-height: calc(297mm - 33mm);
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

/* ============================================
   CARD-INSPIRED DESIGN SYSTEM (v2)
   ============================================ */

/* Blue gradient header bar */
.doc-header {
  background: linear-gradient(135deg, var(--blue) 0%, #0a3d80 100%);
  padding: 12px 16px;
  display: flex;
  align-items: center;
  justify-content: space-between;
  border-radius: 10px 10px 0 0;
  color: #fff;
}
.doc-header .doc-logo {
  width: 36px;
  height: 36px;
  background: rgba(255,255,255,0.9);
  border-radius: 8px;
  display: flex;
  align-items: center;
  justify-content: center;
  overflow: hidden;
  flex-shrink: 0;
}
.doc-header .doc-logo img {
  width: 30px;
  height: 30px;
  object-fit: contain;
}
.doc-header .doc-logo-fallback {
  font-weight: 800;
  font-size: 9px;
  color: var(--blue);
}
.doc-header .doc-org {
  display: flex;
  align-items: center;
  gap: 10px;
}
.doc-header .doc-org-text {
  display: grid;
  gap: 1px;
}
.doc-header .doc-org-name {
  font-weight: 800;
  font-size: 14px;
  color: #fff;
  letter-spacing: 0.3px;
}
.doc-header .doc-org-sub {
  font-size: 9px;
  color: rgba(255,255,255,0.8);
  font-weight: 600;
}
.doc-header .doc-badge {
  padding: 3px 10px;
  border-radius: 10px;
  font-size: 8px;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.5px;
  background: var(--orange);
  color: #fff;
  flex-shrink: 0;
}

/* Orange accent stripe */
.doc-accent {
  height: 3px;
  background: linear-gradient(90deg, var(--orange), #f9a825, var(--orange));
}

/* Document title bar */
.doc-title {
  padding: 10px 16px;
  text-align: center;
}
.doc-title h1 {
  font-size: 14px;
  font-weight: 800;
  letter-spacing: 0.04em;
  color: var(--blue);
  text-transform: uppercase;
  margin: 0;
}
.doc-title .doc-subtitle {
  font-size: 9px;
  color: var(--muted);
  margin-top: 2px;
}

/* Sections with card-style fields */
.doc-section {
  border: 1px solid var(--line);
  border-radius: 8px;
  padding: 10px 14px;
  background: #fff;
}
.doc-section-title {
  font-size: 10px;
  font-weight: 700;
  color: var(--blue);
  text-transform: uppercase;
  letter-spacing: 0.5px;
  margin-bottom: 8px;
  padding-bottom: 4px;
  border-bottom: 1px solid var(--line);
}

/* Field label/value pattern (card-style) */
.doc-field {
  margin-bottom: 6px;
}
.doc-field:last-child { margin-bottom: 0; }
.doc-field-label {
  font-size: 8px;
  text-transform: uppercase;
  letter-spacing: 0.6px;
  color: var(--blue);
  font-weight: 600;
  margin-bottom: 1px;
}
.doc-field-value {
  font-size: 11px;
  color: var(--text);
  font-weight: 600;
  line-height: 1.3;
}
.doc-field-value.name {
  font-size: 13px;
  font-weight: 700;
}
.doc-field-value.mono {
  font-family: 'Courier New', Courier, monospace;
  font-size: 10px;
}

/* Field row (side-by-side) */
.doc-field-row {
  display: flex;
  gap: 12px;
}
.doc-field-row .doc-field { flex: 1; }

/* Two-column document grid */
.doc-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 10px;
}

/* Notice / certification statement */
.doc-notice {
  border-left: 3px solid var(--blue);
  background: var(--soft);
  padding: 10px 14px;
  border-radius: 0 8px 8px 0;
  font-size: 10px;
  line-height: 1.45;
  color: var(--text);
}

/* QR verification section */
.doc-qr-section {
  display: flex;
  gap: 12px;
  align-items: center;
  padding: 10px 14px;
  border: 1px solid var(--line);
  border-radius: 8px;
  background: #fff;
}
.doc-qr-box {
  width: 70px;
  height: 70px;
  border: 1px solid var(--line);
  border-radius: 6px;
  overflow: hidden;
  flex-shrink: 0;
  background: #fff;
  padding: 2px;
}
.doc-qr-box img { width: 100%; height: 100%; object-fit: contain; }
.doc-qr-text {
  font-size: 9px;
  color: var(--muted);
}
.doc-qr-text .verify-label {
  font-size: 11px;
  font-weight: 700;
  color: var(--text);
  display: block;
  margin-bottom: 2px;
}

/* Signatory section */
.doc-signatory {
  padding: 10px 14px;
  border: 1px solid var(--line);
  border-radius: 8px;
  background: #fff;
}
.doc-signatory .sig-name {
  font-weight: 700;
  font-size: 12px;
  color: var(--text);
}
.doc-signatory .sig-title {
  font-size: 10px;
  color: var(--muted);
}
.doc-signatory .sig-line {
  height: 1px;
  background: var(--line);
  margin: 6px 0;
}

/* Blue footer bar */
.doc-footer {
  background: var(--blue);
  padding: 8px 16px;
  border-radius: 0 0 10px 10px;
  display: flex;
  justify-content: space-between;
  align-items: center;
  color: rgba(255,255,255,0.9);
  font-size: 8px;
  font-weight: 600;
}
.doc-footer .doc-footer-cert {
  font-size: 9px;
  font-weight: 700;
}
.doc-footer .doc-footer-far {
  font-size: 7px;
  color: rgba(255,255,255,0.7);
}
.doc-footer .doc-footer-far .far-sport { color: var(--orange); font-weight: 700; }
.doc-footer .doc-footer-far .far-hunting { color: #fbbf24; font-weight: 700; }

/* Document wrapper (adds the rounded border + shadow effect for screen viewing) */
.doc-wrapper {
  border: 1px solid var(--line);
  border-radius: 10px;
  overflow: hidden;
  background: #fff;
}

/* Print styles */
@media print {
  html, body {
    width: 210mm;
    height: 297mm;
    background: #fff !important;
  }
  body { 
    background: #fff !important;
    display: block;
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
    background: #fff !important;
  }
  .page-inner { 
    padding: 18mm 20mm 15mm 20mm;
    max-height: calc(297mm - 33mm);
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

<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Sen Solution Electronique — Devis {{ $quote->quote_number }}</title>
  <style>
    /* Même charte visuelle "encre" que documents/sale_document.blade.php,
       pour rester cohérent entre devis, factures et bons d'échange. */
    * { margin: 0; padding: 0; box-sizing: border-box; }

    @page {
      margin: 0;
    }

    :root {
      --ink: #1e3a5f;
      --accent: #1e3a5f;
      --accent-dark: #14283f;
      --text: #1a1a2e;
      --text-muted: #5b6479;
      --line: #c7cad6;
      --line-light: #e3e5ec;
    }

    body {
      font-family: 'Segoe UI', Arial, sans-serif;
      font-size: 13px;
      color: var(--text);
      background: #fff;
    }

    .page {
      width: 210mm;
      margin: 0 auto;
      background: #fff;
      position: relative;
    }

    .header { padding: 0; position: relative; }
    .header-inner { display: table; width: 100%; padding: 22px 32px 16px; }
    .brand { display: table-cell; vertical-align: top; }
    .brand-row { display: flex; align-items: center; gap: 14px; }
    .brand-icon {
      width: 78px; height: 78px; border-radius: 50%;
      display: flex; align-items: center; justify-content: center;
      border: 2px solid var(--accent); overflow: hidden;
    }
    .brand-icon img { width: 100%; height: 100%; object-fit: cover; display: block; }
    .brand-name { color: var(--text); font-size: 22px; font-weight: 700; letter-spacing: -0.3px; line-height: 1; }
    .brand-sub { color: var(--text-muted); font-size: 10px; letter-spacing: 2px; text-transform: uppercase; margin-top: 3px; }

    .header-doc { display: table-cell; vertical-align: top; text-align: right; }
    .doc-type { color: var(--text-muted); font-size: 11px; letter-spacing: 3px; text-transform: uppercase; margin-bottom: 4px; }
    .doc-number { color: var(--text); font-size: 20px; font-weight: 700; letter-spacing: -0.3px; }
    .doc-status {
      display: inline-block; margin-top: 6px; padding: 2px 10px; border-radius: 3px;
      font-size: 9px; font-weight: 700; letter-spacing: 1px; text-transform: uppercase;
      border: 1px solid var(--text); color: var(--text);
    }

    .header-stripe { height: 3px; background: var(--accent); }

    .meta-band {
      display: table; table-layout: fixed; width: 100%;
      padding: 18px 32px; border-bottom: 1px solid var(--line);
    }
    .meta-block { display: table-cell; vertical-align: top; width: 58%; padding-right: 20px; }
    .meta-block h4 { font-size: 9px; font-weight: 700; letter-spacing: 1.5px; text-transform: uppercase; color: var(--text-muted); margin-bottom: 6px; }
    .meta-block p { color: var(--text); font-size: 13px; line-height: 1.6; }
    .meta-block .name { font-size: 14px; font-weight: 700; color: var(--text); }
    .meta-block.right { width: 41%; text-align: right; padding-left: 20px; padding-right: 0; border-left: 1px solid var(--line); }

    .date-badge {
      display: inline-flex; flex-direction: column; align-items: center;
      border: 1px solid var(--line); border-radius: 6px; padding: 6px 14px; min-width: 76px;
    }
    .date-badge .day { font-size: 18px; font-weight: 700; line-height: 1.1; color: var(--text); }
    .date-badge .month { font-size: 9px; text-transform: uppercase; letter-spacing: 1px; color: var(--text-muted); }
    .date-badge .year { font-size: 10px; color: var(--text-muted); }

    .items-section { padding: 0 32px 8px; }
    .items-table { width: 100%; border-collapse: collapse; margin-top: 18px; }
    .items-table thead tr { border-bottom: 2px solid var(--text); }
    .items-table thead th {
      padding: 8px 10px; font-size: 9.5px; font-weight: 700; letter-spacing: 1px;
      text-transform: uppercase; color: var(--text); text-align: left;
    }
    .items-table thead th.num    { text-align: center; }
    .items-table thead th.amount { text-align: right; }
    .items-table tbody tr { border-bottom: 1px solid var(--line-light); }
    .items-table tbody td { padding: 9px 10px; color: var(--text); vertical-align: middle; }
    .items-table tbody td.desc { font-weight: 500; }
    .items-table tbody td.qty   { text-align: center; }
    .items-table tbody td.unit  { text-align: right; }
    .items-table tbody td.total { text-align: right; font-weight: 700; }
    .qty-badge { display: inline-block; border: 1px solid var(--line); border-radius: 4px; padding: 1px 8px; font-size: 12px; font-weight: 600; }

    .totals-row { display: flex; justify-content: flex-end; padding: 14px 32px 8px; }
    .totals-box { width: 290px; }
    .totals-line { display: flex; justify-content: space-between; padding: 6px 0; border-bottom: 1px solid var(--line-light); font-size: 13px; color: var(--text-muted); }
    .totals-line:last-of-type { border-bottom: none; }
    .totals-line .label { font-weight: 500; }
    .totals-line .val   { font-weight: 600; color: var(--text); }
    .totals-grand {
      display: flex; justify-content: space-between; align-items: center;
      border-top: 2px solid var(--text); border-bottom: 2px solid var(--text);
      padding: 10px 4px; margin-top: 8px;
    }
    .totals-grand .label { font-size: 11px; letter-spacing: 1px; text-transform: uppercase; color: var(--text); }
    .totals-grand .val   { font-size: 18px; font-weight: 700; color: var(--text); }

    .amount-words {
      margin: 8px 32px 0; border-left: 2px solid var(--text);
      padding: 6px 14px; font-size: 11.5px; color: var(--text-muted);
    }
    .amount-words span { font-weight: 700; color: var(--text); }

    /* ── SIGNATURE / CACHET — espace réservé pour la validation manuscrite,
       occupe l'espace en flux normal (pas de min-height forcé : DomPDF gère
       mal cette technique). */
    .signature-section {
      display: table; width: 100%; table-layout: fixed;
      padding: 0 32px; margin-top: 110px;
    }
    .signature-col { display: table-cell; width: 50%; vertical-align: bottom; padding-right: 24px; }
    .signature-col:last-child { padding-right: 0; padding-left: 24px; }
    .signature-line { border-top: 1px solid var(--line); margin-bottom: 6px; height: 1px; }
    .signature-col p { font-size: 10.5px; color: var(--text-muted); text-align: center; text-transform: uppercase; letter-spacing: .5px; }

    .remarks-section { padding: 10px 32px; }
    .info-card { border: 1px solid var(--line); border-radius: 6px; padding: 10px 14px; }
    .info-card h4 { font-size: 9px; font-weight: 700; letter-spacing: 1.5px; text-transform: uppercase; color: var(--text-muted); margin-bottom: 8px; }
    .info-card p { font-size: 13px; color: var(--text); font-weight: 600; }
    .remarks-text { font-size: 11px; color: var(--text-muted); line-height: 1.6; }

    .footer {
      margin-top: 14px;
      border-top: 1px solid var(--text);
      padding: 14px 32px;
      width: 100%;
      display: table;
    }
    .footer-contact { display: table-cell; vertical-align: middle; text-align: left; color: var(--text-muted); font-size: 11px; line-height: 1.7; }
    .footer-thanks { display: table-cell; vertical-align: middle; text-align: right; color: var(--text); font-size: 13px; font-weight: 700; }

    @media print {
      html, body { margin: 0; padding: 0; background: #fff; }
      .page { width: 100%; min-height: 100vh; margin: 0; box-shadow: none; border-radius: 0; }
      .no-print { display: none !important; }
    }

    @media screen {
      body { padding: 20px 0 40px; background: #f0f1f4; }
      .page { box-shadow: 0 4px 30px rgba(30,58,95,0.10); border-radius: 4px; }
    }
  </style>
</head>
<body>

@php
  // Voir sale_document.blade.php : DomPDF ne charge pas les images
  // distantes par défaut, donc le logo restait vide dans le PDF envoyé/
  // téléchargé. Le data URI base64 fonctionne à l'identique dans l'aperçu
  // navigateur et dans le PDF généré par DomPDF.
  $logoPath = public_path('images/logo.png');
  $logoSrc = is_file($logoPath)
      ? 'data:image/jpeg;base64,' . base64_encode(file_get_contents($logoPath))
      : asset('images/logo.png');
@endphp

@if(empty($isPdf))
<div class="no-print" style="display:flex;justify-content:center;gap:12px;margin-bottom:16px;">
  <a href="{{ url()->previous() }}" class="btn btn-outline-secondary" style="padding:10px 28px;border-radius:8px;font-size:13px;font-weight:600;">
    🔙 Retour
  </a>
  <button onclick="window.print()" style="background:#1e3a5f;color:#fff;border:none;padding:10px 28px;border-radius:8px;font-size:13px;cursor:pointer;font-weight:600;">
    🖨️ Imprimer
  </button>
  @if(!empty($downloadUrl))
    <a href="{{ $downloadUrl }}" style="background:#14283f;color:#fff;border:none;padding:10px 28px;border-radius:8px;font-size:13px;font-weight:600;text-decoration:none;">
      ⬇️ Télécharger PDF
    </a>
  @endif
</div>
@endif

<div class="page">

  <div class="header">
    <div class="header-inner">
      <div class="brand">
        <div class="brand-row">
          <div class="brand-icon">
            <img src="{{ $logoSrc }}" alt="Sen Solution Electronique">
          </div>
          <div>
            <div class="brand-name">Sen Solution Electronique</div>
            <div class="brand-sub">Système d'information</div>
          </div>
        </div>
      </div>
      <div class="header-doc">
        <div class="doc-type">Devis</div>
        <div class="doc-number">{{ $quote->quote_number }}</div>
        <span class="doc-status">{{ $quote->status->label() }}</span>
      </div>
    </div>
  </div>
  <div class="header-stripe"></div>

  <div class="meta-band">
    <div class="meta-block">
      <h4>Client</h4>
      @if($quote->customer)
        <p class="name">{{ $quote->customer->full_name }}</p>
        @if($quote->customer->phone)<p>Tél. : {{ $quote->customer->phone }}</p>@endif
        @if($quote->customer->email)<p>Email : {{ $quote->customer->email }}</p>@endif
        @if($quote->customer->address)<p>Adresse : {{ $quote->customer->address }}</p>@endif
      @else
        <p class="name">Client anonyme</p>
      @endif
    </div>

    <div class="meta-block right">
      <h4>Date</h4>
      <div class="date-badge" style="margin-left:auto;">
        <span class="day">{{ $quote->quote_date->format('d') }}</span>
        <span class="month">{{ $quote->quote_date->translatedFormat('M') }}</span>
        <span class="year">{{ $quote->quote_date->format('Y') }}</span>
      </div>
      @if($quote->valid_until)
        <h4 style="margin-top:14px;">Valable jusqu'au</h4>
        <p style="font-weight:600;color:#1e3a5f;">{{ $quote->valid_until->format('d/m/Y') }}</p>
      @endif
    </div>
  </div>

  <div class="items-section">
    <table class="items-table">
      <thead>
        <tr>
          <th style="width:5%;text-align:center;">#</th>
          <th style="width:45%;text-align:left;">Désignation</th>
          <th class="num" style="width:15%;">Qté</th>
          <th class="amount" style="width:17%;">P. Unitaire</th>
          <th class="amount" style="width:18%;">Total</th>
        </tr>
      </thead>
      <tbody>
        @forelse($quote->items as $index => $item)
          <tr>
            <td style="text-align:center;color:#8a97ab;font-size:11px;">{{ str_pad($index + 1, 2, '0', STR_PAD_LEFT) }}</td>
            <td class="desc">{{ $item->product?->name ?? '—' }}</td>
            <td class="qty"><span class="qty-badge">{{ $item->quantity }}</span></td>
            <td class="unit">{{ number_format($item->unit_price, 0, ',', ' ') }} FCFA</td>
            <td class="total">{{ number_format($item->line_total, 0, ',', ' ') }} FCFA</td>
          </tr>
        @empty
          <tr>
            <td colspan="5" style="text-align:center;padding:30px;color:#8a97ab;">Aucun article</td>
          </tr>
        @endforelse
      </tbody>
    </table>
  </div>

  <div class="totals-row">
    <div class="totals-box">
      @php
        $discount = (float) ($quote->discount_amount ?? 0);
        $total = (float) $quote->total_ttc;
        $subtotal = $total + $discount;
      @endphp
      <div class="totals-line">
        <span class="label">Sous-total</span>
        <span class="val">{{ number_format($subtotal, 0, ',', ' ') }} FCFA</span>
      </div>
      @if($discount > 0)
        <div class="totals-line">
          <span class="label">Remise</span>
          <span class="val">-{{ number_format($discount, 0, ',', ' ') }} FCFA</span>
        </div>
      @endif
      <div class="totals-grand">
        <span class="label">Total devis</span>
        <span class="val">{{ number_format($total, 0, ',', ' ') }} FCFA</span>
      </div>
    </div>
  </div>

  <div class="amount-words">
    Devis établi pour la somme de : <span>{{ \App\Helpers\NumberHelper::toWords($total) ?? number_format($total, 0, ',', ' ') . ' Francs CFA' }}</span>
  </div>

  <div class="signature-section">
    <div class="signature-col">
      <div class="signature-line"></div>
      <p>Date et signature du client</p>
    </div>
    <div class="signature-col">
      <div class="signature-line"></div>
      <p>Cachet et signature Sen Solution Electronique</p>
    </div>
  </div>

  <div class="remarks-section">
    <div class="info-card">
      <h4>Conditions</h4>
      <p class="remarks-text">
        @if($quote->notes)
          {{ $quote->notes }}
        @else
          @if($quote->valid_until)
            Ce devis est valable jusqu'au {{ $quote->valid_until->format('d/m/Y') }}. Les prix indiqués peuvent varier après cette date.
          @else
            Les prix indiqués sont susceptibles de varier dans le temps.
          @endif
          Ce document ne constitue pas une facture et n'engage pas de commande tant qu'il n'a pas été accepté.
        @endif
      </p>
    </div>
  </div>

  <div class="footer">
    <div class="footer-contact">
      <div>Email : {{ config('company.email') }}</div>
      <div>Tél. : {{ config('company.phone') }}</div>
      <div>Adresse : {{ config('company.address_line1') }}, {{ config('company.address_line2') }}</div>
      <div>Ninea : {{ config('company.ninea') }} — RC : {{ config('company.rc') }}</div>
    </div>
    <div class="footer-thanks">
      <strong>Merci de votre confiance</strong>
    </div>
  </div>

</div>

</body>
</html>

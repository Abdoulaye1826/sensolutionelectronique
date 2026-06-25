<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  @php
    $isEchange = $sale->isEchange();
    $documentType = $isEchange ? "Bon d'échange" : 'Facture';
    $documentNumber = $isEchange ? $sale->exchange_voucher_number : ($invoice->invoice_number ?? $sale->sale_number);
  @endphp
  <title>SEN SOLUTION ELECTRONIQUE — {{ $documentType }} {{ $documentNumber }}</title>
  <style>
    * { margin: 0; padding: 0; box-sizing: border-box; }

    @page {
      margin: 0;
    }

    body {
      font-family: 'Segoe UI', Arial, sans-serif;
      font-size: 13px;
      color: #1a1a2e;
      background: #f0f4f8;
      -webkit-print-color-adjust: exact;
      print-color-adjust: exact;
    }

    .page {
      width: 210mm;
      min-height: 297mm;
      margin: 0 auto;
      background: #fff;
      position: relative;
      overflow: hidden;
      padding-bottom: 96px;
    }

    /* ── HEADER ── */
    .header {
      background: linear-gradient(135deg, #1a237e 0%, #283593 60%, #1565c0 100%);
      padding: 0;
      position: relative;
      overflow: hidden;
    }

    .header-inner {
      display: flex;
      align-items: center;
      justify-content: space-between;
      padding: 24px 32px 20px;
    }

    .brand { display: flex; align-items: center; gap: 14px; }

    .brand-icon {
      width: 64px; height: 64px;
      background: rgba(255,255,255,0.15);
      border-radius: 50%;
      display: flex; align-items: center; justify-content: center;
      border: 1.5px solid rgba(255,255,255,0.25);
      overflow: hidden;
    }

    .brand-icon img { width: 100%; height: 100%; object-fit: cover; display: block; }

    .brand-name { color: #fff; font-size: 26px; font-weight: 700; letter-spacing: -0.5px; line-height: 1; }
    .brand-sub { color: rgba(255,255,255,0.7); font-size: 11px; letter-spacing: 2px; text-transform: uppercase; margin-top: 3px; }

    .header-doc { text-align: right; }
    .doc-type { color: rgba(255,255,255,0.85); font-size: 11px; letter-spacing: 3px; text-transform: uppercase; margin-bottom: 4px; }
    .doc-number { color: #fff; font-size: 22px; font-weight: 700; letter-spacing: -0.5px; }

    .doc-status {
      display: inline-block; margin-top: 6px; padding: 3px 12px; border-radius: 20px;
      font-size: 10px; font-weight: 600; letter-spacing: 1px; text-transform: uppercase;
    }
    .status-issued    { background: rgba(255,193,7,0.25); color: #FFD54F; border: 1px solid rgba(255,193,7,0.4); }
    .status-paid      { background: rgba(76,175,80,0.25); color: #81C784; border: 1px solid rgba(76,175,80,0.4); }
    .status-cancelled { background: rgba(244,67,54,0.25); color: #EF9A9A; border: 1px solid rgba(244,67,54,0.4); }

    .header-stripe { height: 6px; background: linear-gradient(90deg, #FF6F00, #FFB300, #FFF176, #FFB300, #FF6F00); }

    /* ── META BAND ── */
    .meta-band {
      display: grid;
      grid-template-columns: 1fr auto 1fr;
      align-items: start;
      gap: 20px;
      padding: 22px 32px;
      border-bottom: 1px solid #e8eaf6;
      background: #fafbff;
    }

    .meta-block h4 { font-size: 10px; font-weight: 600; letter-spacing: 1.5px; text-transform: uppercase; color: #7986cb; margin-bottom: 8px; }
    .meta-block p { color: #1a237e; font-size: 13px; line-height: 1.7; }
    .meta-block .name { font-size: 15px; font-weight: 600; color: #0d1b6e; }
    .meta-divider { width: 1px; background: #e8eaf6; align-self: stretch; }
    .meta-block.right { text-align: right; }

    .date-badge {
      display: inline-flex; flex-direction: column; align-items: center;
      background: #1a237e; color: #fff; border-radius: 12px; padding: 10px 16px; min-width: 80px;
    }
    .date-badge .day { font-size: 24px; font-weight: 700; line-height: 1; }
    .date-badge .month { font-size: 10px; text-transform: uppercase; letter-spacing: 1px; opacity: 0.8; }
    .date-badge .year { font-size: 12px; opacity: 0.7; }

    /* ── ITEMS TABLE (facture vente) ── */
    .items-section { padding: 0 32px 8px; }

    .items-table { width: 100%; border-collapse: collapse; margin-top: 20px; }
    .items-table thead tr { background: #1a237e; color: #fff; }
    .items-table thead th { padding: 11px 14px; font-size: 10px; font-weight: 600; letter-spacing: 1px; text-transform: uppercase; }
    .items-table thead th:first-child { border-radius: 8px 0 0 0; }
    .items-table thead th:last-child  { border-radius: 0 8px 0 0; text-align: right; }
    .items-table thead th.num    { text-align: center; }
    .items-table thead th.amount { text-align: right; }

    .items-table tbody tr { border-bottom: 1px solid #e8eaf6; }
    .items-table tbody tr:nth-child(even) { background: #f5f6fd; }
    .items-table tbody tr:last-child { border-bottom: 2px solid #e8eaf6; }
    .items-table tbody td { padding: 12px 14px; color: #2c3e7a; vertical-align: middle; }
    .items-table tbody td.desc { font-weight: 500; color: #1a237e; }
    .items-table tbody td.desc small { display: block; font-size: 11px; color: #9fa8da; font-weight: 400; }
    .items-table tbody td.qty   { text-align: center; }
    .items-table tbody td.unit  { text-align: right; }
    .items-table tbody td.total { text-align: right; font-weight: 600; color: #1a237e; }

    .qty-badge { display: inline-block; background: #e8eaf6; color: #3949ab; border-radius: 6px; padding: 2px 8px; font-size: 12px; font-weight: 600; }

    /* ── ÉCHANGE : PRODUITS ── */
    .exchange-section { padding: 24px 32px 8px; display: grid; grid-template-columns: 1fr auto 1fr; gap: 16px; align-items: stretch; }

    .exchange-card { border: 1px solid #e8eaf6; border-radius: 10px; padding: 16px; background: #f8f9ff; }
    .exchange-card h4 { font-size: 11px; font-weight: 700; letter-spacing: 1px; text-transform: uppercase; color: #7986cb; margin-bottom: 10px; }
    .exchange-card .product-name { font-size: 15px; font-weight: 600; color: #1a237e; margin-bottom: 4px; }
    .exchange-card .product-ref { font-size: 11px; color: #9fa8da; margin-bottom: 12px; }
    .exchange-card .value-row { display: flex; justify-content: space-between; align-items: center; border-top: 1px dashed #e0e4f4; padding-top: 10px; }
    .exchange-card .value-row .label { font-size: 12px; color: #4a5580; font-weight: 500; }
    .exchange-card .value-row .val   { font-size: 16px; font-weight: 700; color: #1a237e; }

    .exchange-arrow { display: flex; align-items: center; justify-content: center; font-size: 28px; color: #7986cb; font-weight: 700; }

    .items-list { margin-top: 10px; }
    .items-list .item-row { display: flex; justify-content: space-between; font-size: 12px; color: #4a5580; padding: 3px 0; }
    .items-list .item-row .qty { color: #9fa8da; }

    /* ── TOTAUX ──
       Même bloc pour le « Total final » (vente) et le « Montant ajouté par le
       client » (échange) : identique en position, taille et style. */
    .totals-row { display: flex; justify-content: flex-end; padding: 16px 32px 8px; }
    .totals-box { width: 300px; }

    .totals-line { display: flex; justify-content: space-between; padding: 7px 0; border-bottom: 1px dashed #e0e4f4; font-size: 13px; color: #4a5580; }
    .totals-line:last-of-type { border-bottom: none; }
    .totals-line .label { font-weight: 500; }
    .totals-line .val   { font-weight: 600; color: #1a237e; }

    .totals-grand {
      display: flex; justify-content: space-between; align-items: center;
      background: #1a237e; color: #fff; border-radius: 10px; padding: 12px 16px; margin-top: 10px;
    }
    .totals-grand .label { font-size: 11px; letter-spacing: 1px; text-transform: uppercase; opacity: 0.85; }
    .totals-grand .val   { font-size: 20px; font-weight: 700; }

    /* ── MONTANT EN LETTRES ── */
    .amount-words {
      margin: 8px 32px 0; background: #f0f4ff; border-left: 4px solid #7986cb;
      border-radius: 0 8px 8px 0; padding: 10px 16px; font-size: 12px; color: #3949ab;
    }
    .amount-words span { font-weight: 600; }

    /* ── REMARQUES / CONDITIONS ──
       Section identique pour les factures de vente et les bons d'échange. */
    .remarks-section { padding: 16px 32px; }

    .info-card { background: #f8f9ff; border: 1px solid #e8eaf6; border-radius: 10px; padding: 14px 16px; }
    .info-card h4 { font-size: 10px; font-weight: 600; letter-spacing: 1.5px; text-transform: uppercase; color: #7986cb; margin-bottom: 10px; }
    .info-card p { font-size: 13px; color: #1a237e; font-weight: 600; }
    .remarks-text { font-size: 11.5px; color: #4a5580; line-height: 1.7; }

    /* ── FOOTER ──
       Positionné en absolu par rapport à .page (qui réserve l'espace via padding-bottom)
       afin de toujours rester collé au bas de la page, même si le contenu est court
       (cas du bon d'échange). Compatible navigateur et DomPDF. */
    .footer {
      position: absolute;
      left: 0; right: 0; bottom: 0;
      background: linear-gradient(135deg, #1a237e 0%, #283593 60%, #1565c0 100%);
      padding: 18px 32px; display: flex; justify-content: space-between; align-items: center;
    }
    .footer-contact { color: rgba(255,255,255,0.9); font-size: 12px; line-height: 1.8; }
    .footer-thanks { color: rgba(255,255,255,0.9); font-size: 15px; font-weight: 700; }

    @media print {
      html, body { margin: 0; padding: 0; background: #fff; }
      .page { width: 100%; min-height: 100vh; margin: 0; box-shadow: none; border-radius: 0; }
      .no-print { display: none !important; }
    }

    @media screen {
      body { padding: 20px 0 40px; }
      .page { box-shadow: 0 4px 30px rgba(26,35,126,0.15); border-radius: 4px; }
    }
  </style>
</head>
<body>

<div class="no-print" style="display:flex;justify-content:center;gap:12px;margin-bottom:16px;">
  <a href="{{ url()->previous() }}" class="btn btn-outline-secondary" style="padding:10px 28px;border-radius:8px;font-size:13px;font-weight:600;">
    🔙 Retour
  </a>
  <button onclick="window.print()" style="background:#1a237e;color:#fff;border:none;padding:10px 28px;border-radius:8px;font-size:13px;cursor:pointer;font-weight:600;">
    🖨️ Imprimer
  </button>
  @if(!empty($downloadUrl))
    <a href="{{ $downloadUrl }}" style="background:#283593;color:#fff;border:none;padding:10px 28px;border-radius:8px;font-size:13px;font-weight:600;text-decoration:none;">
      ⬇️ Télécharger PDF
    </a>
  @endif
</div>

<div class="page">

  {{-- ── EN-TÊTE ── --}}
  <div class="header">
    <div class="header-inner">
      <div class="brand">
        <div class="brand-icon">
          <img src="{{ asset('images/logo.png') }}" alt="SEN SOLUTION ELECTRONIQUE">
        </div>
        <div>
          <div class="brand-name">SEN SOLUTION ELECTRONIQUE</div>
          <div class="brand-sub">Système d'information</div>
        </div>
      </div>
      <div class="header-doc">
        <div class="doc-type">{{ $documentType }}</div>
        <div class="doc-number">{{ $documentNumber }}</div>
        @if(!$isEchange && $invoice)
          <span class="doc-status {{ 'status-' . $invoice->status->value }}">
            {{ $invoice->status->label() }}
          </span>
        @endif
      </div>
    </div>
  </div>
  <div class="header-stripe"></div>

  {{-- ── MÉTA : Client / Dates ── --}}
  <div class="meta-band">
    <div class="meta-block">
      <h4>Client</h4>
      @if($sale->customer)
        <p class="name">{{ $sale->customer->full_name }}</p>
        @if($sale->customer->phone)
          <p>📞 {{ $sale->customer->phone }}</p>
        @endif
        @if(!$isEchange && $sale->customer->email)
          <p>✉️ {{ $sale->customer->email }}</p>
        @endif
        @if(!$isEchange && $sale->customer->address)
          <p>📍 {{ $sale->customer->address }}</p>
        @endif
      @else
        <p class="name">Client anonyme</p>
      @endif
    </div>

    <div class="meta-divider"></div>

    <div class="meta-block right">
      <h4>Date</h4>
      @php $metaDate = $isEchange ? $sale->sale_date : ($invoice->issued_at ?? $sale->sale_date); @endphp
      <div class="date-badge" style="margin-left:auto;">
        <span class="day">{{ $metaDate->format('d') }}</span>
        <span class="month">{{ $metaDate->translatedFormat('M') }}</span>
        <span class="year">{{ $metaDate->format('Y') }}</span>
      </div>
      @if(!$isEchange)
        <h4 style="margin-top:14px;">Vente associée</h4>
        <p style="font-weight:600;color:#1a237e;">{{ $sale->sale_number }}</p>
      @endif
    </div>
  </div>

  @if($isEchange)
    {{-- ── ÉCHANGE : PRODUIT APPORTÉ / PRODUIT REMIS ── --}}
    @php
      $exchangeDetails = $sale->exchange_details ?? [];
      $broughtQuantity = (int) ($exchangeDetails['quantity'] ?? 1);
      $givenQuantity = (int) $sale->items->sum('quantity');
      $addedAmount = (float) ($exchangeDetails['added_amount'] ?? 0);
    @endphp

    <div class="exchange-section">
      <div class="exchange-card">
        <h4>Produit apporté par le client</h4>
        <div class="product-name">{{ $exchangeDetails['name'] ?? '—' }}</div>
        <div class="product-ref">
          {{ $exchangeDetails['reference'] ?? '' }}
          @if(!empty($exchangeDetails['brand'])) — {{ $exchangeDetails['brand'] }} @endif
        </div>
        <div class="value-row">
          <span class="label">Quantité apportée</span>
          <span class="val">{{ $broughtQuantity }}</span>
        </div>
      </div>

      <div class="exchange-arrow">⇄</div>

      <div class="exchange-card">
        <h4>Produit remis par le magasin</h4>
        <div class="items-list">
          @forelse($sale->items as $item)
            <div class="item-row">
              <span>{{ $item->product?->name ?? '—' }}</span>
              <span class="qty">x{{ $item->quantity }}</span>
            </div>
          @empty
            <div class="item-row"><span>—</span></div>
          @endforelse
        </div>
        <div class="value-row">
          <span class="label">Quantité remise</span>
          <span class="val">{{ $givenQuantity }}</span>
        </div>
      </div>
    </div>

    {{-- ── MONTANT AJOUTÉ : seul montant financier affiché, au même emplacement
         et avec le même style que le « Total final » des factures de vente ── --}}
    <div class="totals-row">
      <div class="totals-box">
        <div class="totals-grand">
          <span class="label">Montant ajouté par le client</span>
          <span class="val">{{ number_format($addedAmount, 0, ',', ' ') }} FCFA</span>
        </div>
      </div>
    </div>
  @else
    {{-- ── VENTE : TABLEAU DES ARTICLES ── --}}
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
          @forelse($sale->items as $index => $item)
            <tr>
              <td style="text-align:center;color:#9fa8da;font-size:11px;">{{ str_pad($index + 1, 2, '0', STR_PAD_LEFT) }}</td>
              <td class="desc">{{ $item->product?->name ?? '—' }}</td>
              <td class="qty"><span class="qty-badge">{{ $item->quantity }}</span></td>
              <td class="unit">{{ number_format($item->unit_price, 0, ',', ' ') }} FCFA</td>
              <td class="total">{{ number_format($item->line_total ?? ($item->quantity * $item->unit_price), 0, ',', ' ') }} FCFA</td>
            </tr>
          @empty
            <tr>
              <td colspan="5" style="text-align:center;padding:30px;color:#9fa8da;">Aucun article</td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>

    {{-- ── TOTAUX ── --}}
    <div class="totals-row">
      <div class="totals-box">
        @php
          $discount = (float) ($sale->discount_amount ?? 0);
          $total = (float) $sale->total_ttc;
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
          <span class="label">Total final</span>
          <span class="val">{{ number_format($total, 0, ',', ' ') }} FCFA</span>
        </div>
      </div>
    </div>

    {{-- ── MONTANT EN LETTRES ── --}}
    <div class="amount-words">
      Arrêtée la présente facture à la somme de : <span>{{ \App\Helpers\NumberHelper::toWords($total) ?? number_format($total, 0, ',', ' ') . ' Francs CFA' }}</span>
    </div>
  @endif

  {{-- ── REMARQUES / CONDITIONS ── identique pour les factures de vente et les bons d'échange --}}
  <div class="remarks-section">
    <div class="info-card">
      <h4>Remarques / Conditions</h4>
      <p class="remarks-text">
        @php $remarksText = $invoice?->notes ?? $sale->notes; @endphp
        @if($remarksText)
          {{ $remarksText }}
        @else
          La garantie est de 30 jours et concerne les défauts d'usine. Le service après-vente peut durer une semaine maximum si la garantie n'a pas expiré. Nous ne remboursons pas — nous réparons ou remplaçons.
        @endif
      </p>
    </div>
  </div>

  {{-- ── PIED DE PAGE ──
       Identique sur toutes les factures de vente et tous les bons d'échange
       (aperçu, impression, PDF téléchargé). Aucune date/heure de génération. --}}
  <div class="footer">
    <div class="footer-contact">
      <div>📧 {{ config('company.email') }}</div>
      <div>📞 {{ config('company.phone') }}</div>
      <div>📍 {{ config('company.address_line1') }}, {{ config('company.address_line2') }}</div>
    </div>
    <div class="footer-thanks">
      <strong>Merci de votre confiance</strong>
    </div>
  </div>

</div>

</body>
</html>

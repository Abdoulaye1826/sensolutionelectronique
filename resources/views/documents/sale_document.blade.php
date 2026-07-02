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
  <title>MBOUP GAMING — {{ $documentType }} {{ $documentNumber }}</title>
  <style>
    /* ============================================================
       Modèle économique en encre : pas de fonds colorés ni de
       dégradés. Uniquement du texte noir/gris sur fond blanc, avec
       une seule couleur d'accent (--ink) réservée aux bordures,
       filets et libellés clés — jamais en aplat. Imprimable en
       noir et blanc sans perte d'information.
       ============================================================ */
    * { margin: 0; padding: 0; box-sizing: border-box; }

    @page {
      margin: 0;
    }

    :root {
      --ink: #1a237e;
      --accent: #1a237e;
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
      min-height: 297mm;
      margin: 0 auto;
      background: #fff;
      position: relative;
      overflow: hidden;
      padding-bottom: 80px;
    }

    /* ── HEADER : fond blanc, simple filet de séparation ── */
    .header {
      padding: 0;
      position: relative;
    }

    .header-inner {
      display: flex;
      align-items: center;
      justify-content: space-between;
      padding: 22px 32px 16px;
    }

    .brand { display: flex; align-items: center; gap: 14px; }

    .brand-icon {
      width: 78px; height: 78px;
      border-radius: 50%;
      display: flex; align-items: center; justify-content: center;
      border: 2px solid var(--accent);
      overflow: hidden;
    }

    .brand-icon img { width: 100%; height: 100%; object-fit: cover; display: block; }

    .brand-name { color: var(--text); font-size: 22px; font-weight: 700; letter-spacing: -0.3px; line-height: 1; }
    .brand-sub { color: var(--text-muted); font-size: 10px; letter-spacing: 2px; text-transform: uppercase; margin-top: 3px; }

    .header-doc { text-align: right; }
    .doc-type { color: var(--text-muted); font-size: 11px; letter-spacing: 3px; text-transform: uppercase; margin-bottom: 4px; }
    .doc-number { color: var(--text); font-size: 20px; font-weight: 700; letter-spacing: -0.3px; }

    /* Badge de statut : juste un contour, pas d'aplat de couleur */
    .doc-status {
      display: inline-block; margin-top: 6px; padding: 2px 10px; border-radius: 3px;
      font-size: 9px; font-weight: 700; letter-spacing: 1px; text-transform: uppercase;
      border: 1px solid var(--text);
      color: var(--text);
    }

    .header-stripe { height: 3px; background: var(--accent); }

    /* ── META BAND : fond blanc, filet inférieur uniquement ── */
    .meta-band {
      display: grid;
      grid-template-columns: 1fr auto 1fr;
      align-items: start;
      gap: 20px;
      padding: 18px 32px;
      border-bottom: 1px solid var(--line);
    }

    .meta-block h4 { font-size: 9px; font-weight: 700; letter-spacing: 1.5px; text-transform: uppercase; color: var(--text-muted); margin-bottom: 6px; }
    .meta-block p { color: var(--text); font-size: 13px; line-height: 1.6; }
    .meta-block .name { font-size: 14px; font-weight: 700; color: var(--text); }
    .meta-divider { width: 1px; background: var(--line); align-self: stretch; }
    .meta-block.right { text-align: right; }

    /* Date : simple encadré, plus de pastille pleine */
    .date-badge {
      display: inline-flex; flex-direction: column; align-items: center;
      border: 1px solid var(--line); border-radius: 6px; padding: 6px 14px; min-width: 76px;
    }
    .date-badge .day { font-size: 18px; font-weight: 700; line-height: 1.1; color: var(--text); }
    .date-badge .month { font-size: 9px; text-transform: uppercase; letter-spacing: 1px; color: var(--text-muted); }
    .date-badge .year { font-size: 10px; color: var(--text-muted); }

    /* ── ITEMS TABLE (facture vente) — sans fond, lignes fines ── */
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
    .items-table tbody td.desc small { display: block; font-size: 11px; color: var(--text-muted); font-weight: 400; }
    .items-table tbody td.qty   { text-align: center; }
    .items-table tbody td.unit  { text-align: right; }
    .items-table tbody td.total { text-align: right; font-weight: 700; }

    .qty-badge { display: inline-block; border: 1px solid var(--line); border-radius: 4px; padding: 1px 8px; font-size: 12px; font-weight: 600; }

    /* ── ÉCHANGE : PRODUITS — cartes en simple encadré ── */
    .exchange-section { padding: 22px 32px 8px; display: grid; grid-template-columns: 1fr auto 1fr; gap: 16px; align-items: stretch; }

    .exchange-card { border: 1px solid var(--line); border-radius: 6px; padding: 14px; }
    .exchange-card h4 { font-size: 10px; font-weight: 700; letter-spacing: 1px; text-transform: uppercase; color: var(--text-muted); margin-bottom: 8px; }
    .exchange-card .product-name { font-size: 14px; font-weight: 700; color: var(--text); margin-bottom: 4px; }
    .exchange-card .product-ref { font-size: 11px; color: var(--text-muted); margin-bottom: 10px; }
    .exchange-card .value-row { display: flex; justify-content: space-between; align-items: center; border-top: 1px solid var(--line-light); padding-top: 8px; }
    .exchange-card .value-row .label { font-size: 11px; color: var(--text-muted); font-weight: 500; }
    .exchange-card .value-row .val   { font-size: 15px; font-weight: 700; color: var(--text); }

    .exchange-arrow { display: flex; align-items: center; justify-content: center; font-size: 22px; color: var(--text); font-weight: 700; }

    .items-list { margin-top: 8px; }
    .items-list .item-row { display: flex; justify-content: space-between; font-size: 12px; color: var(--text); padding: 2px 0; }
    .items-list .item-row .qty { color: var(--text-muted); }

    /* ── TOTAUX ──
       Même bloc pour le « Total final » (vente) et le « Montant ajouté par le
       client » (échange) : un simple encadré à double filet, sans aplat. */
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

    /* ── MONTANT EN LETTRES — filet gauche, pas de fond ── */
    .amount-words {
      margin: 8px 32px 0; border-left: 2px solid var(--text);
      padding: 6px 14px; font-size: 11.5px; color: var(--text-muted);
    }
    .amount-words span { font-weight: 700; color: var(--text); }

    /* ── REMARQUES / CONDITIONS — simple encadré ── */
    .remarks-section { padding: 16px 32px; }

    .info-card { border: 1px solid var(--line); border-radius: 6px; padding: 12px 14px; }
    .info-card h4 { font-size: 9px; font-weight: 700; letter-spacing: 1.5px; text-transform: uppercase; color: var(--text-muted); margin-bottom: 8px; }
    .info-card p { font-size: 13px; color: var(--text); font-weight: 600; }
    .remarks-text { font-size: 11px; color: var(--text-muted); line-height: 1.6; }

    /* ── FOOTER ──
       Fond blanc, simple filet supérieur. Positionné en absolu par rapport
       à .page (qui réserve l'espace via padding-bottom) afin de toujours
       rester collé au bas de la page, même si le contenu est court (cas
       du bon d'échange). Compatible navigateur et DomPDF. */
    .footer {
      position: absolute;
      left: 0; right: 0; bottom: 0;
      border-top: 1px solid var(--text);
      padding: 14px 32px; display: flex; justify-content: space-between; align-items: center;
    }
    .footer-contact { color: var(--text-muted); font-size: 11px; line-height: 1.7; }
    .footer-thanks { color: var(--text); font-size: 13px; font-weight: 700; }

    @media print {
      html, body { margin: 0; padding: 0; background: #fff; }
      .page { width: 100%; min-height: 100vh; margin: 0; box-shadow: none; border-radius: 0; }
      .no-print { display: none !important; }
    }

    @media screen {
      body { padding: 20px 0 40px; background: #f0f1f4; }
      .page { box-shadow: 0 4px 30px rgba(26,35,126,0.08); border-radius: 4px; }
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
          <img src="{{ asset('images/logo.jpeg') }}" alt="MBOUP GAMING">
        </div>
        <div>
          <div class="brand-name">MBOUP GAMING</div>
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
        @if(!empty($exchangeDetails['imei']))
          <div class="product-ref">IMEI : {{ $exchangeDetails['imei'] }}</div>
        @endif
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
              <span>
                {{ $item->product?->name ?? '—' }}
                @if($item->productImei)
                  <br><small>IMEI : {{ $item->productImei->imei }}</small>
                @endif
              </span>
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
        @if($invoice && !$invoice->isFullyPaid())
          <div class="totals-line">
            <span class="label">Payé</span>
            <span class="val">{{ number_format($invoice->amount_paid, 0, ',', ' ') }} FCFA</span>
          </div>
          <div class="totals-line">
            <span class="label">Reste à payer</span>
            <span class="val">{{ number_format($invoice->remaining_amount, 0, ',', ' ') }} FCFA</span>
          </div>
        @endif
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
              <td class="desc">
                {{ $item->product?->name ?? '—' }}
                @if($item->productImei)
                  <small>IMEI : {{ $item->productImei->imei }}</small>
                @endif
              </td>
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
        @if($invoice && !$invoice->isFullyPaid())
          <div class="totals-line">
            <span class="label">Payé</span>
            <span class="val">{{ number_format($invoice->amount_paid, 0, ',', ' ') }} FCFA</span>
          </div>
          <div class="totals-line">
            <span class="label">Reste à payer</span>
            <span class="val">{{ number_format($invoice->remaining_amount, 0, ',', ' ') }} FCFA</span>
          </div>
        @endif
      </div>
    </div>

    {{-- ── MONTANT EN LETTRES ── --}}
    <div class="amount-words">
      Arrêtée la présente facture à la somme de : <span>{{ \App\Helpers\NumberHelper::toWords($total) ?? number_format($total, 0, ',', ' ') . ' Francs CFA' }}</span>
    </div>
  @endif

  {{-- ── GARANTIE ── durée choisie à la vente, propre à chaque transaction --}}
  @if($sale->warranty_duration && $sale->warranty_duration->value !== 'none')
    <div class="remarks-section" style="padding-bottom:0;">
      <div class="info-card">
        <h4>Garantie</h4>
        <p style="font-size:13px;color:var(--text);font-weight:600;margin-bottom:2px;">{{ $sale->warranty_duration->label() }}</p>
        @if($sale->warranty_end_date)
          <p class="remarks-text">Valable jusqu'au {{ $sale->warranty_end_date->format('d/m/Y') }}</p>
        @endif
      </div>
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
          Le service après-vente peut durer une semaine maximum si la garantie n'a pas expiré. Nous ne remboursons pas — nous réparons ou remplaçons.
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
      <div>Ninea : {{ config('company.ninea') }} — RC : {{ config('company.rc') }}</div>
    </div>
    <div class="footer-thanks">
      <strong>Merci de votre confiance</strong>
    </div>
  </div>

</div>

</body>
</html>

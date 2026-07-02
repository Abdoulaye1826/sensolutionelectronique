@extends('layouts.dashboard')

@section('title', 'Tableau de bord')
@section('page-title', 'Tableau de bord')

@section('content')
@php $isCashier = auth()->user()->hasRole('cashier'); @endphp

<div class="page-header d-flex justify-content-between align-items-center flex-wrap gap-2">
  <div>
    <h1>Tableau de bord</h1>
    <nav aria-label="breadcrumb">
      <ol class="breadcrumb">
        <li class="breadcrumb-item active">Accueil</li>
      </ol>
    </nav>
  </div>
  <div class="text-muted small">
    <i class="bi bi-calendar3 me-1"></i>{{ now()->translatedFormat('l d F Y') }}
  </div>
</div>

{{-- KPI Cards --}}
<div class="row g-3 mb-4">
  @php
    $kpis = [
      ['label' => 'CA du jour',             'value' => number_format($stats['revenue_today'], 0, ',', ' ') . ' FCFA', 'icon' => 'bi-currency-exchange', 'color' => 'bg-primary bg-opacity-10 text-primary'],
      ['label' => 'CA du mois',             'value' => number_format($stats['revenue_month'], 0, ',', ' ') . ' FCFA', 'icon' => 'bi-graph-up-arrow',   'color' => 'bg-success bg-opacity-10 text-success'],
      ['label' => 'Ventes validées',        'value' => $stats['sales_count'],                                         'icon' => 'bi-cart-check',         'color' => 'bg-info bg-opacity-10 text-info'],
      ['label' => 'Factures émises',        'value' => $stats['invoices_count'],                                      'icon' => 'bi-file-earmark-text',   'color' => 'bg-secondary bg-opacity-10 text-secondary'],
      ['label' => 'Factures payées',        'value' => $stats['paid_invoices_count'],                                 'icon' => 'bi-wallet2',             'color' => 'bg-success bg-opacity-10 text-success'],
      ['label' => 'Impayés',                'value' => $stats['pending_invoices_count'],                              'icon' => 'bi-hourglass-split',     'color' => 'bg-warning bg-opacity-10 text-warning'],
      ['label' => 'Montant payé',           'value' => number_format($stats['amount_paid_total'], 0, ',', ' ') . ' FCFA', 'icon' => 'bi-cash-stack',       'color' => 'bg-success bg-opacity-10 text-success'],
      ['label' => 'Reste à payer',          'value' => number_format($stats['remaining_amount_total'], 0, ',', ' ') . ' FCFA', 'icon' => 'bi-exclamation-circle', 'color' => 'bg-danger bg-opacity-10 text-danger'],
      ['label' => 'Clients',                'value' => $stats['customers_count'],                                     'icon' => 'bi-people',              'color' => 'bg-primary bg-opacity-10 text-primary'],
      ['label' => 'Nouveaux clients (mois)','value' => $stats['new_customers_month'],                                 'icon' => 'bi-person-plus',         'color' => 'bg-info bg-opacity-10 text-info'],
      // Masqué pour le caissier — non présents sur le rapport
      ...($isCashier ? [] : [
        ['label' => 'Valeur du stock',    'value' => number_format($stats['stock_value'], 0, ',', ' ') . ' FCFA',        'icon' => 'bi-box-seam',        'color' => 'bg-secondary bg-opacity-10 text-secondary'],
        ['label' => 'Panier moyen (mois)','value' => number_format($stats['average_sale_amount'], 0, ',', ' ') . ' FCFA', 'icon' => 'bi-basket3',        'color' => 'bg-primary bg-opacity-10 text-primary'],
        ['label' => 'Marge brute (mois)', 'value' => number_format($stats['margin_month'], 0, ',', ' ') . ' FCFA',       'icon' => 'bi-graph-up',        'color' => 'bg-success bg-opacity-10 text-success'],
        ['label' => 'Échanges (mois)',    'value' => $stats['exchanges_count_month'],                                     'icon' => 'bi-arrow-left-right','color' => 'bg-warning bg-opacity-10 text-warning'],
      ]),
    ];
  @endphp

  @foreach($kpis as $kpi)
    <div class="col-6 col-md-4 col-xl-3">
      <div class="kpi-card">
        <div class="d-flex align-items-center gap-3">
          <div class="kpi-icon {{ $kpi['color'] }}">
            <i class="bi {{ $kpi['icon'] }}"></i>
          </div>
          <div>
            <div class="kpi-label">{{ $kpi['label'] }}</div>
            <div class="kpi-value">{{ $kpi['value'] }}</div>
          </div>
        </div>
      </div>
    </div>
  @endforeach
</div>

{{-- Graphique journalier — masqué pour le caissier --}}
@unless($isCashier)
<div class="row g-3 mb-4">
  <div class="col-lg-12">
    <div class="chart-card">
      <div class="card-title"><i class="bi bi-graph-up me-2"></i>Évolution des ventes journalières (30 derniers jours)</div>
      <canvas id="salesByDayChart" height="80"></canvas>
    </div>
  </div>
</div>
@endunless

{{-- Ventes par mois + Ventes par catégorie --}}
<div class="row g-3 mb-4">
  <div class="col-lg-8">
    <div class="chart-card">
      <div class="card-title"><i class="bi bi-bar-chart me-2"></i>Ventes par mois (FCFA)</div>
      <canvas id="salesByMonthChart" height="100"></canvas>
    </div>
  </div>
  <div class="col-lg-4">
    <div class="chart-card">
      <div class="card-title"><i class="bi bi-pie-chart me-2"></i>Ventes par catégorie</div>
      <canvas id="salesByCategoryChart" height="200"></canvas>
    </div>
  </div>
</div>

{{-- Statut factures + Ventes vs Échanges + Mouvements de stock — masqués pour le caissier --}}
@unless($isCashier)
<div class="row g-3 mb-4">
  <div class="col-lg-4">
    <div class="chart-card h-100">
      <div class="card-title"><i class="bi bi-pie-chart-fill me-2"></i>Statut des factures</div>
      <canvas id="invoiceStatusChart" height="260"></canvas>
    </div>
  </div>
  <div class="col-lg-4">
    <div class="chart-card h-100">
      <div class="card-title"><i class="bi bi-arrow-left-right me-2"></i>Ventes vs Échanges (12 mois)</div>
      <canvas id="salesTypeChart" height="260"></canvas>
    </div>
  </div>
  <div class="col-lg-4">
    <div class="table-card h-100">
      <div class="p-3 border-bottom">
        <h6 class="mb-0 fw-semibold"><i class="bi bi-arrow-down-up me-2"></i>Derniers mouvements de stock</h6>
      </div>
      <div class="table-responsive" style="max-height: 320px;">
        <table class="table table-hover mb-0 small">
          <thead>
            <tr>
              <th>Produit</th>
              <th>Type</th>
              <th class="text-end">Qté</th>
            </tr>
          </thead>
          <tbody>
            @forelse($recentStockMovements as $movement)
              <tr>
                <td>{{ $movement->product?->name ?? '—' }}</td>
                <td>
                  @php
                    $movementBadge = match($movement->type->value ?? $movement->type) {
                      'entry' => 'bg-success',
                      'exit' => 'bg-danger',
                      'sale' => 'bg-primary',
                      'return' => 'bg-warning text-dark',
                      default => 'bg-secondary',
                    };
                  @endphp
                  <span class="badge {{ $movementBadge }}">{{ $movement->type->label() }}</span>
                </td>
                <td class="text-end">{{ $movement->quantity_before }} → {{ $movement->quantity_after }}</td>
              </tr>
            @empty
              <tr>
                <td colspan="3" class="text-center text-muted py-4">Aucun mouvement de stock</td>
              </tr>
            @endforelse
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>
@endunless

{{-- Factures récentes --}}
<div class="row g-3 mb-4">
  <div class="col-lg-12">
    <div class="table-card h-100">
      <div class="p-3 border-bottom">
        <h6 class="mb-0 fw-semibold"><i class="bi bi-clock-history me-2"></i>Factures récentes</h6>
      </div>
      <div class="table-responsive">
        <table class="table table-hover mb-0">
          <thead>
            <tr>
              <th>Numéro</th>
              <th>Client</th>
              <th class="text-end">Montant</th>
              <th class="text-end">Statut</th>
            </tr>
          </thead>
          <tbody>
            @forelse($recentInvoices as $invoice)
              @php
                $invoiceStatus = $invoice->status instanceof App\Enums\InvoiceStatus
                    ? $invoice->status
                    : App\Enums\InvoiceStatus::from($invoice->status);
              @endphp
              <tr>
                <td>{{ $invoice->invoice_number }}</td>
                <td>{{ $invoice->customer?->full_name ?? '—' }}</td>
                <td class="text-end">{{ number_format($invoice->total_ttc, 0, ',', ' ') }} FCFA</td>
                <td class="text-end">
                  <span class="badge {{ $invoiceStatus === App\Enums\InvoiceStatus::Paid ? 'bg-success' : ($invoiceStatus === App\Enums\InvoiceStatus::Issued ? 'bg-warning text-dark' : 'bg-danger') }}">
                    {{ $invoiceStatus->label() }}
                  </span>
                </td>
              </tr>
            @empty
              <tr>
                <td colspan="4" class="text-center text-muted py-4">Aucune facture récente</td>
              </tr>
            @endforelse
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>

{{-- Top clients + Vendeurs performants --}}
<div class="row g-3 mb-4">
  <div class="col-lg-6">
    <div class="table-card h-100">
      <div class="p-3 border-bottom">
        <h6 class="mb-0 fw-semibold"><i class="bi bi-trophy me-2"></i>Top clients</h6>
      </div>
      <div class="table-responsive">
        <table class="table table-hover mb-0">
          <thead>
            <tr>
              <th>#</th>
              <th>Client</th>
              <th class="text-center">Factures</th>
              <th class="text-end">Montant</th>
            </tr>
          </thead>
          <tbody>
            @forelse($topCustomers as $index => $customer)
              <tr>
                <td>{{ $index + 1 }}</td>
                <td>{{ $customer->full_name }}</td>
                <td class="text-center"><span class="badge bg-primary">{{ $customer->invoices_count }}</span></td>
                <td class="text-end">{{ number_format($customer->total_amount, 0, ',', ' ') }} FCFA</td>
              </tr>
            @empty
              <tr>
                <td colspan="4" class="text-center text-muted py-4">Aucun client n'a encore passé de commande</td>
              </tr>
            @endforelse
          </tbody>
        </table>
      </div>
    </div>
  </div>

  <div class="col-lg-6">
    <div class="table-card h-100">
      <div class="p-3 border-bottom">
        <h6 class="mb-0 fw-semibold"><i class="bi bi-people-fill me-2"></i>Vendeurs performants</h6>
      </div>
      <div class="table-responsive">
        <table class="table table-hover mb-0">
          <thead>
            <tr>
              <th>#</th>
              <th>Vendeur</th>
              <th class="text-center">Ventes</th>
              <th class="text-end">Montant</th>
            </tr>
          </thead>
          <tbody>
            @forelse($salesByUser as $index => $user)
              <tr>
                <td>{{ $index + 1 }}</td>
                <td>{{ $user->name }}</td>
                <td class="text-center"><span class="badge bg-info">{{ $user->sales_count }}</span></td>
                <td class="text-end">{{ number_format($user->total_amount, 0, ',', ' ') }} FCFA</td>
              </tr>
            @empty
              <tr>
                <td colspan="4" class="text-center text-muted py-4">Aucun vendeur enregistré</td>
              </tr>
            @endforelse
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>

{{-- Top produits + Alertes stock — masqués pour le caissier --}}
@unless($isCashier)
<div class="row g-3">
  <div class="col-lg-7">
    <div class="table-card">
      <div class="p-3 border-bottom">
        <h6 class="mb-0 fw-semibold"><i class="bi bi-trophy me-2"></i>Produits les plus vendus</h6>
      </div>
      <div class="table-responsive">
        <table class="table table-hover mb-0">
          <thead>
            <tr>
              <th>#</th>
              <th>Produit</th>
              <th class="text-center">Qté vendue</th>
              <th class="text-end">Montant</th>
            </tr>
          </thead>
          <tbody>
            @forelse($topProducts as $index => $product)
              <tr>
                <td>{{ $index + 1 }}</td>
                <td>{{ $product->name }}</td>
                <td class="text-center"><span class="badge bg-primary">{{ $product->total_qty }}</span></td>
                <td class="text-end">{{ number_format($product->total_amount, 0, ',', ' ') }} FCFA</td>
              </tr>
            @empty
              <tr>
                <td colspan="4" class="text-center text-muted py-4">Aucune vente enregistrée pour le moment</td>
              </tr>
            @endforelse
          </tbody>
        </table>
      </div>
    </div>
  </div>

  <div class="col-lg-5">
    <div class="table-card">
      <div class="p-3 border-bottom">
        <h6 class="mb-0 fw-semibold"><i class="bi bi-exclamation-triangle me-2"></i>Alertes stock</h6>
      </div>
      <div class="table-responsive">
        <table class="table table-hover mb-0">
          <thead>
            <tr>
              <th>Produit</th>
              <th class="text-center">Stock</th>
              <th>Statut</th>
            </tr>
          </thead>
          <tbody>
            @forelse($stockAlerts as $product)
              <tr>
                <td>
                  <div class="fw-medium">{{ $product->name }}</div>
                  <small class="text-muted">{{ $product->category?->name }}</small>
                </td>
                <td class="text-center">{{ $product->stock_quantity }}</td>
                <td>
                  @if($product->isOutOfStock())
                    <span class="badge bg-danger">Rupture</span>
                  @else
                    <span class="badge bg-warning text-dark">Stock faible</span>
                  @endif
                </td>
              </tr>
            @empty
              <tr>
                <td colspan="3" class="text-center text-muted py-4">Aucune alerte stock</td>
              </tr>
            @endforelse
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>
@endunless
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js"></script>
<script>
  const chartDefaults = { responsive: true, maintainAspectRatio: true };

  @unless($isCashier)
  // Évolution journalière des ventes (30 derniers jours)
  new Chart(document.getElementById('salesByDayChart'), {
    type: 'line',
    data: {
      labels: @json($salesByDay['labels']),
      datasets: [{
        label: 'CA (FCFA)',
        data: @json($salesByDay['data']),
        borderColor: '#d97706',
        backgroundColor: 'rgba(217, 119, 6, 0.12)',
        fill: true,
        tension: 0.35,
        pointRadius: 2,
        pointHoverRadius: 5,
        borderWidth: 2,
      }]
    },
    options: {
      ...chartDefaults,
      plugins: {
        legend: { display: false },
        tooltip: {
          callbacks: {
            label: function (ctx) {
              const counts = @json($salesByDay['counts']);
              const count = counts[ctx.dataIndex] ?? 0;
              return ctx.parsed.y.toLocaleString('fr-FR') + ' FCFA (' + count + ' vente' + (count > 1 ? 's' : '') + ')';
            }
          }
        }
      },
      scales: {
        y: { beginAtZero: true },
        x: { ticks: { maxRotation: 0, autoSkip: true, maxTicksLimit: 12 } }
      }
    }
  });
  @endunless

  // Ventes par mois
  new Chart(document.getElementById('salesByMonthChart'), {
    type: 'bar',
    data: {
      labels: @json($salesByMonth['labels']),
      datasets: [{
        label: 'CA (FCFA)',
        data: @json($salesByMonth['data']),
        backgroundColor: 'rgba(59, 130, 246, 0.7)',
        borderRadius: 6,
      }]
    },
    options: {
      ...chartDefaults,
      plugins: { legend: { display: false } },
      scales: { y: { beginAtZero: true } }
    }
  });

  // Ventes par catégorie
  const catLabels = @json($salesByCategory['labels']);
  const catData = @json($salesByCategory['data']);
  const colors = ['#3b82f6','#10b981','#f59e0b','#ef4444','#8b5cf6','#06b6d4','#ec4899'];

  new Chart(document.getElementById('salesByCategoryChart'), {
    type: 'doughnut',
    data: {
      labels: catLabels.length ? catLabels : ['Aucune donnée'],
      datasets: [{
        data: catData.length ? catData : [1],
        backgroundColor: catLabels.length ? colors.slice(0, catLabels.length) : ['#e2e8f0'],
      }]
    },
    options: {
      ...chartDefaults,
      plugins: {
        legend: { position: 'bottom', labels: { boxWidth: 12, font: { size: 11 } } }
      }
    }
  });

  @unless($isCashier)
  // Statut des factures
  const invoiceLabels = @json($invoiceStatusSummary['labels']);
  const invoiceData = @json($invoiceStatusSummary['values']);
  const invoiceColors = ['#0d6efd', '#198754', '#ffc107', '#dc3545'];

  new Chart(document.getElementById('invoiceStatusChart'), {
    type: 'doughnut',
    data: {
      labels: invoiceLabels.length ? invoiceLabels : ['Aucune donnée'],
      datasets: [{
        data: invoiceData.length ? invoiceData : [1],
        backgroundColor: invoiceLabels.length ? invoiceColors.slice(0, invoiceLabels.length) : ['#e2e8f0'],
      }]
    },
    options: {
      ...chartDefaults,
      plugins: {
        legend: { position: 'bottom', labels: { boxWidth: 12, font: { size: 11 } } }
      }
    }
  });

  @endunless
  @unless($isCashier)
  // Ventes vs Échanges
  const salesTypeLabels = @json($salesTypeBreakdown['labels']);
  const salesTypeData = @json($salesTypeBreakdown['data']);

  new Chart(document.getElementById('salesTypeChart'), {
    type: 'doughnut',
    data: {
      labels: salesTypeLabels,
      datasets: [{
        data: salesTypeData.some(v => v > 0) ? salesTypeData : [1, 0],
        backgroundColor: ['#0d6efd', '#fd7e14'],
      }]
    },
    options: {
      ...chartDefaults,
      plugins: {
        legend: { position: 'bottom', labels: { boxWidth: 12, font: { size: 11 } } }
      }
    }
  });
  @endunless
</script>
@endpush

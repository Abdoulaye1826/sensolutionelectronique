@extends('layouts.dashboard')

@section('title', 'Factures')
@section('page-title', 'Gestion des factures')

@section('content')
<div class="page-header d-flex justify-content-between align-items-center flex-wrap gap-2">
  <div>
    <h1><i class="bi bi-receipt me-2"></i>Factures</h1>
    <nav aria-label="breadcrumb">
      <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Accueil</a></li>
        <li class="breadcrumb-item active">Factures</li>
      </ol>
    </nav>
  </div>
  <a href="{{ route('invoices.create') }}" class="btn btn-primary">
    <i class="bi bi-plus-lg me-1"></i>Nouvelle facture
  </a>
</div>

<div class="dashboard-summary-grid mb-4">
  <div class="dashboard-summary-card">
    <div class="summary-card-top">
      <span class="badge bg-primary">Total</span>
      <i class="bi bi-receipt summary-icon text-primary"></i>
    </div>
    <div class="summary-card-value">{{ $summary['total'] }}</div>
    <div class="summary-card-label">Factures enregistrées</div>
  </div>

  <div class="dashboard-summary-card">
    <div class="summary-card-top">
      <span class="badge bg-secondary">Non payées</span>
      <i class="bi bi-send summary-icon text-secondary"></i>
    </div>
    <div class="summary-card-value">{{ $summary['issued'] }}</div>
    <div class="summary-card-label">Factures non payées</div>
  </div>

  <div class="dashboard-summary-card">
    <div class="summary-card-top">
      <span class="badge bg-warning text-dark">Partielles</span>
      <i class="bi bi-hourglass-split summary-icon text-warning"></i>
    </div>
    <div class="summary-card-value">{{ $summary['partial'] }}</div>
    <div class="summary-card-label">Partiellement payées</div>
  </div>

  <div class="dashboard-summary-card">
    <div class="summary-card-top">
      <span class="badge bg-success">Payées</span>
      <i class="bi bi-cash-stack summary-icon text-success"></i>
    </div>
    <div class="summary-card-value">{{ $summary['paid'] }}</div>
    <div class="summary-card-label">Factures payées</div>
  </div>

  <div class="dashboard-summary-card">
    <div class="summary-card-top">
      <span class="badge bg-danger">Annulées</span>
      <i class="bi bi-x-circle summary-icon text-danger"></i>
    </div>
    <div class="summary-card-value">{{ $summary['cancelled'] }}</div>
    <div class="summary-card-label">Factures annulées</div>
  </div>
</div>

<div class="dashboard-summary-grid mb-4">
  <div class="dashboard-summary-card">
    <div class="summary-card-top">
      <span class="badge bg-primary">Facturé</span>
      <i class="bi bi-receipt-cutoff summary-icon text-primary"></i>
    </div>
    <div class="summary-card-value">{{ number_format($summary['amount_total'], 0, ',', ' ') }} FCFA</div>
    <div class="summary-card-label">Montant total facturé (hors annulées)</div>
  </div>

  <div class="dashboard-summary-card">
    <div class="summary-card-top">
      <span class="badge bg-success">Payé</span>
      <i class="bi bi-cash-stack summary-icon text-success"></i>
    </div>
    <div class="summary-card-value">{{ number_format($summary['amount_paid'], 0, ',', ' ') }} FCFA</div>
    <div class="summary-card-label">Montant total payé</div>
  </div>

  <div class="dashboard-summary-card">
    <div class="summary-card-top">
      <span class="badge bg-danger">Reste</span>
      <i class="bi bi-exclamation-circle summary-icon text-danger"></i>
    </div>
    <div class="summary-card-value">{{ number_format($summary['amount_remaining'], 0, ',', ' ') }} FCFA</div>
    <div class="summary-card-label">Reste à payer global</div>
  </div>

  <div class="dashboard-summary-card">
    <div class="summary-card-top">
      <span class="badge bg-dark">Plus ancienne</span>
      <i class="bi bi-clock-history summary-icon text-dark"></i>
    </div>
    <div class="summary-card-value">
      @if($summary['oldest_unpaid'])
        <a href="{{ route('invoices.edit', $summary['oldest_unpaid']) }}">{{ $summary['oldest_unpaid']->invoice_number }}</a>
      @else
        —
      @endif
    </div>
    <div class="summary-card-label">
      @if($summary['oldest_unpaid'])
        Impayée depuis le {{ $summary['oldest_unpaid']->issued_at->format('d/m/Y') }} ({{ $summary['oldest_unpaid']->customer?->full_name ?? 'Client anonyme' }})
      @else
        Aucune facture impayée
      @endif
    </div>
  </div>
</div>

<div class="card border-0 shadow-sm mb-4 filter-card">
  <div class="card-body">
    <form method="GET" action="{{ route('invoices.index') }}" class="row g-3 align-items-end">
      <div class="col-md-4">
        <label class="form-label small">Rechercher</label>
        <input type="text" name="search" class="form-control" placeholder="Numéro, client"
               value="{{ $filters['search'] ?? '' }}">
      </div>
      <div class="col-md-3">
        <label class="form-label small">Statut</label>
        <select name="status" class="form-select">
          <option value="">Tous</option>
          <option value="issued" @selected(($filters['status'] ?? '') === 'issued')>Non payé</option>
          <option value="partial" @selected(($filters['status'] ?? '') === 'partial')>Partiellement payée</option>
          <option value="paid" @selected(($filters['status'] ?? '') === 'paid')>Payée</option>
          <option value="cancelled" @selected(($filters['status'] ?? '') === 'cancelled')>Annulée</option>
        </select>
      </div>
      <div class="col-md-3">
        <label class="form-label small">Client</label>
        <input type="text" name="customer_id" class="form-control" placeholder="ID client"
               value="{{ $filters['customer_id'] ?? '' }}">
      </div>
      <div class="col-md-2 d-flex align-items-end gap-2">
        <button type="submit" class="btn btn-primary w-100"><i class="bi bi-search me-1"></i>Filtrer</button>
        <a href="{{ route('invoices.index') }}" class="btn btn-outline-secondary w-100">Réinitialiser</a>
      </div>
    </form>
  </div>
</div>

<div class="table-card">
  <div class="table-responsive">
    <table class="table table-hover mb-0">
      <thead>
        <tr>
          <th>Numéro</th>
          <th>Client</th>
          <th>Vente</th>
          <th>Date</th>
          <th class="text-end">Montant total</th>
          <th class="text-end">Montant payé</th>
          <th class="text-end">Reste à payer</th>
          <th>Statut</th>
          <th class="text-end">Actions</th>
        </tr>
      </thead>
      <tbody>
        @forelse($invoices as $invoice)
          <tr>
            <td>{{ $invoice->invoice_number }}</td>
            <td>{{ $invoice->customer?->full_name ?? 'Client anonyme' }}</td>
            <td>{{ $invoice->sale?->sale_number ?? '—' }}</td>
            <td>{{ $invoice->issued_at->format('d/m/Y') }}</td>
            <td class="text-end">{{ number_format($invoice->total_ttc, 0, ',', ' ') }} FCFA</td>
            <td class="text-end text-success">{{ number_format($invoice->amount_paid, 0, ',', ' ') }} FCFA</td>
            <td class="text-end {{ $invoice->remaining_amount > 0 ? 'text-danger' : 'text-success' }}">{{ number_format($invoice->remaining_amount, 0, ',', ' ') }} FCFA</td>
            <td><span class="badge {{ $invoice->status->badgeClass() }}">{{ $invoice->status->label() }}</span></td>
            <td class="text-end">
              <a href="{{ route('invoices.print', $invoice) }}" target="_blank" class="btn btn-sm btn-outline-secondary" title="Imprimer">
                <i class="bi bi-printer"></i>
              </a>
              {{-- <a href="{{ route('invoices.download', $invoice) }}" class="btn btn-sm btn-outline-dark" title="PDF">
                <i class="bi bi-file-earmark-pdf"></i>
              </a> --}}
              <button type="button" class="btn btn-sm btn-outline-success js-whatsapp-share" title="Envoyer le PDF par WhatsApp"
                      data-payload-url="{{ route('invoices.whatsapp.payload', $invoice) }}">
                <i class="bi bi-whatsapp"></i>
              </button>
              @php $customerEmail = $invoice->customer?->email ?? $invoice->sale?->customer?->email; @endphp
              <form action="{{ route('invoices.email', $invoice) }}" method="POST" class="d-inline"
                    onsubmit="return confirm('Envoyer ce document par email à {{ $customerEmail }} ?')">
                @csrf
                <button type="submit" class="btn btn-sm btn-outline-info" title="{{ $customerEmail ? 'Envoyer par email' : 'Aucun email renseigné pour ce client' }}"
                        @if(!$customerEmail) disabled @endif>
                  <i class="bi bi-envelope"></i>
                </button>
              </form>
              <a href="{{ route('invoices.edit', $invoice) }}" class="btn btn-sm btn-outline-primary" title="Modifier">
                <i class="bi bi-pencil"></i>
              </a>
              <form action="{{ route('invoices.destroy', $invoice) }}" method="POST" class="d-inline" onsubmit="return confirm('Supprimer cette facture ?')">
                @csrf @method('DELETE')
                <button type="submit" class="btn btn-sm btn-outline-danger">
                  <i class="bi bi-trash"></i>
                </button>
              </form>
            </td>
          </tr>
        @empty
          <tr>
            <td colspan="9" class="text-center text-muted py-4">Aucune facture trouvée.</td>
          </tr>
        @endforelse
      </tbody>
    </table>
  </div>
  <div class="p-3 border-top">{{ $invoices->links() }}</div>
</div>

@push('scripts')
  @include('partials.whatsapp-share-script')
@endpush
@endsection

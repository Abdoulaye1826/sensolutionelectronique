@extends('layouts.dashboard')

@section('title', 'Ventes')
@section('page-title', 'Gestion des ventes')

@section('content')
<div class="page-header d-flex justify-content-between align-items-center flex-wrap gap-2">
  <div>
    <h1><i class="bi bi-cart-check me-2"></i>Ventes</h1>
    <nav aria-label="breadcrumb">
      <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Accueil</a></li>
        <li class="breadcrumb-item active">Ventes</li>
      </ol>
    </nav>
  </div>
  <a href="{{ route('sales.create') }}" class="btn btn-primary">
    <i class="bi bi-plus-lg me-1"></i>Nouvelle vente
  </a>
</div>

<div class="mb-3">
  <span class="badge bg-primary fs-6">{{ $sales->total() }} vente(s)</span>
</div>

<div class="card border-0 shadow-sm mb-4">
  <div class="card-body">
    <form method="GET" action="{{ route('sales.index') }}" class="row g-3 align-items-end">
      <div class="col-md-4">
        <label class="form-label small">Rechercher</label>
        <input type="text" name="search" class="form-control" placeholder="Numéro ou client"
               value="{{ $filters['search'] ?? '' }}">
      </div>
      <div class="col-md-3">
        <label class="form-label small">Statut</label>
        <select name="status" class="form-select">
          <option value="">Tous</option>
          <option value="draft" @selected(($filters['status'] ?? '') === 'draft')>Brouillon</option>
          <option value="validated" @selected(($filters['status'] ?? '') === 'validated')>Validée</option>
          <option value="cancelled" @selected(($filters['status'] ?? '') === 'cancelled')>Annulée</option>
        </select>
      </div>
      <div class="col-md-3">
        <label class="form-label small">Type</label>
        <select name="sale_type" class="form-select">
          <option value="">Tous</option>
          <option value="vente" @selected(($filters['sale_type'] ?? '') === 'vente')>Vente</option>
          <option value="echange" @selected(($filters['sale_type'] ?? '') === 'echange')>Échange</option>
        </select>
      </div>
      <div class="col-md-2">
        <label class="form-label small">Client</label>
        <input type="text" name="customer_id" class="form-control" placeholder="ID client"
               value="{{ $filters['customer_id'] ?? '' }}">
      </div>
      <div class="col-md-2 text-end">
        <button type="submit" class="btn btn-outline-primary w-100"><i class="bi bi-search me-1"></i>Filtrer</button>
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
          <th>Type</th>
          <th>Client</th>
          <th>Date</th>
          <th>Montant TTC</th>
          <th>Statut</th>
          <th class="text-end">Actions</th>
        </tr>
      </thead>
      <tbody>
        @forelse($sales as $sale)
          <tr>
            <td>{{ $sale->sale_number }}</td>
            <td><span class="badge {{ $sale->sale_type->badgeClass() }}">{{ $sale->sale_type->label() }}</span></td>
            <td>{{ $sale->customer?->full_name ?? 'Client anonyme' }}</td>
            <td>{{ $sale->sale_date->format('d/m/Y') }}</td>
            <td>{{ number_format($sale->total_ttc, 2, ',', ' ') }} FCFA</td>
            <td><span class="badge {{ $sale->status->badgeClass() }}">{{ $sale->status->label() }}</span></td>
            <td class="text-end">
              @if($sale->isEchange())
                <a href="{{ route('sales.exchange-voucher.print', $sale) }}" class="btn btn-sm btn-outline-secondary" title="Bon d'échange" target="_blank">
                  <i class="bi bi-receipt"></i>
                </a>
                <button type="button" class="btn btn-sm btn-outline-success js-whatsapp-share" title="Envoyer le PDF par WhatsApp"
                        data-payload-url="{{ route('sales.exchange-voucher.whatsapp.payload', $sale) }}">
                  <i class="bi bi-whatsapp"></i>
                </button>
              @endif
              <a href="{{ route('sales.edit', $sale) }}" class="btn btn-sm btn-outline-primary" title="Modifier">
                <i class="bi bi-pencil"></i>
              </a>
              <form action="{{ route('sales.destroy', $sale) }}" method="POST" class="d-inline" onsubmit="return confirm('Supprimer cette vente ?')">
                @csrf @method('DELETE')
                <button type="submit" class="btn btn-sm btn-outline-danger">
                  <i class="bi bi-trash"></i>
                </button>
              </form>
            </td>
          </tr>
        @empty
          <tr>
            <td colspan="6" class="text-center text-muted py-4">Aucune vente trouvée.</td>
          </tr>
        @endforelse
      </tbody>
    </table>
  </div>
  <div class="p-3 border-top">{{ $sales->links() }}</div>
</div>

@push('scripts')
  @include('partials.whatsapp-share-script')
@endpush
@endsection

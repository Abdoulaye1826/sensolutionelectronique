@extends('layouts.dashboard')

@section('title', 'Garanties')
@section('page-title', 'Gestion des garanties')

@section('content')
<div class="page-header d-flex justify-content-between align-items-center flex-wrap gap-2">
  <div>
    <h1><i class="bi bi-shield-check me-2"></i>Garanties</h1>
    <nav aria-label="breadcrumb">
      <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Accueil</a></li>
        <li class="breadcrumb-item active">Garanties</li>
      </ol>
    </nav>
  </div>
</div>

<div class="card border-0 shadow-sm mb-4 filter-card">
  <div class="card-body">
    <form method="GET" action="{{ route('warranties.index') }}" class="row g-3 align-items-end">
      <div class="col-md-8">
        <label class="form-label small">Rechercher</label>
        <input type="text" name="search" class="form-control"
               placeholder="N° facture, client, téléphone, produit ou IMEI"
               value="{{ $filters['search'] ?? '' }}">
      </div>
      <div class="col-md-4 d-flex align-items-end gap-2">
        <button type="submit" class="btn btn-primary w-100"><i class="bi bi-search me-1"></i>Rechercher</button>
        <a href="{{ route('warranties.index') }}" class="btn btn-outline-secondary w-100">Réinitialiser</a>
      </div>
    </form>
  </div>
</div>

<div class="table-card">
  <div class="table-responsive">
    <table class="table table-hover mb-0">
      <thead>
        <tr>
          <th>Facture</th>
          <th>Client</th>
          <th>Produit</th>
          <th>Date de vente</th>
          <th>Durée</th>
          <th>Date de fin</th>
          <th>Statut</th>
        </tr>
      </thead>
      <tbody>
        @forelse($items as $item)
          @php $sale = $item->sale; @endphp
          <tr>
            <td>{{ $sale?->invoice?->invoice_number ?? $sale?->sale_number ?? '—' }}</td>
            <td>{{ $sale?->customer?->full_name ?? 'Client anonyme' }}</td>
            <td>
              {{ $item->product?->name ?? '—' }}
              @if($item->productImei)
                <br><small class="text-muted">IMEI : {{ $item->productImei->imei }}</small>
              @endif
            </td>
            <td>{{ $sale?->sale_date?->format('d/m/Y') ?? '—' }}</td>
            <td>{{ $sale?->warranty_duration?->label() ?? '—' }}</td>
            <td>{{ $sale?->warranty_end_date?->format('d/m/Y') ?? '—' }}</td>
            <td>
              @php $status = $sale?->warrantyStatus(); @endphp
              @if($status === 'active')
                <span class="badge bg-success">Active</span>
              @elseif($status === 'expired')
                <span class="badge bg-danger">Expirée</span>
              @else
                <span class="badge bg-secondary">Aucune garantie</span>
              @endif
            </td>
          </tr>
        @empty
          <tr>
            <td colspan="7" class="text-center text-muted py-4">Aucun produit trouvé.</td>
          </tr>
        @endforelse
      </tbody>
    </table>
  </div>
  <div class="p-3 border-top">{{ $items->links() }}</div>
</div>
@endsection

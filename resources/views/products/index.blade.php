@extends('layouts.dashboard')

@section('title', 'Produits')
@section('page-title', 'Gestion des produits')

@section('content')
<div class="page-header d-flex justify-content-between align-items-center flex-wrap gap-2">
  <div>
    <h1><i class="bi bi-controller me-2"></i>Produits</h1>
    <nav aria-label="breadcrumb">
      <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Accueil</a></li>
        <li class="breadcrumb-item active">Produits</li>
      </ol>
    </nav>
  </div>
  <a href="{{ route('products.create') }}" class="btn btn-primary">
    <i class="bi bi-plus-lg me-1"></i>Nouveau produit
  </a>
</div>

<div class="dashboard-summary-grid mb-4">
  <div class="dashboard-summary-card">
    <div class="summary-card-top">
      <span class="badge bg-primary">Totaux</span>
      <i class="bi bi-box-seam summary-icon text-primary"></i>
    </div>
    <div class="summary-card-value">{{ $products->total() }}</div>
    <div class="summary-card-label">Produits enregistrés</div>
  </div>

  <div class="dashboard-summary-card">
    <div class="summary-card-top">
      <span class="badge bg-info">Affichage</span>
      <i class="bi bi-eye summary-icon text-info"></i>
    </div>
    <div class="summary-card-value">{{ $products->count() }}</div>
    <div class="summary-card-label">Produits sur cette page</div>
  </div>

  <div class="dashboard-summary-card">
    <div class="summary-card-top">
      <span class="badge bg-success">Catégories</span>
      <i class="bi bi-tags summary-icon text-success"></i>
    </div>
    <div class="summary-card-value">{{ count($categories) }}</div>
    <div class="summary-card-label">Catégories actives</div>
  </div>

  <div class="dashboard-summary-card">
    <div class="summary-card-top">
      <span class="badge bg-warning">Marques</span>
      <i class="bi bi-award summary-icon text-warning"></i>
    </div>
    <div class="summary-card-value">{{ count($brands) }}</div>
    <div class="summary-card-label">Marques disponibles</div>
  </div>
</div>

<div class="card border-0 shadow-sm mb-4 filter-card">
  <div class="card-body">
    <form method="GET" action="{{ route('products.index') }}" id="filterForm" class="row g-3 align-items-end">
      <div class="col-md-3">
        <label class="form-label small">Rechercher</label>
        <input type="text" name="search" id="searchInput" class="form-control"
               placeholder="Nom, référence, code-barres..."
               value="{{ $filters['search'] ?? '' }}">
      </div>
      <div class="col-md-2">
        <label class="form-label small">Catégorie</label>
        <select name="category_id" class="form-select filter-input">
          <option value="">Toutes</option>
          @foreach($categories as $cat)
            <option value="{{ $cat->id }}" @selected(($filters['category_id'] ?? '') == $cat->id)>{{ $cat->name }}</option>
          @endforeach
        </select>
      </div>
      <div class="col-md-2">
        <label class="form-label small">Marque</label>
        <select name="brand" class="form-select filter-input">
          <option value="">Toutes</option>
          @foreach($brands as $brand)
            <option value="{{ $brand }}" @selected(($filters['brand'] ?? '') === $brand)>{{ $brand }}</option>
          @endforeach
        </select>
      </div>
      <div class="col-md-2">
        <label class="form-label small">Stock</label>
        <select name="stock_status" class="form-select filter-input">
          <option value="">Tous</option>
          <option value="low" @selected(($filters['stock_status'] ?? '') === 'low')>Stock faible</option>
          <option value="out" @selected(($filters['stock_status'] ?? '') === 'out')>Rupture</option>
        </select>
      </div>
      <div class="col-md-1">
        <label class="form-label small">Statut</label>
        <select name="is_active" class="form-select filter-input">
          <option value="">Tous</option>
          <option value="1" @selected(($filters['is_active'] ?? '') === '1')>Actifs</option>
          <option value="0" @selected(($filters['is_active'] ?? '') === '0')>Inactifs</option>
        </select>
      </div>
      <div class="col-md-2 d-flex align-items-end gap-2">
        <button type="submit" class="btn btn-primary w-100"><i class="bi bi-search me-1"></i>Filtrer</button>
        <a href="{{ route('products.index') }}" class="btn btn-outline-secondary w-100">Réinitialiser</a>
      </div>
    </form>
    <div id="productsLoading" class="text-muted small mt-3 d-none">Chargement des produits...</div>
  </div>
</div>

<div id="productsGridWrapper">
  @include('products.partials.grid', ['products' => $products])
</div>
<div id="paginationWrapper">
  @include('products.partials.pagination', ['products' => $products])
</div>
@endsection

@push('scripts')
<script>
(function () {
  const form = document.getElementById('filterForm');
  const searchInput = document.getElementById('searchInput');
  const gridWrapper = document.getElementById('productsGridWrapper');
  const paginationWrapper = document.getElementById('paginationWrapper');
  let debounceTimer;

  function fetchProducts(url) {
    const params = new URLSearchParams(new FormData(form));
    const fetchUrl = url || '{{ route('products.index') }}?' + params.toString();

    setLoading(true);
    fetch(fetchUrl, {
      headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }
    })
    .then(response => {
      if (!response.ok) throw new Error('Erreur réseau');
      return response.json();
    })
    .then(data => {
      if (data.html && gridWrapper) {
        gridWrapper.innerHTML = data.html;
      }
      if (data.pagination && paginationWrapper) {
        paginationWrapper.innerHTML = data.pagination;
      }
      bindPaginationLinks();
    })
    .catch(err => console.error('Erreur chargement produits:', err))
    .finally(() => setLoading(false));
  }

  function bindPaginationLinks() {
    document.querySelectorAll('#paginationWrapper a.page-link').forEach(link => {
      link.addEventListener('click', function (e) {
        e.preventDefault();
        fetchProducts(this.href);
      });
    });
  }

  function setLoading(show) {
    const loading = document.getElementById('productsLoading');

    if (!loading) return;
    loading.classList.toggle('d-none', !show);
  }

  if (searchInput) {
    searchInput.addEventListener('input', function () {
      clearTimeout(debounceTimer);
      debounceTimer = setTimeout(() => fetchProducts(), 400);
    });
  }

  document.querySelectorAll('.filter-input').forEach(el => {
    el.addEventListener('change', () => fetchProducts());
  });

  bindPaginationLinks();
})();
</script>
@endpush

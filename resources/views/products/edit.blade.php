@extends('layouts.dashboard')

@section('title', 'Modifier produit')
@section('page-title', 'Modifier produit')

@section('content')
<div class="page-header">
  <h1><i class="bi bi-pencil me-2"></i>Modifier : {{ $product->name }}</h1>
  <nav aria-label="breadcrumb">
    <ol class="breadcrumb">
      <li class="breadcrumb-item"><a href="{{ route('products.index') }}">Produits</a></li>
      <li class="breadcrumb-item active">Modifier</li>
    </ol>
  </nav>
</div>

<div class="form-shell u-animate">
  <form method="POST" action="{{ route('products.update', $product) }}" enctype="multipart/form-data" data-ui-form novalidate>
    @csrf @method('PUT')
    <div class="form-card">
      <div class="form-card__header">
        <h2><i class="bi bi-controller"></i>Fiche produit</h2>
        <p class="form-card__subtitle">Mettez à jour les informations ci-dessous. Les champs marqués <span class="req">*</span> sont obligatoires.</p>
      </div>
      <div class="form-card__body">
        @include('products._form', ['product' => $product, 'categories' => $categories, 'suppliers' => $suppliers])
      </div>
      <div class="form-card__footer">
        <a href="{{ route('products.index') }}" class="btn btn-outline-secondary"><i class="bi bi-x-lg me-1"></i>Annuler</a>
        <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg me-1"></i>Mettre à jour</button>
      </div>
    </div>
  </form>
</div>
@endsection

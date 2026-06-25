@extends('layouts.dashboard')

@section('title', 'Nouveau client')
@section('page-title', 'Nouveau client')

@section('content')
<div class="page-header">
  <h1><i class="bi bi-plus-circle me-2"></i>Nouveau client</h1>
  <nav aria-label="breadcrumb">
    <ol class="breadcrumb">
      <li class="breadcrumb-item"><a href="{{ route('customers.index') }}">Clients</a></li>
      <li class="breadcrumb-item active">Nouveau</li>
    </ol>
  </nav>
</div>

<div class="form-shell u-animate">
  <form method="POST" action="{{ route('customers.store') }}" data-ui-form novalidate>
    @csrf
    <div class="form-card">
      <div class="form-card__header">
        <h2><i class="bi bi-person-plus"></i>Fiche client</h2>
        <p class="form-card__subtitle">Renseignez les coordonnées du client. Les champs marqués <span class="req">*</span> sont obligatoires.</p>
      </div>
      <div class="form-card__body">
        @include('customers._form')
      </div>
      <div class="form-card__footer">
        <a href="{{ route('customers.index') }}" class="btn btn-outline-secondary"><i class="bi bi-x-lg me-1"></i>Annuler</a>
        <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg me-1"></i>Enregistrer le client</button>
      </div>
    </div>
  </form>
</div>
@endsection

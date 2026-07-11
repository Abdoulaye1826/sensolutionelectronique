@extends('layouts.guest')

@section('title', 'Connexion')

@section('content')
@if(request()->boolean('expired'))
  <div class="auth-alert-expired mb-4" role="alert">
    <i class="bi bi-clock-history"></i>
    <span>Votre session a expiré. Veuillez vous reconnecter.</span>
  </div>
@endif
<form method="POST" action="{{ route('login') }}">
  @csrf

  <div class="mb-3">
    <label for="email" class="form-label fw-medium">Adresse email</label>
    <div class="input-group">
      <span class="input-group-text"><i class="bi bi-envelope"></i></span>
      <input type="email" class="form-control @error('email') is-invalid @enderror"
             id="email" name="email" value="{{ old('email') }}"
             placeholder="admin@gaming-store.local" required autofocus>
    </div>
    @error('email')
      <div class="text-danger small mt-1">{{ $message }}</div>
    @enderror
  </div>

  <div class="mb-3">
    <label for="password" class="form-label fw-medium">Mot de passe</label>
    <div class="input-group">
      <span class="input-group-text"><i class="bi bi-lock"></i></span>
      <input type="password" class="form-control @error('password') is-invalid @enderror"
             id="password" name="password" placeholder="••••••••" required>
    </div>
    @error('password')
      <div class="text-danger small mt-1">{{ $message }}</div>
    @enderror
  </div>

  <div class="d-flex justify-content-between align-items-center mb-4">
    <div class="form-check">
      <input class="form-check-input" type="checkbox" name="remember" id="remember">
      <label class="form-check-label small" for="remember">Se souvenir de moi</label>
    </div>
    @if (Route::has('password.request'))
      <a href="{{ route('password.request') }}" class="small text-decoration-none">Mot de passe oublié ?</a>
    @endif
  </div>

  <button type="submit" class="btn btn-primary w-100 py-2 fw-medium">
    <i class="bi bi-box-arrow-in-right me-2"></i>Se connecter
  </button>
</form>
@endsection

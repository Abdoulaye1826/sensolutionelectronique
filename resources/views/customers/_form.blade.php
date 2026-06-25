<div data-form-sections>

  {{-- ── Section 1 : Identité ────────────────────────────────── --}}
  <div class="form-section">
    <button type="button" class="form-section__header" data-toggle-section aria-expanded="true" aria-controls="section-identite">
      <span class="form-section__title"><i class="bi bi-person"></i>Identité</span>
      <i class="bi bi-chevron-down chevron"></i>
    </button>
    <div class="form-section__body" id="section-identite">
      <div class="row">
        <div class="col-md-6 field-group">
          <label for="full_name" class="form-label">Nom complet <span class="req">*</span></label>
          <div class="field-input-wrap">
            <i class="bi bi-person-circle field-icon"></i>
            <input type="text" class="form-control has-icon @error('full_name') is-invalid @enderror"
                   id="full_name" name="full_name" value="{{ old('full_name', $customer->full_name ?? '') }}"
                   placeholder="Ex : Aminata Diop" required>
            <i class="bi bi-check-circle-fill valid-feedback-icon"></i>
            <i class="bi bi-exclamation-circle-fill invalid-feedback-icon"></i>
          </div>
          @error('full_name')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
        </div>
        <div class="col-md-6 field-group">
          <label for="phone" class="form-label">Téléphone <span class="req">*</span></label>
          <div class="field-input-wrap">
            <i class="bi bi-telephone field-icon"></i>
            <input type="tel" class="form-control has-icon @error('phone') is-invalid @enderror"
                   id="phone" name="phone" value="{{ old('phone', $customer->phone ?? '') }}"
                   placeholder="+221 77 123 45 67" pattern="^[0-9+ ]{7,20}$" required>
          </div>
          @error('phone')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
          <div class="form-text">Utilisé pour l'envoi WhatsApp des factures et bons d'échange.</div>
        </div>
      </div>

      <div class="row mb-0">
        <div class="col-md-6 field-group">
          <label for="email" class="form-label">Email</label>
          <div class="field-input-wrap">
            <i class="bi bi-envelope field-icon"></i>
            <input type="email" class="form-control has-icon @error('email') is-invalid @enderror"
                   id="email" name="email" value="{{ old('email', $customer->email ?? '') }}"
                   placeholder="client@email.com">
          </div>
          @error('email')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
        </div>
        <div class="col-md-6 field-group mb-0">
          <label for="registered_at" class="form-label">Date d'inscription <span class="req">*</span></label>
          <input type="date" class="form-control @error('registered_at') is-invalid @enderror"
                 id="registered_at" name="registered_at"
                 value="{{ old('registered_at', optional($customer->registered_at ?? null)->format('Y-m-d') ?? now()->format('Y-m-d')) }}" required>
          @error('registered_at')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
      </div>
    </div>
  </div>

  {{-- ── Section 2 : Localisation ────────────────────────────── --}}
  <div class="form-section">
    <button type="button" class="form-section__header" data-toggle-section aria-expanded="true" aria-controls="section-localisation">
      <span class="form-section__title"><i class="bi bi-geo-alt"></i>Localisation</span>
      <i class="bi bi-chevron-down chevron"></i>
    </button>
    <div class="form-section__body" id="section-localisation">
      <div class="field-group">
        <label for="city" class="form-label">Ville</label>
        <div class="field-input-wrap">
          <i class="bi bi-buildings field-icon"></i>
          <input type="text" class="form-control has-icon @error('city') is-invalid @enderror"
                 id="city" name="city" value="{{ old('city', $customer->city ?? '') }}" placeholder="Dakar, Thiès, Saint-Louis...">
        </div>
        @error('city')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
      </div>

      <div class="field-group mb-0">
        <label for="address" class="form-label">Adresse</label>
        <textarea class="form-control @error('address') is-invalid @enderror"
                  id="address" name="address" rows="3"
                  placeholder="Quartier, rue, repère...">{{ old('address', $customer->address ?? '') }}</textarea>
        @error('address')<div class="invalid-feedback">{{ $message }}</div>@enderror
      </div>
    </div>
  </div>

</div>

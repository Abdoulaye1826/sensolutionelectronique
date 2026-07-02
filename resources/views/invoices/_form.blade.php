<div data-form-sections>

  {{-- ── Section 1 : Vente associée ──────────────────────────── --}}
  <div class="form-section">
    <button type="button" class="form-section__header" data-toggle-section aria-expanded="true" aria-controls="section-vente">
      <span class="form-section__title"><i class="bi bi-cart-check"></i>Vente associée</span>
      <i class="bi bi-chevron-down chevron"></i>
    </button>
    <div class="form-section__body" id="section-vente">
      <div class="row">
        <div class="col-md-6 field-group">
          <label for="sale_id" class="form-label">Vente</label>
          <div class="searchable-select" data-searchable-select>
            <input type="text" class="form-control" data-select-filter autocomplete="off"
                   placeholder="Rechercher une vente..." aria-label="Filtrer les ventes">
            <select name="sale_id" id="sale_id" class="form-select @error('sale_id') is-invalid @enderror" required>
              <option value="">Sélectionnez une vente</option>
              @foreach($sales as $saleOption)
                <option value="{{ $saleOption->id }}" @selected(old('sale_id', $invoice?->sale_id) == $saleOption->id)>
                  {{ $saleOption->sale_number }} — {{ $saleOption->customer?->full_name ?? 'Client anonyme' }}
                </option>
              @endforeach
            </select>
          </div>
          @error('sale_id')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
        </div>

        <div class="col-md-6 field-group">
          <label for="customer_id" class="form-label">Client</label>
          <select name="customer_id" id="customer_id" class="form-select @error('customer_id') is-invalid @enderror" disabled>
            <option value="">Sélectionnez une vente</option>
            @foreach($customers as $customer)
              <option value="{{ $customer->id }}" @selected(old('customer_id', $invoice?->customer_id) == $customer->id)>
                {{ $customer->full_name }}
              </option>
            @endforeach
          </select>
          <div class="form-text">Le client est récupéré automatiquement depuis la vente sélectionnée.</div>
          @error('customer_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
      </div>

      <div class="field-group mb-0">
        <label for="issued_at" class="form-label">Date d'émission</label>
        <input type="date" name="issued_at" id="issued_at"
               value="{{ old('issued_at', $invoice?->issued_at?->format('Y-m-d') ?? now()->format('Y-m-d')) }}"
               class="form-control @error('issued_at') is-invalid @enderror" required>
        @error('issued_at')<div class="invalid-feedback">{{ $message }}</div>@enderror
      </div>
    </div>
  </div>

  {{-- ── Section 2 : Montants ────────────────────────────────── --}}
  <div class="form-section">
    <button type="button" class="form-section__header" data-toggle-section aria-expanded="true" aria-controls="section-montants">
      <span class="form-section__title"><i class="bi bi-cash-coin"></i>Montants</span>
      <i class="bi bi-chevron-down chevron"></i>
    </button>
    <div class="form-section__body" id="section-montants">
      <div class="row mb-0">
        <div class="col-md-6 field-group mb-md-0">
          <label for="subtotal_ht" class="form-label">Sous-total</label>
          <div class="field-input-wrap">
            <input type="number" step="0.01" name="subtotal_ht" id="subtotal_ht"
                   value="{{ old('subtotal_ht', $invoice?->subtotal_ht ?? 0) }}"
                   class="form-control @error('subtotal_ht') is-invalid @enderror">
          </div>
          @error('subtotal_ht')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
          <div class="form-text">FCFA, avant remise éventuelle.</div>
        </div>
        <div class="col-md-6 field-group mb-0">
          <label for="total_ttc" class="form-label">Total final</label>
          <input type="number" step="0.01" name="total_ttc" id="total_ttc"
                 value="{{ old('total_ttc', $invoice?->total_ttc ?? 0) }}"
                 class="form-control @error('total_ttc') is-invalid @enderror">
          @error('total_ttc')<div class="invalid-feedback">{{ $message }}</div>@enderror
          <div class="form-text">FCFA, montant net à payer par le client.</div>
        </div>
      </div>
    </div>
  </div>

  {{-- ── Section 3 : Statut & document ───────────────────────── --}}
  <div class="form-section">
    <button type="button" class="form-section__header" data-toggle-section aria-expanded="true" aria-controls="section-statut">
      <span class="form-section__title"><i class="bi bi-flag"></i>Statut &amp; document</span>
      <i class="bi bi-chevron-down chevron"></i>
    </button>
    <div class="form-section__body" id="section-statut">
      <div class="row mb-0">
        <div class="col-md-4 field-group mb-md-0">
          <label for="status" class="form-label">Statut</label>
          <select name="status" id="status" class="form-select @error('status') is-invalid @enderror">
            <option value="issued" @selected(old('status', $invoice?->status->value ?? 'issued') === 'issued')>Non payé</option>
            <option value="partial" @selected(old('status', $invoice?->status->value ?? '') === 'partial')>Partiellement payée</option>
            <option value="paid" @selected(old('status', $invoice?->status->value ?? '') === 'paid')>Payée</option>
            <option value="cancelled" @selected(old('status', $invoice?->status->value ?? '') === 'cancelled')>Annulée</option>
          </select>
          @error('status')<div class="invalid-feedback">{{ $message }}</div>@enderror
          @if($invoice?->payments?->isNotEmpty())
            <div class="form-text">Ce statut est recalculé automatiquement à chaque paiement enregistré ci-dessous.</div>
          @endif
        </div>

        <div class="col-md-8 field-group mb-0">
          <label for="pdf_path" class="form-label">Chemin du PDF</label>
          <div class="field-input-wrap">
            <i class="bi bi-file-earmark-pdf field-icon"></i>
            <input type="text" name="pdf_path" id="pdf_path" value="{{ old('pdf_path', $invoice?->pdf_path ?? '') }}"
                   class="form-control has-icon @error('pdf_path') is-invalid @enderror" placeholder="Optionnel — généré automatiquement sinon">
          </div>
          @error('pdf_path')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
        </div>
      </div>
    </div>
  </div>

</div>

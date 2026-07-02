<div data-form-sections>

  {{-- ── Section : Image & statut ──────────────────────────── --}}
  <div class="form-section">
    <button type="button" class="form-section__header" data-toggle-section aria-expanded="true" aria-controls="section-image-statut">
      <span class="form-section__title"><i class="bi bi-image"></i>Image &amp; statut</span>
      <i class="bi bi-chevron-down chevron"></i>
    </button>
    <div class="form-section__body" id="section-image-statut">
      <div class="row align-items-start">
        <div class="col-md-7 field-group mb-md-0">
          <label for="image" class="form-label">Image produit</label>
          <label class="image-dropzone" for="image" tabindex="0">
            <input type="file" id="image" name="image" accept="image/jpeg,image/png,image/jpg,image/webp">
            <div class="image-dropzone__icon"><i class="bi bi-cloud-arrow-up"></i></div>
            <div class="image-dropzone__text"><strong>Cliquez</strong> ou glissez-déposez une image ici<br>JPG, PNG ou WEBP</div>
          </label>
          @error('image')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror

          @if(isset($product) && $product->image)
            <div class="image-preview" style="display:flex">
              <img src="{{ asset('storage/'.$product->image) }}" alt="{{ $product->name }}" loading="lazy">
              <button type="button" class="image-preview__remove" data-remove-target="remove_image">
                <i class="bi bi-trash me-1"></i>Supprimer l'image
              </button>
              <input type="checkbox" id="remove_image" name="remove_image" value="1" class="d-none">
            </div>
          @else
            <div class="image-preview" style="display:none"></div>
          @endif
        </div>
        <div class="col-md-5 field-group">
          <label class="form-label">Disponibilité</label>
          <div class="form-check form-switch fs-6 ps-1">
            <input class="form-check-input" type="checkbox" id="is_active" name="is_active" value="1" role="switch"
                   {{ old('is_active', $product->is_active ?? true) ? 'checked' : '' }}>
            <label class="form-check-label" for="is_active">Produit actif (disponible à la vente)</label>
          </div>
          <div class="form-text">Désactivez-le pour le masquer du catalogue sans le supprimer.</div>
        </div>
        <div class="col-md-5 field-group mb-0">
          <label class="form-label">Suivi IMEI</label>
          <div class="form-check form-switch fs-6 ps-5">
            <input class="form-check-input" type="checkbox" id="tracks_imei" name="tracks_imei" value="1" role="switch"
                   {{ old('tracks_imei', $product->tracks_imei ?? false) ? 'checked' : '' }}
                   {{ isset($product) && $product->exists && $product->imeis->isNotEmpty() ? 'disabled' : '' }}>
            <label class="form-check-label" for="tracks_imei">Produit avec suivi IMEI (téléphones)</label>
          </div>
          @if(isset($product) && $product->exists && $product->imeis->isNotEmpty())
            <input type="hidden" name="tracks_imei" value="1">
            <div class="form-text">Verrouillé : des IMEI sont déjà enregistrés pour ce produit.</div>
          @else
            <div class="form-text">Le stock sera géré unité par unité (un IMEI = un appareil), pas en quantité globale.</div>
          @endif
        </div>
      </div>
    </div>
  </div>

  {{-- ── Section 1 : Identification ──────────────────────────── --}}
  <div class="form-section">
    <button type="button" class="form-section__header" data-toggle-section aria-expanded="true" aria-controls="section-identification">
      <span class="form-section__title"><i class="bi bi-tag"></i>Identification</span>
      <i class="bi bi-chevron-down chevron"></i>
    </button>
    <div class="form-section__body" id="section-identification">
      <div class="row">
        <div class="col-md-8 field-group">
          <label for="name" class="form-label">Nom du produit <span class="req">*</span></label>
          <div class="field-input-wrap">
            <i class="bi bi-controller field-icon"></i>
            <input type="text" class="form-control has-icon @error('name') is-invalid @enderror"
                   id="name" name="name" value="{{ old('name', $product->name ?? '') }}"
                   placeholder="Ex : PlayStation 5 Slim" required>
            <i class="bi bi-check-circle-fill valid-feedback-icon"></i>
            <i class="bi bi-exclamation-circle-fill invalid-feedback-icon"></i>
          </div>
          @error('name')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
        </div>
        <div class="col-md-4 field-group">
          <label for="reference" class="form-label">Référence <span class="req">*</span></label>
          <input type="text" class="form-control @error('reference') is-invalid @enderror"
                 id="reference" name="reference" value="{{ old('reference', $product->reference ?? '') }}"
                 placeholder="Ex : PS5-SLIM-001" required>
          @error('reference')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
      </div>

      <div class="row">
        <div class="col-md-6 field-group">
          <label for="barcode" class="form-label">Code-barres</label>
          <div class="field-input-wrap">
            <i class="bi bi-upc-scan field-icon"></i>
            <input type="text" class="form-control has-icon @error('barcode') is-invalid @enderror"
                   id="barcode" name="barcode" value="{{ old('barcode', $product->barcode ?? '') }}"
                   placeholder="Scanner ou saisir le code-barres">
          </div>
          @error('barcode')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
        </div>
        <div class="col-md-6 field-group">
          <label for="brand" class="form-label">Marque</label>
          <input type="text" class="form-control @error('brand') is-invalid @enderror"
                 id="brand" name="brand" value="{{ old('brand', $product->brand ?? '') }}"
                 placeholder="Sony, Nintendo, Microsoft...">
          @error('brand')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
      </div>

      <div class="field-group mb-0">
        <label for="description" class="form-label">Description</label>
        <textarea class="form-control @error('description') is-invalid @enderror"
                  id="description" name="description" rows="3"
                  placeholder="Caractéristiques, état, accessoires inclus...">{{ old('description', $product->description ?? '') }}</textarea>
        @error('description')<div class="invalid-feedback">{{ $message }}</div>@enderror
        <div class="form-text">Quelques lignes suffisent — ce texte apparaît dans la fiche produit.</div>
      </div>
    </div>
  </div>

  {{-- ── Section 2 : Catégorisation ──────────────────────────── --}}
  <div class="form-section">
    <button type="button" class="form-section__header" data-toggle-section aria-expanded="true" aria-controls="section-categorisation">
      <span class="form-section__title"><i class="bi bi-bookmarks"></i>Catégorisation</span>
      <i class="bi bi-chevron-down chevron"></i>
    </button>
    <div class="form-section__body" id="section-categorisation">
      <div class="row">
        <div class="col-md-6 field-group">
          <div class="d-flex align-items-center justify-content-between">
            <label for="category_id" class="form-label mb-0">Catégorie <span class="req">*</span></label>
            <button type="button" class="btn btn-sm btn-outline-primary py-0 px-2" data-bs-toggle="modal" data-bs-target="#newCategoryModal" title="Ajouter une catégorie">
              <i class="bi bi-plus-lg"></i>
            </button>
          </div>
          <div class="searchable-select mt-1" data-searchable-select>
            <input type="text" class="form-control" data-select-filter autocomplete="off"
                   placeholder="Rechercher une catégorie..." aria-label="Filtrer les catégories">
            <select id="category_id" name="category_id" class="form-select @error('category_id') is-invalid @enderror" required>
              <option value="">— Sélectionner —</option>
              @foreach($categories as $cat)
                <option value="{{ $cat->id }}" @selected(old('category_id', $product->category_id ?? '') == $cat->id)>
                  {{ $cat->name }}
                </option>
              @endforeach
            </select>
          </div>
          @error('category_id')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
        </div>
        <div class="col-md-6 field-group">
          <div class="d-flex align-items-center justify-content-between">
            <label for="supplier_id" class="form-label mb-0">Fournisseur</label>
            <button type="button" class="btn btn-sm btn-outline-primary py-0 px-2" data-bs-toggle="modal" data-bs-target="#newSupplierModal" title="Ajouter un fournisseur">
              <i class="bi bi-plus-lg"></i>
            </button>
          </div>
          <div class="searchable-select mt-1" data-searchable-select>
            <input type="text" class="form-control" data-select-filter autocomplete="off"
                   placeholder="Rechercher un fournisseur..." aria-label="Filtrer les fournisseurs">
            <select id="supplier_id" name="supplier_id" class="form-select @error('supplier_id') is-invalid @enderror">
              <option value="">— Aucun —</option>
              @foreach($suppliers as $supplier)
                <option value="{{ $supplier->id }}" @selected(old('supplier_id', $product->supplier_id ?? '') == $supplier->id)>
                  {{ $supplier->name }}
                </option>
              @endforeach
            </select>
          </div>
          @error('supplier_id')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
        </div>
      </div>
    </div>
  </div>

  {{-- ── Section 3 : Prix & stock ────────────────────────────── --}}
  <div class="form-section">
    <button type="button" class="form-section__header" data-toggle-section aria-expanded="true" aria-controls="section-prix-stock">
      <span class="form-section__title"><i class="bi bi-cash-coin"></i>Prix &amp; stock</span>
      <i class="bi bi-chevron-down chevron"></i>
    </button>
    <div class="form-section__body" id="section-prix-stock">
      <div class="row">
        <div class="col-sm-6 col-md-4 field-group">
          <label for="purchase_price" class="form-label">Prix achat <span class="req">*</span></label>
          <div class="field-input-wrap">
            <input type="number" step="0.01" min="0" class="form-control @error('purchase_price') is-invalid @enderror"
                   id="purchase_price" name="purchase_price" value="{{ old('purchase_price', $product->purchase_price ?? '') }}"
                   placeholder="0" required>
          </div>
          @error('purchase_price')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
          <div class="form-text">FCFA, hors frais annexes.</div>
        </div>
        <div class="col-sm-6 col-md-4 field-group">
          <label for="sale_price" class="form-label">Prix vente client <span class="req">*</span></label>
          <input type="number" step="0.01" min="0" class="form-control @error('sale_price') is-invalid @enderror"
                 id="sale_price" name="sale_price" value="{{ old('sale_price', $product->sale_price ?? '') }}"
                 placeholder="0" required>
          @error('sale_price')<div class="invalid-feedback">{{ $message }}</div>@enderror
          <div class="form-text">FCFA, prix affiché au client.</div>
        </div>
        <div class="col-sm-6 col-md-4 field-group">
          <label for="supplier_sale_price" class="form-label">Prix vente revendeur</label>
          <input type="number" step="0.01" min="0" class="form-control @error('supplier_sale_price') is-invalid @enderror"
                 id="supplier_sale_price" name="supplier_sale_price" value="{{ old('supplier_sale_price', $product->supplier_sale_price ?? '') }}"
                 placeholder="Optionnel">
          @error('supplier_sale_price')<div class="invalid-feedback">{{ $message }}</div>@enderror
          <div class="form-text">FCFA, appliqué quand le client est un fournisseur revendeur.</div>
        </div>
      </div>

      <div class="row">
        <div class="col-sm-6 field-group">
          <label for="stock_quantity" class="form-label">Stock <span class="req">*</span></label>
          <input type="number" min="0" class="form-control @error('stock_quantity') is-invalid @enderror"
                 id="stock_quantity" name="stock_quantity" value="{{ old('stock_quantity', $product->stock_quantity ?? 0) }}"
                 {{ old('tracks_imei', $product->tracks_imei ?? false) ? 'readonly' : '' }} required>
          @error('stock_quantity')<div class="invalid-feedback">{{ $message }}</div>@enderror
          <div class="form-text d-none" id="stockImeiNote">Calculé automatiquement à partir des IMEI disponibles (section ci-dessous).</div>
        </div>
        <div class="col-sm-6 field-group">
          <label for="minimum_stock" class="form-label">Seuil d'alerte <span class="req">*</span></label>
          <input type="number" min="0" class="form-control @error('minimum_stock') is-invalid @enderror"
                 id="minimum_stock" name="minimum_stock" value="{{ old('minimum_stock', $product->minimum_stock ?? 5) }}" required>
          @error('minimum_stock')<div class="invalid-feedback">{{ $message }}</div>@enderror
          <div class="form-text">Déclenche l'alerte « stock faible ».</div>
        </div>
      </div>

      <div class="row" id="marginPreviewRow" style="display:none;">
        <div class="col-12">
          <div class="d-flex align-items-center gap-3 p-3 rounded-3" style="background:var(--surface);border:1px solid var(--border);">
            <i class="bi bi-graph-up-arrow fs-4" style="color:var(--copper);"></i>
            <div>
              <div class="small text-muted">Marge réalisée sur ce produit</div>
              <div class="fw-semibold">
                <span id="marginAmount">0</span> FCFA
                <span class="text-muted">·</span>
                <span id="marginRate">0</span> %
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  {{-- ── Section : IMEI (téléphones) ─────────────────────────── --}}
  <div class="form-section" id="imeiSection" style="display:none;">
    <button type="button" class="form-section__header" data-toggle-section aria-expanded="true" aria-controls="section-imei">
      <span class="form-section__title"><i class="bi bi-phone"></i>IMEI (téléphones)</span>
      <span class="form-section__badge" id="imeiCountBadge">0</span>
      <i class="bi bi-chevron-down chevron"></i>
    </button>
    <div class="form-section__body" id="section-imei">
      @if(isset($product) && $product->exists)
        <div class="table-responsive mb-3" id="imeiListWrap" style="{{ $product->imeis->isEmpty() ? 'display:none' : '' }}">
          <table class="table table-sm table-hover mb-0">
            <thead>
              <tr>
                <th>IMEI</th>
                <th>Statut</th>
                <th>Entré le</th>
                <th class="text-end">Action</th>
              </tr>
            </thead>
            <tbody id="imeiTableBody">
              @foreach($product->imeis as $imei)
                <tr>
                  <td class="font-monospace">{{ $imei->imei }}</td>
                  <td><span class="badge {{ $imei->status->badgeClass() }}">{{ $imei->status->label() }}</span></td>
                  <td>{{ $imei->created_at->format('d/m/Y') }}</td>
                  <td class="text-end">
                    @if($imei->status->value === 'available')
                      <button type="button" class="btn btn-sm btn-outline-danger delete-imei-btn"
                              data-url="{{ route('imeis.destroy', $imei) }}">
                        <i class="bi bi-trash"></i>
                      </button>
                    @else
                      <span class="text-muted small">—</span>
                    @endif
                  </td>
                </tr>
              @endforeach
            </tbody>
          </table>
        </div>

        <label class="form-label">Ajouter des IMEI</label>
        <div id="newImeiInputs" class="mb-2">
          <div class="input-group mb-2 new-imei-row">
            <span class="input-group-text"><i class="bi bi-upc-scan"></i></span>
            <input type="text" class="form-control new-imei-input" placeholder="Saisir ou scanner un IMEI (14 à 17 chiffres)" inputmode="numeric" autocomplete="off">
            <button type="button" class="btn btn-outline-danger remove-imei-row" tabindex="-1"><i class="bi bi-x-lg"></i></button>
          </div>
        </div>
        <div class="d-flex gap-2 align-items-center">
          <button type="button" class="btn btn-sm btn-outline-primary" id="addImeiRowButton">
            <i class="bi bi-plus-lg"></i> Ajouter un IMEI
          </button>
          <button type="button" class="btn btn-sm btn-primary" id="saveImeiButton">
            <i class="bi bi-cloud-arrow-up me-1"></i>Enregistrer les IMEI
          </button>
        </div>
        <div class="invalid-feedback d-block" id="imei_error"></div>
        <div class="form-text">Chaque ligne = un IMEI. Utilisez une douchette code-barres : elle se comporte comme un clavier et valide la ligne automatiquement.</div>
      @else
        <div class="alert alert-info mb-0">
          <i class="bi bi-info-circle me-1"></i>Enregistrez d'abord le produit : vous pourrez ensuite ajouter ses IMEI depuis la page de modification.
        </div>
      @endif
    </div>
  </div>

</div>

@push('scripts')
<script>
  document.addEventListener('DOMContentLoaded', function () {
    const purchaseInput = document.getElementById('purchase_price');
    const saleInput = document.getElementById('sale_price');
    const marginRow = document.getElementById('marginPreviewRow');
    const marginAmountEl = document.getElementById('marginAmount');
    const marginRateEl = document.getElementById('marginRate');

    if (!purchaseInput || !saleInput || !marginRow) return;

    function updateMarginPreview() {
      const purchase = parseFloat(purchaseInput.value);
      const sale = parseFloat(saleInput.value);

      if (isNaN(purchase) || isNaN(sale) || purchase <= 0) {
        marginRow.style.display = 'none';
        return;
      }

      const margin = sale - purchase;
      const rate = (margin / purchase) * 100;

      marginAmountEl.textContent = margin.toLocaleString('fr-FR', { maximumFractionDigits: 0 });
      marginRateEl.textContent = rate.toLocaleString('fr-FR', { maximumFractionDigits: 1 });

      marginAmountEl.parentElement.style.color = margin < 0 ? 'var(--danger)' : '';
      marginRow.style.display = '';
    }

    purchaseInput.addEventListener('input', updateMarginPreview);
    saleInput.addEventListener('input', updateMarginPreview);
    updateMarginPreview();
  });

  /**
   * Création rapide d'une catégorie ou d'un fournisseur depuis le formulaire
   * produit, sans quitter la page (modale + AJAX).
   */
  function setupQuickCreateModal({ formId, buttonId, url, selectId, fieldOrder, errorPrefix }) {
    const form = document.getElementById(formId);
    const button = document.getElementById(buttonId);
    const select = document.getElementById(selectId);
    if (!form || !button || !select) return;

    const modalEl = form.closest('.modal');

    // Focalise le premier champ une fois la modale réellement affichée
    // (un autofocus HTML natif serait bloqué par Bootstrap : la modale est
    // encore aria-hidden au moment où le navigateur tente de focaliser).
    modalEl?.addEventListener('shown.bs.modal', function () {
      form.querySelector('input, textarea, select')?.focus();
    });

    button.addEventListener('click', async function () {
      fieldOrder.forEach((field) => {
        const errorEl = document.getElementById(`${errorPrefix}_${field}_error`);
        const inputEl = form.querySelector(`[name="${field}"]`);
        if (errorEl) errorEl.textContent = '';
        if (inputEl) inputEl.classList.remove('is-invalid');
      });

      button.disabled = true;
      const originalHtml = button.innerHTML;
      button.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Création...';

      try {
        const formData = new FormData(form);
        const response = await fetch(url, {
          method: 'POST',
          headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
            'Accept': 'application/json',
          },
          body: formData,
        });

        const data = await response.json();

        if (!response.ok) {
          if (data.errors) {
            Object.entries(data.errors).forEach(([field, messages]) => {
              const inputEl = form.querySelector(`[name="${field}"]`);
              const errorEl = document.getElementById(`${errorPrefix}_${field}_error`);
              if (inputEl) inputEl.classList.add('is-invalid');
              if (errorEl) errorEl.textContent = messages.join(' ');
            });
          } else if (window.UiToast) {
            window.UiToast.show("Impossible de créer l'élément.", 'error');
          }
          return;
        }

        const option = document.createElement('option');
        option.value = data.id;
        option.textContent = data.name;
        option.selected = true;
        select.appendChild(option);
        select.value = data.id;
        select.dispatchEvent(new Event('change', { bubbles: true }));

        const modal = bootstrap.Modal.getInstance(modalEl);
        modal?.hide();
        form.reset();

        if (window.UiToast) {
          window.UiToast.show('Ajouté avec succès.', 'success');
        }
      } catch (error) {
        if (window.UiToast) {
          window.UiToast.show("Erreur lors de la création.", 'error');
        }
      } finally {
        button.disabled = false;
        button.innerHTML = originalHtml;
      }
    });
  }

  document.addEventListener('DOMContentLoaded', function () {
    setupQuickCreateModal({
      formId: 'newCategoryForm',
      buttonId: 'saveNewCategoryButton',
      url: '{{ route('categories.store') }}',
      selectId: 'category_id',
      fieldOrder: ['name', 'description', 'is_active'],
      errorPrefix: 'new_category',
    });

    setupQuickCreateModal({
      formId: 'newSupplierForm',
      buttonId: 'saveNewSupplierButton',
      url: '{{ route('suppliers.store') }}',
      selectId: 'supplier_id',
      fieldOrder: ['name', 'phone', 'email', 'address', 'country', 'is_active'],
      errorPrefix: 'new_supplier',
    });

    // ───────────────────────────────────────────────────────────────
    // Suivi IMEI : affichage conditionnel + gestion des IMEI
    // ───────────────────────────────────────────────────────────────
    const tracksImeiCheckbox = document.getElementById('tracks_imei');
    const imeiSection = document.getElementById('imeiSection');
    const stockQuantityInput = document.getElementById('stock_quantity');
    const stockImeiNote = document.getElementById('stockImeiNote');

    function syncImeiVisibility() {
      const tracksImei = tracksImeiCheckbox?.checked;
      if (imeiSection) imeiSection.style.display = tracksImei ? '' : 'none';
      if (stockQuantityInput) stockQuantityInput.readOnly = !!tracksImei;
      if (stockImeiNote) stockImeiNote.classList.toggle('d-none', !tracksImei);
    }

    if (tracksImeiCheckbox && !tracksImeiCheckbox.disabled) {
      tracksImeiCheckbox.addEventListener('change', syncImeiVisibility);
    }
    syncImeiVisibility();

    const newImeiInputsWrap = document.getElementById('newImeiInputs');
    const addImeiRowButton = document.getElementById('addImeiRowButton');
    const saveImeiButton = document.getElementById('saveImeiButton');
    const imeiTableBody = document.getElementById('imeiTableBody');
    const imeiListWrap = document.getElementById('imeiListWrap');
    const imeiCountBadge = document.getElementById('imeiCountBadge');
    const imeiErrorEl = document.getElementById('imei_error');

    function newImeiRowTemplate() {
      const wrap = document.createElement('div');
      wrap.className = 'input-group mb-2 new-imei-row';
      wrap.innerHTML = `
        <span class="input-group-text"><i class="bi bi-upc-scan"></i></span>
        <input type="text" class="form-control new-imei-input" placeholder="Saisir ou scanner un IMEI (14 à 17 chiffres)" inputmode="numeric" autocomplete="off">
        <button type="button" class="btn btn-outline-danger remove-imei-row" tabindex="-1"><i class="bi bi-x-lg"></i></button>
      `;
      return wrap;
    }

    function bindImeiRow(row) {
      const input = row.querySelector('.new-imei-input');
      const removeBtn = row.querySelector('.remove-imei-row');

      removeBtn?.addEventListener('click', () => {
        if (newImeiInputsWrap.querySelectorAll('.new-imei-row').length > 1) {
          row.remove();
        } else {
          input.value = '';
        }
      });

      // Une douchette code-barres envoie le code puis "Entrée" : on ajoute
      // automatiquement une nouvelle ligne et on y place le focus, pour
      // scanner en rafale sans toucher la souris.
      input?.addEventListener('keydown', (e) => {
        if (e.key === 'Enter') {
          e.preventDefault();
          const rows = Array.from(newImeiInputsWrap.querySelectorAll('.new-imei-input'));
          const isLast = rows.indexOf(input) === rows.length - 1;
          if (isLast && input.value.trim() !== '') {
            addImeiRow();
          } else if (isLast) {
            saveImeiButton?.click();
          }
        }
      });
    }

    function addImeiRow(focus = true) {
      if (!newImeiInputsWrap) return;
      const row = newImeiRowTemplate();
      newImeiInputsWrap.appendChild(row);
      bindImeiRow(row);
      if (focus) row.querySelector('.new-imei-input')?.focus();
    }

    if (newImeiInputsWrap) {
      newImeiInputsWrap.querySelectorAll('.new-imei-row').forEach(bindImeiRow);
    }

    addImeiRowButton?.addEventListener('click', () => addImeiRow());

    saveImeiButton?.addEventListener('click', async function () {
      if (imeiErrorEl) imeiErrorEl.textContent = '';

      const inputs = Array.from(newImeiInputsWrap?.querySelectorAll('.new-imei-input') ?? []);
      const imeis = inputs.map(i => i.value.trim()).filter(v => v !== '');

      if (imeis.length === 0) {
        if (imeiErrorEl) imeiErrorEl.textContent = 'Veuillez saisir au moins un IMEI.';
        return;
      }

      saveImeiButton.disabled = true;
      const originalHtml = saveImeiButton.innerHTML;
      saveImeiButton.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Enregistrement...';

      try {
        const response = await fetch('{{ isset($product) && $product->exists ? route('products.imeis.store', $product) : '#' }}', {
          method: 'POST',
          headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
            'Accept': 'application/json',
            'Content-Type': 'application/json',
          },
          body: JSON.stringify({ imeis }),
        });

        const data = await response.json();

        if (!response.ok) {
          if (imeiErrorEl) imeiErrorEl.textContent = data.error || (data.errors ? Object.values(data.errors).flat().join(' ') : 'Erreur lors de l\'enregistrement.');
          return;
        }

        (data.imeis || []).forEach((imei) => {
          const tr = document.createElement('tr');
          tr.innerHTML = `
            <td class="font-monospace">${imei.imei}</td>
            <td><span class="badge bg-success">Disponible</span></td>
            <td>—</td>
            <td class="text-end text-muted small">—</td>
          `;
          imeiTableBody?.appendChild(tr);
        });

        if (imeiListWrap) imeiListWrap.style.display = '';
        if (imeiCountBadge) imeiCountBadge.textContent = imeiTableBody?.querySelectorAll('tr').length ?? 0;
        if (stockQuantityInput && data.stock_quantity !== undefined) {
          stockQuantityInput.value = data.stock_quantity;
        }

        newImeiInputsWrap.innerHTML = '';
        addImeiRow(false);

        if (window.UiToast) {
          window.UiToast.show(imeis.length + ' IMEI ajouté(s) avec succès.', 'success');
        }
      } catch (error) {
        if (imeiErrorEl) imeiErrorEl.textContent = 'Erreur réseau lors de l\'enregistrement.';
      } finally {
        saveImeiButton.disabled = false;
        saveImeiButton.innerHTML = originalHtml;
      }
    });

    if (imeiCountBadge && imeiTableBody) {
      imeiCountBadge.textContent = imeiTableBody.querySelectorAll('tr').length;
    }

    // Suppression d'un IMEI disponible (AJAX, sans <form> imbriqué dans le
    // formulaire principal du produit — cela casserait sa soumission).
    document.querySelectorAll('.delete-imei-btn').forEach((btn) => {
      btn.addEventListener('click', async function () {
        if (!confirm('Supprimer cet IMEI ?')) return;

        const row = btn.closest('tr');
        btn.disabled = true;

        try {
          const response = await fetch(btn.dataset.url, {
            method: 'DELETE',
            headers: {
              'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
              'Accept': 'application/json',
            },
          });

          const data = await response.json();

          if (!response.ok) {
            if (window.UiToast) window.UiToast.show(data.error || 'Erreur lors de la suppression.', 'error');
            btn.disabled = false;
            return;
          }

          row?.remove();
          if (imeiCountBadge && imeiTableBody) {
            imeiCountBadge.textContent = imeiTableBody.querySelectorAll('tr').length;
          }
          if (stockQuantityInput && data.stock_quantity !== undefined) {
            stockQuantityInput.value = data.stock_quantity;
          }
          if (window.UiToast) window.UiToast.show('IMEI supprimé.', 'success');
        } catch (error) {
          if (window.UiToast) window.UiToast.show('Erreur réseau lors de la suppression.', 'error');
          btn.disabled = false;
        }
      });
    });
  });
</script>
@endpush

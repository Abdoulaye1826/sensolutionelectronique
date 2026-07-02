<div class="row">
  <div class="col-md-6 mb-3">
    <label for="customer_search" class="form-label">Client</label>

    {{-- Champ hidden pour stocker l'ID du client sélectionné --}}
    <input type="hidden" id="customer_id" name="customer_id" value="{{ old('customer_id', $sale?->customer_id ?? '') }}">

    <div class="position-relative">
      <div class="input-group">
        <span class="input-group-text"><i class="bi bi-search"></i></span>
        <input type="text" class="form-control @error('customer_id') is-invalid @enderror"
               id="customer_search" autocomplete="off"
               placeholder="Tapez le nom, le téléphone ou l'email..."
               value="{{ old('customer_id', $sale?->customer_id ?? '') ? optional($sale?->customer ?? \App\Models\Customer::find(old('customer_id')))->full_name : '' }}">
        <button type="button" class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#newCustomerModal" title="Ajouter un client">
          <i class="bi bi-person-plus"></i>
        </button>
      </div>
      @error('customer_id')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror

      {{-- Liste d'autocomplétion --}}
      <div id="customerDropdown" class="list-group position-absolute w-100 shadow-sm" style="z-index: 1050; max-height: 250px; overflow-y: auto; display: none;"></div>
    </div>

    {{-- Client sélectionné --}}
    @php $selectedCustomer = old('customer_id', $sale?->customer_id ?? '') ? ($sale?->customer ?? \App\Models\Customer::find(old('customer_id'))) : null; @endphp
    <div id="customerSelected" class="alert alert-success d-flex align-items-center justify-content-between mt-2 py-2 px-3"
         style="display: {{ $selectedCustomer ? 'flex' : 'none' }} !important;">
      <span id="customerSelectedText">
        @if($selectedCustomer)
          <i class="bi bi-check-circle me-1"></i>
          <strong>{{ $selectedCustomer->full_name }}</strong>
          @if($selectedCustomer->phone) <span class="text-muted">({{ $selectedCustomer->phone }})</span> @endif
        @endif
      </span>
      <button type="button" class="btn btn-sm btn-outline-danger ms-2" id="customerClear">
        <i class="bi bi-x-lg"></i>
      </button>
    </div>

    {{-- Bouton ajouter un client (visible quand aucun résultat) --}}
    <div id="customerNotFound" class="mt-2" style="display: none;">
      <div class="alert alert-warning py-2 px-3 d-flex align-items-center justify-content-between mb-0">
        <small><i class="bi bi-exclamation-triangle me-1"></i>Aucun client trouvé pour cette recherche.</small>
        <button type="button" class="btn btn-sm btn-primary" id="openNewCustomerModalFromSearch">
          <i class="bi bi-plus-circle me-1"></i>Ajouter un client
        </button>
      </div>
    </div>

    <div class="form-text">Laissez vide pour un client anonyme.</div>
  </div>
  <div class="col-md-4 mb-3">
    <label for="sale_type" class="form-label">Type de transaction <span class="text-danger">*</span></label>
    <select id="sale_type" name="sale_type" class="form-select @error('sale_type') is-invalid @enderror" required>
      <option value="vente" @selected(old('sale_type', $sale?->sale_type->value ?? 'vente') === 'vente')>Vente</option>
      <option value="echange" @selected(old('sale_type', $sale?->sale_type->value ?? '') === 'echange')>Échange</option>
    </select>
    @error('sale_type')<div class="invalid-feedback">{{ $message }}</div>@enderror
  </div>
  <div class="col-md-6 mb-3">
    <label for="sale_date_display" class="form-label">Date de vente</label>
    <input type="text" readonly class="form-control" id="sale_date_display"
           value="{{ old('sale_date', $sale?->sale_date?->format('Y-m-d H:i:s') ?? now()->format('Y-m-d H:i:s')) }}">
    <div class="form-text">La date est générée automatiquement par le serveur.</div>
  </div>
</div>

<div class="row">
  <div class="col-12 mb-3">
    <label class="form-label">Produits</label>
    <div class="card p-3">
      <div class="mb-3">
        <div class="d-flex justify-content-between align-items-center mb-2">
          <div class="text-muted">Ajoutez les produits de la vente</div>
          <button type="button" class="btn btn-sm btn-outline-primary" id="addSaleItemButton">
            <i class="bi bi-plus-lg"></i> Ajouter un produit
          </button>
        </div>
        <div class="btn-group price-tier-group" role="group" aria-label="Tarif applicable" id="globalPriceTierGroup">
          <button type="button" class="btn btn-outline-secondary price-tier-btn active" data-tier="client">client</button>
          <button type="button" class="btn btn-outline-secondary price-tier-btn" data-tier="fournisseur">Revendeur</button>
        </div>
      </div>
      <div id="saleItemsContainer">
        @php
          $oldProductIds = old('product_id', $sale?->items->pluck('product_id')->toArray() ?? []);
          $oldQuantities = old('quantity', $sale?->items->pluck('quantity')->toArray() ?? []);
          $oldUnitPrices = old('unit_price', $sale?->items->pluck('unit_price')->toArray() ?? []);
          $oldImeis = old('imei', $sale?->items->map(fn ($i) => $i->productImei?->imei)->toArray() ?? []);

          $saleItems = collect(is_array($oldProductIds) ? $oldProductIds : [$oldProductIds])
              ->map(function ($productId, $index) use ($oldQuantities, $oldUnitPrices, $oldImeis) {
                  return [
                      'product_id' => $productId,
                      'quantity' => is_array($oldQuantities) ? ($oldQuantities[$index] ?? 1) : 1,
                      'unit_price' => is_array($oldUnitPrices) ? ($oldUnitPrices[$index] ?? 0) : ($oldUnitPrices ?? 0),
                      'imei' => is_array($oldImeis) ? ($oldImeis[$index] ?? '') : '',
                  ];
              });

          if ($saleItems->isEmpty()) {
              $saleItems = collect([['product_id' => '', 'quantity' => 1, 'unit_price' => 0, 'imei' => '']]);
          }
        @endphp

        @foreach($saleItems as $index => $saleItem)
          <div class="sale-item-row row g-3 align-items-end mb-2" data-price-tier="client">
            <div class="col-md-4">
              <label class="form-label">Produit</label>
              <select name="product_id[]" class="form-select @error('product_id.' . $index) is-invalid @enderror" required>
                <option value="">— Sélectionnez un produit —</option>
                @foreach($products as $product)
                  <option value="{{ $product->id }}" @selected((int) $saleItem['product_id'] === $product->id)>
                    {{ $product->reference }} — {{ $product->name }} @if($product->stock_quantity !== null)({{ $product->stock_quantity }} en stock)@endif
                  </option>
                @endforeach
              </select>
              @error('product_id.' . $index)<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-md-2">
              <label class="form-label">Prix unitaire</label>
              <input type="number" step="0.01" min="0" name="unit_price[]" class="form-control price-input @error('unit_price.' . $index) is-invalid @enderror"
                     value="{{ old('unit_price.' . $index, $saleItem['unit_price'] ?? 0) }}" required>
              @error('unit_price.' . $index)<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-md-2">
              <label class="form-label">Quantité</label>
              <input type="number" step="1" min="1" name="quantity[]" class="form-control @error('quantity.' . $index) is-invalid @enderror"
                     value="{{ $saleItem['quantity'] }}" required>
              @error('quantity.' . $index)<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-md-2">
              <label class="form-label">Total</label>
              <input type="text" class="form-control line-total" value="0" readonly>
            </div>
            <div class="col-md-1 text-end">
              <button type="button" class="btn btn-outline-danger btn-remove-item" style="margin-top: 32px;">
                <i class="bi bi-trash"></i>
              </button>
            </div>
            <div class="col-12 imei-row-field" style="display:none;">
              <label class="form-label">IMEI <span class="text-danger">*</span></label>
              <input type="text" name="imei[]" class="form-control imei-input @error('imei.' . $index) is-invalid @enderror"
                     list="imei-options-{{ $index }}" value="{{ old('imei.' . $index, $saleItem['imei'] ?? '') }}"
                     placeholder="Scanner ou saisir l'IMEI" autocomplete="off">
              <datalist class="imei-datalist" id="imei-options-{{ $index }}"></datalist>
              @error('imei.' . $index)<div class="invalid-feedback">{{ $message }}</div>@enderror
              <div class="form-text imei-count-text"></div>
            </div>
          </div>
        @endforeach
      </div>
    </div>
  </div>

  <template id="saleItemTemplate">
    <div class="sale-item-row row g-3 align-items-end mb-2" data-price-tier="client">
      <div class="col-md-4">
        <label class="form-label">Produit</label>
        <select name="product_id[]" class="form-select" required>
          <option value="">— Sélectionnez un produit —</option>
          @foreach($products as $product)
            <option value="{{ $product->id }}">
              {{ $product->reference }} — {{ $product->name }} @if($product->stock_quantity !== null)({{ $product->stock_quantity }} en stock)@endif
            </option>
          @endforeach
        </select>
      </div>
      <div class="col-md-2">
        <label class="form-label">Prix unitaire</label>
        <input type="number" step="0.01" min="0" name="unit_price[]" class="form-control price-input" value="0" required>
      </div>
      <div class="col-md-2">
        <label class="form-label">Quantité</label>
        <input type="number" step="1" min="1" name="quantity[]" class="form-control" value="1" required>
      </div>
      <div class="col-md-2">
        <label class="form-label">Total</label>
        <input type="text" class="form-control line-total" value="0" readonly>
      </div>
      <div class="col-md-1 text-end">
        <button type="button" class="btn btn-outline-danger btn-remove-item" style="margin-top: 32px;">
          <i class="bi bi-trash"></i>
        </button>
      </div>
      <div class="col-12 imei-row-field" style="display:none;">
        <label class="form-label">IMEI <span class="text-danger">*</span></label>
        <input type="text" name="imei[]" class="form-control imei-input" list="" placeholder="Scanner ou saisir l'IMEI" autocomplete="off">
        <datalist class="imei-datalist"></datalist>
        <div class="form-text imei-count-text"></div>
      </div>
    </div>
  </template>

</div>

<div id="exchangeFields" class="border rounded p-3 mb-3" style="display: {{ old('sale_type', $sale?->sale_type->value ?? 'vente') === 'echange' ? 'block' : 'none' }};">
  <h5 class="mb-3"><i class="bi bi-arrow-left-right me-2"></i>Produit apport&eacute; par le client</h5>

  {{-- Champ hidden pour stocker l'ID du produit s&eacute;lectionn&eacute; --}}
  <input type="hidden" id="exchange_product_id" name="exchange_product_id"
         value="{{ old('exchange_product_id', $sale?->exchange_details['product_id'] ?? '') }}">

  <div class="row">
    <div class="col-md-6 mb-3">
      <label for="exchange_product_search" class="form-label">Produit apport&eacute; <span class="text-danger">*</span></label>
      <div class="position-relative">
        <div class="input-group">
          <span class="input-group-text"><i class="bi bi-search"></i></span>
          <input type="text" class="form-control @error('exchange_product_id') is-invalid @enderror"
                 id="exchange_product_search" autocomplete="off"
                 placeholder="Tapez le nom, la r&eacute;f&eacute;rence ou la marque..."
                 value="@if(old('exchange_product_id', $sale?->exchange_details['product_id'] ?? '')){{ optional(\App\Models\Product::find(old('exchange_product_id', $sale?->exchange_details['product_id'] ?? '')))->reference }} — {{ optional(\App\Models\Product::find(old('exchange_product_id', $sale?->exchange_details['product_id'] ?? '')))->name }}@endif">
        </div>
        @error('exchange_product_id')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror

        {{-- Liste d'autocompl&eacute;tion --}}
        <div id="exchangeProductDropdown" class="list-group position-absolute w-100 shadow-sm" style="z-index: 1050; max-height: 250px; overflow-y: auto; display: none;"></div>
      </div>

      {{-- Produit s&eacute;lectionn&eacute; --}}
      <div id="exchangeProductSelected" class="alert alert-success d-flex align-items-center justify-content-between mt-2 py-2 px-3"
           style="display: {{ old('exchange_product_id', $sale?->exchange_details['product_id'] ?? '') ? 'flex' : 'none' }} !important;">
        <span id="exchangeProductSelectedText">
          @if(old('exchange_product_id', $sale?->exchange_details['product_id'] ?? ''))
            @php $selectedProduct = \App\Models\Product::find(old('exchange_product_id', $sale?->exchange_details['product_id'] ?? '')); @endphp
            @if($selectedProduct)
              <i class="bi bi-check-circle me-1"></i>
              <strong>{{ $selectedProduct->reference }}</strong> — {{ $selectedProduct->name }}
              @if($selectedProduct->brand) <span class="text-muted">({{ $selectedProduct->brand }})</span> @endif
            @endif
          @endif
        </span>
        <button type="button" class="btn btn-sm btn-outline-danger ms-2" id="exchangeProductClear">
          <i class="bi bi-x-lg"></i>
        </button>
      </div>

      {{-- Bouton ajouter un produit (visible quand aucun r&eacute;sultat) --}}
      <div id="exchangeProductNotFound" class="mt-2" style="display: none;">
        <div class="alert alert-warning py-2 px-3 d-flex align-items-center justify-content-between mb-0">
          <small><i class="bi bi-exclamation-triangle me-1"></i>Aucun produit trouv&eacute; pour cette recherche.</small>
          <button type="button" class="btn btn-sm btn-primary" id="openNewExchangeProductModal">
            <i class="bi bi-plus-circle me-1"></i>Ajouter un produit
          </button>
        </div>
      </div>
    </div>

    <div class="col-md-2 mb-3" id="exchangeQuantityField">
      <label for="exchange_quantity" class="form-label">Quantit&eacute; apport&eacute;e</label>
      <input type="number" step="1" min="1" class="form-control @error('exchange_quantity') is-invalid @enderror"
             id="exchange_quantity" name="exchange_quantity" value="{{ old('exchange_quantity', $sale?->exchange_details['quantity'] ?? 1) }}">
      @error('exchange_quantity')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
  </div>

  <div class="row">
    <div class="col-md-6 mb-3">
      <label for="exchange_imei" class="form-label">IMEI du produit apporté</label>
      <input type="text" class="form-control @error('exchange_imei') is-invalid @enderror"
             id="exchange_imei" name="exchange_imei" value="{{ old('exchange_imei', $sale?->exchange_details['imei'] ?? '') }}"
             placeholder="Scanner ou saisir l'IMEI (optionnel)" autocomplete="off">
      @error('exchange_imei')<div class="invalid-feedback">{{ $message }}</div>@enderror
      <div class="form-text">Obligatoire uniquement pour les téléphones. Sera enregistré avec le produit après validation.</div>
    </div>
  </div>

  <div class="row">
    <div class="col-md-4 mb-3">
      <label for="exchange_added_amount" class="form-label">Montant ajout&eacute; par le client (FCFA)</label>
      <input type="number" step="0.01" min="0" class="form-control @error('exchange_added_amount') is-invalid @enderror"
             id="exchange_added_amount" name="exchange_added_amount" value="{{ old('exchange_added_amount', $sale?->exchange_details['added_amount'] ?? 0) }}">
      @error('exchange_added_amount')<div class="invalid-feedback">{{ $message }}</div>@enderror
      <div class="form-text">Montant saisi manuellement, sans calcul automatique.</div>
    </div>
  </div>
</div>

@php
  $existingPaymentsCount = $sale?->invoice?->payments?->count() ?? 0;
  $currentPaymentMethod = old('payment_method', $sale?->invoice?->payments?->first()?->method?->value ?? '');
  $currentAmountGiven = old('amount_given', $existingPaymentsCount > 0 ? $sale?->invoice?->amount_paid : null);
@endphp
<div class="row" id="totalColumn" style="display: {{ old('sale_type', $sale?->sale_type->value ?? 'vente') === 'echange' ? 'none' : 'flex' }};">
  <div class="col-md-4 mb-3">
    <label for="total_ttc" class="form-label">Total</label>
    <input type="number" step="0.01" min="0" class="form-control @error('total_ttc') is-invalid @enderror"
           id="total_ttc" name="total_ttc" value="{{ old('total_ttc', $sale?->total_ttc ?? 0) }}" readonly>
    @error('total_ttc')<div class="invalid-feedback">{{ $message }}</div>@enderror
    <div class="form-text">Le total est calculé automatiquement à partir des produits.</div>
  </div>
  <div class="col-md-4 mb-3">
    <label for="amount_given" class="form-label">Montant donné par le client (FCFA)</label>
    <input type="number" step="0.01" min="0" class="form-control @error('amount_given') is-invalid @enderror"
           id="amount_given" name="amount_given" value="{{ $currentAmountGiven }}"
           @if($existingPaymentsCount > 0) readonly @endif>
    @error('amount_given')<div class="invalid-feedback">{{ $message }}</div>@enderror
    @if($existingPaymentsCount > 0)
      <div class="form-text">Paiement déjà enregistré. Pour un complément, utilisez la fiche facture.</div>
    @else
      <div class="form-text">Laissez au montant total pour un paiement intégral, ou réduisez-le pour un paiement partiel.</div>
    @endif
  </div>
  <div class="col-md-4 mb-3">
    <label for="remaining_amount_display" class="form-label">Reste à payer</label>
    <input type="text" class="form-control fw-bold" id="remaining_amount_display" value="0" readonly>
  </div>
</div>
<div class="row">
  <div class="col-md-4 mb-3">
    <label for="payment_method" class="form-label">Mode de paiement</label>
    <select id="payment_method" name="payment_method" class="form-select @error('payment_method') is-invalid @enderror">
      <option value="">— Non renseigné —</option>
      <option value="wave" @selected($currentPaymentMethod === 'wave')>Wave</option>
      <option value="orange_money" @selected($currentPaymentMethod === 'orange_money')>Orange Money</option>
      <option value="cash" @selected($currentPaymentMethod === 'cash')>Espèces</option>
    </select>
    @error('payment_method')<div class="invalid-feedback">{{ $message }}</div>@enderror
  </div>
  @php $currentWarrantyDuration = old('warranty_duration', $sale?->warranty_duration?->value ?? '30d'); @endphp
  <div class="col-md-4 mb-3">
    <label for="warranty_duration" class="form-label">Garantie <span class="text-danger">*</span></label>
    <select id="warranty_duration" name="warranty_duration" class="form-select @error('warranty_duration') is-invalid @enderror" required>
      <option value="none" @selected($currentWarrantyDuration === 'none')>Aucune garantie</option>
      <option value="30d" @selected($currentWarrantyDuration === '30d')>30 jours</option>
      <option value="3m" @selected($currentWarrantyDuration === '3m')>3 mois</option>
      <option value="6m" @selected($currentWarrantyDuration === '6m')>6 mois</option>
      <option value="1y" @selected($currentWarrantyDuration === '1y')>1 an</option>
    </select>
    @error('warranty_duration')<div class="invalid-feedback">{{ $message }}</div>@enderror
  </div>
  <div class="col-md-4 mb-3">
    <label for="status" class="form-label">Statut <span class="text-danger">*</span></label>
    <select id="status" name="status" class="form-select @error('status') is-invalid @enderror" required>
      <option value="draft" @selected(old('status', $sale?->status->value ?? 'draft') === 'draft')>Brouillon</option>
      <option value="validated" @selected(old('status', $sale?->status->value ?? '') === 'validated')>Validée</option>
      <option value="cancelled" @selected(old('status', $sale?->status->value ?? '') === 'cancelled')>Annulée</option>
    </select>
    @error('status')<div class="invalid-feedback">{{ $message }}</div>@enderror
  </div>
</div>

<div class="mb-3">
  <label for="notes" class="form-label">Observations</label>
  <textarea class="form-control @error('notes') is-invalid @enderror"
            id="notes" name="notes" rows="3">{{ old('notes', $sale->notes ?? '') }}</textarea>
  @error('notes')<div class="invalid-feedback">{{ $message }}</div>@enderror
</div>

@push('styles')
<style>
  #exchangeProductDropdown .list-group-item {
    cursor: pointer;
    transition: background-color 0.15s;
  }
  #exchangeProductDropdown .list-group-item:hover,
  #exchangeProductDropdown .list-group-item.active {
    background-color: #0d6efd;
    color: #fff;
  }
  #exchangeProductDropdown .list-group-item.active .text-muted {
    color: rgba(255,255,255,0.75) !important;
  }
</style>
@endpush

@push('scripts')
<script>
  document.addEventListener('DOMContentLoaded', function () {
    const saleTypeField = document.getElementById('sale_type');
    const exchangeFields = document.getElementById('exchangeFields');
    const addSaleItemButton = document.getElementById('addSaleItemButton');
    const saleItemsContainer = document.getElementById('saleItemsContainer');
    const saleItemTemplate = document.getElementById('saleItemTemplate');
    const productClientPrices = {
      @foreach($products as $product)
        {{ $product->id }}: {{ $product->sale_price }},
      @endforeach
    };
    const productSupplierPrices = {
      @foreach($products as $product)
        {{ $product->id }}: {{ $product->supplier_sale_price ?? $product->sale_price }},
      @endforeach
    };
    const productTracksImei = {
      @foreach($products as $product)
        {{ $product->id }}: {{ $product->tracks_imei ? 'true' : 'false' }},
      @endforeach
    };
    let imeiRowCounter = 1000;

    const totalColumn = document.getElementById('totalColumn');
    const amountGivenField = document.getElementById('amount_given');
    const remainingDisplay = document.getElementById('remaining_amount_display');
    let amountGivenTouched = amountGivenField ? amountGivenField.value !== '' : false;

    if (saleTypeField && exchangeFields) {
      saleTypeField.addEventListener('change', function () {
        const isEchange = this.value === 'echange';
        exchangeFields.style.display = isEchange ? 'block' : 'none';
        if (totalColumn) {
          totalColumn.style.display = isEchange ? 'none' : 'flex';
        }
        calculateTotals();
      });
    }

    function updateRemaining() {
      if (!remainingDisplay) return;
      const total = parseFloat(document.getElementById('total_ttc')?.value || 0) || 0;
      const given = parseFloat(amountGivenField?.value || 0) || 0;
      const remaining = Math.max(0, total - given);
      remainingDisplay.value = remaining.toLocaleString('fr-FR', { minimumFractionDigits: 0, maximumFractionDigits: 2 }) + ' FCFA';
      remainingDisplay.classList.toggle('text-danger', remaining > 0);
      remainingDisplay.classList.toggle('text-success', remaining <= 0);
    }

    if (amountGivenField) {
      amountGivenField.addEventListener('input', function () {
        amountGivenTouched = true;
        updateRemaining();
      });
    }

    function calculateTotals() {
      const rows = saleItemsContainer.querySelectorAll('.sale-item-row');
      let total = 0;

      rows.forEach(row => {
        const quantityInput = row.querySelector('input[name="quantity[]"]');
        const unitPriceInput = row.querySelector('input[name="unit_price[]"]');
        const lineTotalInput = row.querySelector('.line-total');

        const quantity = parseFloat(quantityInput?.value || 0) || 0;
        const unitPrice = parseFloat(unitPriceInput?.value || 0) || 0;
        const lineTotal = quantity * unitPrice;

        if (lineTotalInput) {
          lineTotalInput.value = lineTotal.toFixed(2).replace('.', ',');
        }

        total += lineTotal;
      });

      // Aucun calcul automatique pour les échanges : le total est géré côté serveur
      // à partir du montant ajouté par le client, saisi manuellement.
      if (saleTypeField && saleTypeField.value === 'echange') {
        return;
      }

      const totalField = document.getElementById('total_ttc');
      const roundedTotal = Math.max(0, total);

      if (totalField) {
        totalField.value = roundedTotal.toFixed(2);
      }

      // Par défaut, le montant donné suit le total (paiement intégral) tant
      // que l'utilisateur ne l'a pas modifié manuellement pour un paiement
      // partiel.
      if (amountGivenField && !amountGivenTouched) {
        amountGivenField.value = roundedTotal.toFixed(2);
      }

      updateRemaining();
    }

    let globalPriceTier = 'client';

    function applyPriceForTier(row) {
      const select = row.querySelector('select[name="product_id[]"]');
      const unitPriceInput = row.querySelector('.price-input');
      if (!select || !unitPriceInput) return;

      const productId = select.value;
      const prices = globalPriceTier === 'fournisseur' ? productSupplierPrices : productClientPrices;

      unitPriceInput.value = prices[productId] !== undefined ? Number(prices[productId]).toFixed(2) : 0;
      row.dataset.priceTier = globalPriceTier;
    }

    const globalPriceTierGroup = document.getElementById('globalPriceTierGroup');
    if (globalPriceTierGroup) {
      globalPriceTierGroup.querySelectorAll('.price-tier-btn').forEach(button => {
        button.addEventListener('click', function () {
          globalPriceTier = this.dataset.tier;
          globalPriceTierGroup.querySelectorAll('.price-tier-btn').forEach(btn => {
            btn.classList.toggle('active', btn === this);
          });

          saleItemsContainer.querySelectorAll('.sale-item-row').forEach(row => {
            applyPriceForTier(row);
          });
          calculateTotals();
        });
      });
    }

    /**
     * Affiche/masque le champ IMEI d'une ligne selon que le produit
     * sélectionné est suivi par IMEI, verrouille la quantité à 1 dans ce
     * cas, et alimente la liste des IMEI disponibles (saisie ou scan).
     */
    async function syncImeiFieldForRow(row, { keepImei = false } = {}) {
      const select = row.querySelector('select[name="product_id[]"]');
      const quantityInput = row.querySelector('input[name="quantity[]"]');
      const imeiField = row.querySelector('.imei-row-field');
      const imeiInput = row.querySelector('.imei-input');
      const imeiDatalist = row.querySelector('.imei-datalist');
      const countText = row.querySelector('.imei-count-text');
      if (!select || !imeiField || !imeiInput) return;

      const productId = select.value;
      const tracksImei = productId && productTracksImei[productId];

      if (!tracksImei) {
        imeiField.style.display = 'none';
        if (!keepImei) imeiInput.value = '';
        if (quantityInput) {
          quantityInput.readOnly = false;
        }
        return;
      }

      imeiField.style.display = '';
      if (quantityInput) {
        quantityInput.value = 1;
        quantityInput.readOnly = true;
      }
      if (!keepImei) imeiInput.value = '';

      if (countText) countText.textContent = 'Chargement des IMEI disponibles...';

      try {
        const response = await fetch(`/products/${productId}/available-imeis`, {
          headers: { Accept: 'application/json' },
        });
        const imeis = await response.json();

        if (imeiDatalist) {
          imeiDatalist.innerHTML = imeis.map(v => `<option value="${v}"></option>`).join('');
        }
        if (countText) {
          countText.textContent = imeis.length > 0
            ? imeis.length + ' IMEI disponible(s) — scannez ou choisissez dans la liste.'
            : 'Aucun IMEI disponible pour ce produit.';
        }
      } catch (error) {
        if (countText) countText.textContent = "Impossible de charger les IMEI disponibles.";
      }
    }

    function bindSaleItemEvents(container) {
      const rows = container.classList?.contains('sale-item-row')
        ? [container]
        : Array.from(container.querySelectorAll('.sale-item-row'));

      rows.forEach(row => {
        const imeiInput = row.querySelector('.imei-input');
        const imeiDatalist = row.querySelector('.imei-datalist');
        if (imeiInput && imeiDatalist && !imeiDatalist.id) {
          imeiRowCounter += 1;
          imeiDatalist.id = 'imei-options-' + imeiRowCounter;
          imeiInput.setAttribute('list', imeiDatalist.id);
        }
      });

      container.querySelectorAll('select[name="product_id[]"]').forEach(select => {
        select.addEventListener('change', function () {
          const row = this.closest('.sale-item-row');
          applyPriceForTier(row);
          syncImeiFieldForRow(row);
          calculateTotals();
        });
      });

      container.querySelectorAll('input[name="quantity[]"], input[name="unit_price[]"]').forEach(input => {
        input.addEventListener('input', calculateTotals);
      });
      container.querySelectorAll('.btn-remove-item').forEach(button => {
        button.addEventListener('click', function () {
          const row = this.closest('.sale-item-row');
          if (row) {
            row.remove();
            calculateTotals();
          }
        });
      });
    }

    if (addSaleItemButton && saleItemTemplate) {
      addSaleItemButton.addEventListener('click', function () {
        const clone = saleItemTemplate.content.cloneNode(true);
        saleItemsContainer.appendChild(clone);
        bindSaleItemEvents(saleItemsContainer.lastElementChild);
        calculateTotals();
      });
    }

    bindSaleItemEvents(saleItemsContainer);
    saleItemsContainer.querySelectorAll('.sale-item-row').forEach(row => {
      syncImeiFieldForRow(row, { keepImei: true });
    });
    calculateTotals();

    // ───────────────────────────────────────────────────────────────
    // Autocomplétion client (nom, téléphone, email)
    // ───────────────────────────────────────────────────────────────
    const customerSearchInput = document.getElementById('customer_search');
    const customerIdField = document.getElementById('customer_id');
    const customerDropdown = document.getElementById('customerDropdown');
    const customerSelected = document.getElementById('customerSelected');
    const customerSelectedText = document.getElementById('customerSelectedText');
    const customerNotFound = document.getElementById('customerNotFound');
    const customerClear = document.getElementById('customerClear');
    const openNewCustomerModalFromSearch = document.getElementById('openNewCustomerModalFromSearch');

    let customerSearchTimeout = null;
    let customerActiveIndex = -1;

    if (customerSearchInput) {
      customerSearchInput.addEventListener('input', function () {
        const query = this.value.trim();
        customerActiveIndex = -1;
        customerIdField.value = '';

        if (query.length < 2) {
          customerDropdown.style.display = 'none';
          customerNotFound.style.display = 'none';
          return;
        }

        clearTimeout(customerSearchTimeout);
        customerSearchTimeout = setTimeout(() => fetchCustomers(query), 300);
      });

      customerSearchInput.addEventListener('keydown', function (e) {
        const items = customerDropdown.querySelectorAll('.list-group-item');
        if (!items.length) return;

        if (e.key === 'ArrowDown') {
          e.preventDefault();
          customerActiveIndex = Math.min(customerActiveIndex + 1, items.length - 1);
          updateActiveCustomerItem(items);
        } else if (e.key === 'ArrowUp') {
          e.preventDefault();
          customerActiveIndex = Math.max(customerActiveIndex - 1, 0);
          updateActiveCustomerItem(items);
        } else if (e.key === 'Enter') {
          e.preventDefault();
          if (customerActiveIndex >= 0 && items[customerActiveIndex]) {
            items[customerActiveIndex].click();
          }
        } else if (e.key === 'Escape') {
          customerDropdown.style.display = 'none';
        }
      });

      document.addEventListener('click', function (e) {
        if (!customerSearchInput.contains(e.target) && !customerDropdown.contains(e.target)) {
          customerDropdown.style.display = 'none';
        }
      });
    }

    function updateActiveCustomerItem(items) {
      items.forEach((item, idx) => item.classList.toggle('active', idx === customerActiveIndex));
      if (items[customerActiveIndex]) {
        items[customerActiveIndex].scrollIntoView({ block: 'nearest' });
      }
    }

    async function fetchCustomers(query) {
      try {
        const response = await fetch(`{{ route('sales.customers.search') }}?q=${encodeURIComponent(query)}`, {
          headers: {
            'Accept': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
          }
        });

        const customers = await response.json();
        customerDropdown.innerHTML = '';

        if (customers.length === 0) {
          customerDropdown.style.display = 'none';
          customerNotFound.style.display = 'block';
          return;
        }

        customerNotFound.style.display = 'none';

        customers.forEach((customer) => {
          const item = document.createElement('button');
          item.type = 'button';
          item.className = 'list-group-item list-group-item-action py-2 px-3';
          item.innerHTML = `
            <div>
              <strong>${customer.full_name}</strong>
              ${customer.phone ? '<span class="text-muted">— ' + customer.phone + '</span>' : ''}
              ${customer.email ? '<br><small class="text-muted">' + customer.email + '</small>' : ''}
            </div>
          `;
          item.addEventListener('click', () => selectCustomer(customer));
          customerDropdown.appendChild(item);
        });

        customerDropdown.style.display = 'block';
      } catch (error) {
        console.error('Erreur lors de la recherche de clients :', error);
      }
    }

    function selectCustomer(customer) {
      customerIdField.value = customer.id;
      customerSearchInput.value = customer.full_name;
      customerDropdown.style.display = 'none';
      customerNotFound.style.display = 'none';

      customerSelectedText.innerHTML = `
        <i class="bi bi-check-circle me-1"></i>
        <strong>${customer.full_name}</strong>
        ${customer.phone ? '<span class="text-muted">(' + customer.phone + ')</span>' : ''}
      `;
      customerSelected.style.display = 'flex';
    }

    if (customerClear) {
      customerClear.addEventListener('click', function () {
        customerIdField.value = '';
        customerSearchInput.value = '';
        customerSelected.style.display = 'none';
        customerSelectedText.innerHTML = '';
        customerSearchInput.focus();
      });
    }

    // ───────────────────────────────────────────────────────────────
    // Modale création client
    // ───────────────────────────────────────────────────────────────
    const saveCustomerButton = document.getElementById('saveNewCustomerButton');
    const newCustomerForm = document.getElementById('newCustomerForm');

    if (openNewCustomerModalFromSearch) {
      openNewCustomerModalFromSearch.addEventListener('click', function () {
        const nameField = document.getElementById('new_customer_full_name');
        if (nameField && customerSearchInput.value.trim()) {
          nameField.value = customerSearchInput.value.trim();
        }
        const modalEl = document.getElementById('newCustomerModal');
        new bootstrap.Modal(modalEl).show();
      });
    }

    if (saveCustomerButton && newCustomerForm) {
      saveCustomerButton.addEventListener('click', async function () {
        const formData = new FormData(newCustomerForm);

        document.querySelectorAll('#newCustomerForm .invalid-feedback').forEach(el => {
          el.textContent = '';
        });
        document.querySelectorAll('#newCustomerForm .is-invalid').forEach(el => {
          el.classList.remove('is-invalid');
        });

        const response = await fetch('{{ route('customers.store') }}', {
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
              const input = document.getElementById(`new_customer_${field}`);
              const feedback = document.getElementById(`new_customer_${field}_error`);
              if (input) {
                input.classList.add('is-invalid');
              }
              if (feedback) {
                feedback.textContent = messages.join(' ');
              }
            });
          }
          return;
        }

        selectCustomer(data);

        const modal = bootstrap.Modal.getInstance(document.getElementById('newCustomerModal'));
        modal.hide();
        newCustomerForm.reset();
      });
    }

    // ───────────────────────────────────────────────────────────────
    // Autocomplétion produit retourné (échange)
    // ───────────────────────────────────────────────────────────────
    const exchangeSearchInput = document.getElementById('exchange_product_search');
    const exchangeProductIdField = document.getElementById('exchange_product_id');
    const exchangeDropdown = document.getElementById('exchangeProductDropdown');
    const exchangeProductSelected = document.getElementById('exchangeProductSelected');
    const exchangeProductSelectedText = document.getElementById('exchangeProductSelectedText');
    const exchangeProductNotFound = document.getElementById('exchangeProductNotFound');
    const exchangeProductClear = document.getElementById('exchangeProductClear');
    const openNewExchangeProductModal = document.getElementById('openNewExchangeProductModal');

    @php
      $currentExchangeProductId = old('exchange_product_id', $sale?->exchange_details['product_id'] ?? '');
      $currentExchangeProductTracksImei = $currentExchangeProductId
          ? (bool) (\App\Models\Product::find($currentExchangeProductId)?->tracks_imei)
          : false;
    @endphp
    syncExchangeImeiField({{ $currentExchangeProductTracksImei ? 'true' : 'false' }});

    let exchangeSearchTimeout = null;
    let exchangeActiveIndex = -1;
    let exchangeLastResults = [];

    if (exchangeSearchInput) {
      // Recherche avec délai (debounce)
      exchangeSearchInput.addEventListener('input', function () {
        const query = this.value.trim();
        exchangeActiveIndex = -1;

        if (query.length < 2) {
          exchangeDropdown.style.display = 'none';
          exchangeProductNotFound.style.display = 'none';
          return;
        }

        clearTimeout(exchangeSearchTimeout);
        exchangeSearchTimeout = setTimeout(() => fetchExchangeProducts(query), 300);
      });

      // Navigation au clavier
      exchangeSearchInput.addEventListener('keydown', function (e) {
        const items = exchangeDropdown.querySelectorAll('.list-group-item');
        if (!items.length) return;

        if (e.key === 'ArrowDown') {
          e.preventDefault();
          exchangeActiveIndex = Math.min(exchangeActiveIndex + 1, items.length - 1);
          updateActiveItem(items);
        } else if (e.key === 'ArrowUp') {
          e.preventDefault();
          exchangeActiveIndex = Math.max(exchangeActiveIndex - 1, 0);
          updateActiveItem(items);
        } else if (e.key === 'Enter') {
          e.preventDefault();
          if (exchangeActiveIndex >= 0 && items[exchangeActiveIndex]) {
            items[exchangeActiveIndex].click();
          }
        } else if (e.key === 'Escape') {
          exchangeDropdown.style.display = 'none';
        }
      });

      // Fermer le dropdown au clic extérieur
      document.addEventListener('click', function (e) {
        if (!exchangeSearchInput.contains(e.target) && !exchangeDropdown.contains(e.target)) {
          exchangeDropdown.style.display = 'none';
        }
      });
    }

    function updateActiveItem(items) {
      items.forEach((item, idx) => {
        item.classList.toggle('active', idx === exchangeActiveIndex);
      });
      if (items[exchangeActiveIndex]) {
        items[exchangeActiveIndex].scrollIntoView({ block: 'nearest' });
      }
    }

    async function fetchExchangeProducts(query) {
      try {
        const response = await fetch(`{{ route('sales.exchange-products.search') }}?q=${encodeURIComponent(query)}`, {
          headers: {
            'Accept': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
          }
        });

        const products = await response.json();
        exchangeLastResults = products;
        exchangeDropdown.innerHTML = '';

        if (products.length === 0) {
          exchangeDropdown.style.display = 'none';
          exchangeProductNotFound.style.display = 'block';
          return;
        }

        exchangeProductNotFound.style.display = 'none';

        products.forEach((product, index) => {
          const item = document.createElement('button');
          item.type = 'button';
          item.className = 'list-group-item list-group-item-action py-2 px-3';
          item.innerHTML = `
            <div class="d-flex justify-content-between align-items-center">
              <div>
                <strong>${product.reference}</strong> &mdash; ${product.name}
                ${product.brand ? '<span class="text-muted">(' + product.brand + ')</span>' : ''}
              </div>
              <span class="badge bg-secondary">${Number(product.sale_price).toLocaleString('fr-FR')} FCFA</span>
            </div>
          `;
          item.addEventListener('click', () => selectExchangeProduct(product));
          exchangeDropdown.appendChild(item);
        });

        exchangeDropdown.style.display = 'block';
      } catch (error) {
        console.error('Erreur lors de la recherche de produits :', error);
      }
    }

    function selectExchangeProduct(product) {
      exchangeProductIdField.value = product.id;
      exchangeSearchInput.value = product.reference + ' \u2014 ' + product.name;
      exchangeDropdown.style.display = 'none';
      exchangeProductNotFound.style.display = 'none';

      // Afficher le produit s\u00e9lectionn\u00e9
      exchangeProductSelectedText.innerHTML = `
        <i class="bi bi-check-circle me-1"></i>
        <strong>${product.reference}</strong> \u2014 ${product.name}
        ${product.brand ? '<span class="text-muted">(' + product.brand + ')</span>' : ''}
      `;
      exchangeProductSelected.style.display = 'flex';

      syncExchangeImeiField(!!product.tracks_imei);
    }

    /**
     * Produit apport\u00e9 suivi par IMEI : on demande son IMEI (saisie/scan)
     * et on verrouille la quantit\u00e9 \u00e0 1 (un t\u00e9l\u00e9phone = une unit\u00e9).
     */
    function syncExchangeImeiField(tracksImei) {
      const quantityField = document.getElementById('exchange_quantity');

      if (quantityField) {
        if (tracksImei) {
          quantityField.value = 1;
          quantityField.readOnly = true;
        } else {
          quantityField.readOnly = false;
        }
      }
    }

    // Effacer la s\u00e9lection
    if (exchangeProductClear) {
      exchangeProductClear.addEventListener('click', function () {
        exchangeProductIdField.value = '';
        exchangeSearchInput.value = '';
        exchangeProductSelected.style.display = 'none';
        exchangeProductSelectedText.innerHTML = '';
        exchangeSearchInput.focus();
      });
    }

    // ───────────────────────────────────────────────────────────────
    // Modale cr\u00e9ation produit d'\u00e9change
    // ───────────────────────────────────────────────────────────────
    if (openNewExchangeProductModal) {
      openNewExchangeProductModal.addEventListener('click', function () {
        // Pr\u00e9-remplir le nom avec la recherche en cours
        const nameField = document.getElementById('new_exchange_product_name');
        if (nameField && exchangeSearchInput.value.trim()) {
          nameField.value = exchangeSearchInput.value.trim();
        }
        const modalEl = document.getElementById('newExchangeProductModal');
        const modal = new bootstrap.Modal(modalEl);
        modal.show();
      });
    }

    const saveExchangeProductBtn = document.getElementById('saveNewExchangeProductButton');
    const newExchangeProductForm = document.getElementById('newExchangeProductForm');

    if (saveExchangeProductBtn && newExchangeProductForm) {
      saveExchangeProductBtn.addEventListener('click', async function () {
        const formData = new FormData(newExchangeProductForm);

        // R\u00e9initialiser les erreurs
        document.querySelectorAll('#newExchangeProductForm .invalid-feedback').forEach(el => {
          el.textContent = '';
        });
        document.querySelectorAll('#newExchangeProductForm .is-invalid').forEach(el => {
          el.classList.remove('is-invalid');
        });

        // D\u00e9sactiver le bouton pendant le traitement
        saveExchangeProductBtn.disabled = true;
        saveExchangeProductBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Enregistrement...';

        try {
          const response = await fetch('{{ route('sales.exchange-products.store') }}', {
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
                const input = document.getElementById(`new_exchange_product_${field}`);
                const feedback = document.getElementById(`new_exchange_product_${field}_error`);
                if (input) {
                  input.classList.add('is-invalid');
                }
                if (feedback) {
                  feedback.textContent = messages.join(' ');
                }
              });
            }
            return;
          }

          // S\u00e9lectionner automatiquement le nouveau produit
          // Transf\u00e9rer l'IMEI saisi dans la modale vers le champ principal
          const modalImeiInput = document.getElementById('new_exchange_product_imei');
          const mainImeiInput  = document.getElementById('exchange_imei');
          if (modalImeiInput && mainImeiInput && modalImeiInput.value.trim()) {
            mainImeiInput.value = modalImeiInput.value.trim();
          }

          selectExchangeProduct(data);

          // Fermer la modale
          const modalEl = document.getElementById('newExchangeProductModal');
          const modal = bootstrap.Modal.getInstance(modalEl);
          modal.hide();

          // R\u00e9initialiser le formulaire
          newExchangeProductForm.reset();

          // Masquer le message "aucun produit trouv\u00e9"
          exchangeProductNotFound.style.display = 'none';

        } catch (error) {
          console.error('Erreur lors de la cr\u00e9ation du produit :', error);
        } finally {
          saveExchangeProductBtn.disabled = false;
          saveExchangeProductBtn.innerHTML = '<i class="bi bi-check-lg me-1"></i>Enregistrer le produit';
        }
      });
    }
  });
</script>
@endpush

<div class="products-grid">
  @forelse($products as $product)
    <div class="product-card">
      <a href="{{ route('products.show', $product) }}" class="product-card__thumb">
        @if($product->image)
          <img src="{{ asset('storage/'.$product->image) }}" alt="{{ $product->name }}" loading="lazy">
        @else
          <div class="product-card__thumb-placeholder">
            <i class="bi bi-controller"></i>
          </div>
        @endif
        @if($product->isOutOfStock())
          <span class="product-card__stock-flag bg-danger">Rupture</span>
        @elseif($product->isLowStock())
          <span class="product-card__stock-flag bg-warning text-dark">Stock faible</span>
        @endif
        @unless($product->is_active)
          <span class="product-card__inactive-flag">Inactif</span>
        @endunless
      </a>

      <div class="product-card__body">
        <div class="product-card__ref"><code>{{ $product->reference }}</code></div>
        <a href="{{ route('products.show', $product) }}" class="product-card__name">{{ $product->name }}</a>
        @if($product->brand)
          <div class="product-card__brand">{{ $product->brand }}</div>
        @endif
        <div class="product-card__meta">
          <span class="badge bg-light text-dark">{{ $product->category?->name ?? '—' }}</span>
          <span class="product-card__stock {{ $product->isOutOfStock() ? 'text-danger' : ($product->isLowStock() ? 'text-warning' : 'text-muted') }}">
            <i class="bi bi-box-seam"></i> {{ $product->stock_quantity }}
          </span>
        </div>
        <div class="product-card__price">{{ number_format($product->sale_price, 0, ',', ' ') }} FCFA</div>
      </div>

      <div class="product-card__actions">
        <a href="{{ route('products.show', $product) }}" class="btn btn-outline-secondary" title="Voir">
          <i class="bi bi-eye"></i>
        </a>
        <a href="{{ route('products.edit', $product) }}" class="btn btn-outline-primary" title="Modifier">
          <i class="bi bi-pencil"></i>
        </a>
        <form action="{{ route('products.destroy', $product) }}" method="POST"
              onsubmit="return confirm('Supprimer ce produit ?')">
          @csrf @method('DELETE')
          <button type="submit" class="btn btn-outline-danger" title="Supprimer">
            <i class="bi bi-trash"></i>
          </button>
        </form>
      </div>
    </div>
  @empty
    <div class="products-grid__empty text-center text-muted py-5">Aucun produit trouvé</div>
  @endforelse
</div>

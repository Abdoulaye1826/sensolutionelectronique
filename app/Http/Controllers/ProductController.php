<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreProductRequest;
use App\Http\Requests\UpdateProductRequest;
use App\Models\Product;
use App\Services\CategoryService;
use App\Services\ProductService;
use App\Services\SupplierService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProductController extends Controller
{
    public function __construct(
        private readonly ProductService $productService,
        private readonly CategoryService $categoryService,
        private readonly SupplierService $supplierService,
    ) {
        $this->authorizeResource(Product::class, 'product');
    }

    public function index(Request $request): View|JsonResponse
    {
        $filters = $request->only(['search', 'category_id', 'brand', 'is_active', 'stock_status', 'sort', 'direction']);
        $products = $this->productService->paginate($filters);
        $categories = $this->categoryService->activeList();
        $brands = $this->productService->getBrands();

        if ($request->ajax()) {
            return response()->json([
                'html' => view('products.partials.grid', compact('products'))->render(),
                'pagination' => view('products.partials.pagination', compact('products'))->render(),
            ]);
        }

        return view('products.index', compact('products', 'categories', 'brands', 'filters'));
    }

    public function create(): View
    {
        $categories = $this->categoryService->activeList();
        $suppliers = $this->supplierService->activeList();

        return view('products.create', compact('categories', 'suppliers'));
    }

    public function store(StoreProductRequest $request): RedirectResponse
    {
        $this->productService->create(
            $request->validated(),
            $request->file('image')
        );

        return redirect()->route('products.index')
            ->with('success', 'Produit créé avec succès.');
    }

    public function show(Product $product): View
    {
        $product->load([
            'category',
            'supplier',
            'stockMovements' => fn ($q) => $q->latest()->limit(10),
            'imeis' => fn ($q) => $q->latest()->with(['sale.customer', 'sale.invoice', 'exchangeSale']),
        ]);

        return view('products.show', compact('product'));
    }

    public function edit(Product $product): View
    {
        $categories = $this->categoryService->activeList();
        $suppliers = $this->supplierService->activeList();
        $product->load(['imeis' => fn ($q) => $q->orderByDesc('id')]);

        return view('products.edit', compact('product', 'categories', 'suppliers'));
    }

    public function update(UpdateProductRequest $request, Product $product): RedirectResponse
    {
        $this->productService->update(
            $product,
            $request->safe()->except(['image', 'remove_image']),
            $request->file('image'),
            $request->boolean('remove_image')
        );

        return redirect()->route('products.index')
            ->with('success', 'Produit mis à jour avec succès.');
    }

    public function destroy(Product $product): RedirectResponse
    {
        try {
            $this->productService->delete($product);
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()->route('products.index')
            ->with('success', 'Produit supprimé avec succès.');
    }
}

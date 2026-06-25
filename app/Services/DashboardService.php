<?php

namespace App\Services;

use App\Enums\InvoiceStatus;
use App\Enums\SaleStatus;
use App\Enums\SaleType;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\StockMovement;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Agrège les statistiques affichées sur le tableau de bord.
 */
class DashboardService
{
    public function getStats(): array
    {
        $today = Carbon::today();
        $startOfMonth = Carbon::now()->startOfMonth();

        $validatedSales = Sale::validated();

        $salesCountMonth = (clone $validatedSales)->where('sale_date', '>=', $startOfMonth)->count();
        $revenueMonth = (float) (clone $validatedSales)->where('sale_date', '>=', $startOfMonth)->sum('total_ttc');

        $marginMonth = (float) SaleItem::query()
            ->join('sales', 'sale_items.sale_id', '=', 'sales.id')
            ->join('products', 'sale_items.product_id', '=', 'products.id')
            ->where('sales.status', SaleStatus::Validated->value)
            ->where('sales.sale_type', SaleType::Vente->value)
            ->where('sales.sale_date', '>=', $startOfMonth)
            ->sum(DB::raw('(sale_items.unit_price - products.purchase_price) * sale_items.quantity'));

        return [
            'revenue_today' => (float) (clone $validatedSales)->forDate($today)->sum('total_ttc'),
            'revenue_month' => $revenueMonth,
            'sales_count' => Sale::validated()->count(),
            'invoices_count' => Invoice::count(),
            'paid_invoices_count' => Invoice::where('status', InvoiceStatus::Paid)->count(),
            'pending_invoices_count' => Invoice::where('status', InvoiceStatus::Issued)->count(),
            'products_count' => Product::count(),
            'low_stock_count' => Product::lowStock()->count(),
            'out_of_stock_count' => Product::outOfStock()->count(),
            'customers_count' => Customer::count(),
            'new_customers_month' => Customer::where('registered_at', '>=', $startOfMonth)->count(),
            'new_customers_today' => Customer::whereDate('registered_at', $today)->count(),

            // ── Statistiques additionnelles ──
            'stock_value' => (float) Product::query()->sum(DB::raw('stock_quantity * purchase_price')),
            'average_sale_amount' => $salesCountMonth > 0 ? round($revenueMonth / $salesCountMonth, 2) : 0.0,
            'exchanges_count_month' => (clone $validatedSales)
                ->where('sale_type', SaleType::Echange)
                ->where('sale_date', '>=', $startOfMonth)
                ->count(),
            'margin_month' => $marginMonth,
        ];
    }

    /**
     * Ventes mensuelles des 12 derniers mois (pour graphique).
     */
    public function getSalesByMonth(): array
    {
        $start = Carbon::now()->subMonths(11)->startOfMonth();

        $rows = Sale::validated()
            ->where('sale_date', '>=', $start)
            ->select(
                DB::raw('YEAR(sale_date) as year'),
                DB::raw('MONTH(sale_date) as month'),
                DB::raw('SUM(total_ttc) as total')
            )
            ->groupBy('year', 'month')
            ->orderBy('year')
            ->orderBy('month')
            ->get();

        $labels = [];
        $data = [];

        for ($i = 0; $i < 12; $i++) {
            $date = Carbon::now()->subMonths(11 - $i);
            $labels[] = $date->translatedFormat('M Y');

            $row = $rows->first(fn ($r) => (int) $r->year === $date->year && (int) $r->month === $date->month);
            $data[] = $row ? (float) $row->total : 0;
        }

        return compact('labels', 'data');
    }

    /**
     * Répartition des ventes par catégorie.
     */
    public function getSalesByCategory(): array
    {
        $rows = SaleItem::query()
            ->join('sales', 'sale_items.sale_id', '=', 'sales.id')
            ->join('products', 'sale_items.product_id', '=', 'products.id')
            ->join('categories', 'products.category_id', '=', 'categories.id')
            ->where('sales.status', SaleStatus::Validated->value)
            ->select('categories.name', DB::raw('SUM(sale_items.line_total) as total'))
            ->groupBy('categories.id', 'categories.name')
            ->orderByDesc('total')
            ->get();

        return [
            'labels' => $rows->pluck('name')->all(),
            'data' => $rows->pluck('total')->map(fn ($v) => (float) $v)->all(),
        ];
    }

    public function getInvoiceStatusSummary(): array
    {
        $rows = Invoice::query()
            ->select('status', DB::raw('COUNT(*) as count'), DB::raw('SUM(total_ttc) as total'))
            ->groupBy('status')
            ->get();

        return [
            'labels' => $rows->map(fn ($row) => ($row->status instanceof InvoiceStatus ? $row->status : InvoiceStatus::from($row->status))->label())->all(),
            'values' => $rows->map(fn ($row) => (float) $row->total)->all(),
            'counts' => $rows->map(fn ($row) => (int) $row->count)->all(),
        ];
    }

    public function getTopCustomers(int $limit = 5): array
    {
        return Customer::query()
            ->join('invoices', 'customers.id', '=', 'invoices.customer_id')
            ->select(
                'customers.id',
                'customers.full_name',
                DB::raw('COUNT(invoices.id) as invoices_count'),
                DB::raw('SUM(invoices.total_ttc) as total_amount')
            )
            ->groupBy('customers.id', 'customers.full_name')
            ->orderByDesc('total_amount')
            ->limit($limit)
            ->get()
            ->all();
    }

    public function getSalesByUser(int $limit = 5): array
    {
        return Sale::query()
            ->validated()
            ->join('users', 'sales.user_id', '=', 'users.id')
            ->select(
                'users.id',
                'users.name',
                DB::raw('COUNT(sales.id) as sales_count'),
                DB::raw('SUM(sales.total_ttc) as total_amount')
            )
            ->groupBy('users.id', 'users.name')
            ->orderByDesc('total_amount')
            ->limit($limit)
            ->get()
            ->all();
    }

    public function getRecentInvoices(int $limit = 8): array
    {
        return Invoice::query()
            ->with(['customer', 'sale'])
            ->orderByDesc('issued_at')
            ->limit($limit)
            ->get()
            ->all();
    }

    /**
     * Top 5 produits les plus vendus.
     */
    public function getTopProducts(int $limit = 5): array
    {
        return SaleItem::query()
            ->join('sales', 'sale_items.sale_id', '=', 'sales.id')
            ->join('products', 'sale_items.product_id', '=', 'products.id')
            ->where('sales.status', SaleStatus::Validated->value)
            ->select(
                'products.name',
                DB::raw('SUM(sale_items.quantity) as total_qty'),
                DB::raw('SUM(sale_items.line_total) as total_amount')
            )
            ->groupBy('products.id', 'products.name')
            ->orderByDesc('total_qty')
            ->limit($limit)
            ->get()
            ->all();
    }

    /**
     * Répartition des ventes validées entre ventes classiques et échanges (sur les 12 derniers mois).
     */
    public function getSalesTypeBreakdown(): array
    {
        $start = Carbon::now()->subMonths(11)->startOfMonth();

        $rows = Sale::validated()
            ->where('sale_date', '>=', $start)
            ->select('sale_type', DB::raw('COUNT(*) as count'))
            ->groupBy('sale_type')
            ->get();

        $venteCount = (int) ($rows->first(fn ($r) => $r->sale_type === SaleType::Vente)?->count ?? 0);
        $echangeCount = (int) ($rows->first(fn ($r) => $r->sale_type === SaleType::Echange)?->count ?? 0);

        return [
            'labels' => ['Ventes', 'Échanges'],
            'data' => [$venteCount, $echangeCount],
        ];
    }

    /**
     * Derniers mouvements de stock enregistrés (entrées, sorties, retours d'échange...).
     */
    public function getRecentStockMovements(int $limit = 8): array
    {
        return StockMovement::query()
            ->with('product')
            ->latest()
            ->limit($limit)
            ->get()
            ->all();
    }

    /**
     * Produits en alerte stock (rupture + faible).
     */
    public function getStockAlerts(int $limit = 5): array
    {
        return Product::query()
            ->with('category')
            ->where(function ($q) {
                $q->outOfStock()
                    ->orWhere(fn ($q2) => $q2->lowStock());
            })
            ->orderBy('stock_quantity')
            ->limit($limit)
            ->get()
            ->all();
    }
}

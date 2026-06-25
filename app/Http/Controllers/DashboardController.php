<?php

namespace App\Http\Controllers;

use App\Services\DashboardService;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __construct(
        private readonly DashboardService $dashboardService
    ) {}

    public function index(): View
    {
        return view('dashboard.index', [
            'stats' => $this->dashboardService->getStats(),
            'salesByMonth' => $this->dashboardService->getSalesByMonth(),
            'salesByCategory' => $this->dashboardService->getSalesByCategory(),
            'invoiceStatusSummary' => $this->dashboardService->getInvoiceStatusSummary(),
            'topProducts' => $this->dashboardService->getTopProducts(),
            'topCustomers' => $this->dashboardService->getTopCustomers(),
            'salesByUser' => $this->dashboardService->getSalesByUser(),
            'recentInvoices' => $this->dashboardService->getRecentInvoices(),
            'stockAlerts' => $this->dashboardService->getStockAlerts(),
            'salesTypeBreakdown' => $this->dashboardService->getSalesTypeBreakdown(),
            'recentStockMovements' => $this->dashboardService->getRecentStockMovements(),
        ]);
    }
}

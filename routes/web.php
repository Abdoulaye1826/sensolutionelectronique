<?php

use App\Http\Controllers\CategoryController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ProductImeiController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\ReturnController;
use App\Http\Controllers\SaleController;
use App\Http\Controllers\StockController;
use App\Http\Controllers\SupplierController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\WarrantyController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Routes Web — MBOUP GAMING SI
|--------------------------------------------------------------------------
*/

Route::get('/', fn () => redirect()->route('login'));

// ── Documents partagés publiquement (liens signés, sans authentification) ──
// Utilisés par le bouton WhatsApp pour que le client ouvre directement le
// PDF (facture ou bon d'échange) sans être redirigé vers la page de
// connexion. Le middleware "signed" garantit qu'un lien ne peut pas être
// deviné ou modifié pour accéder à un autre document.
Route::get('invoices/{invoice}/public-pdf', [InvoiceController::class, 'publicPdf'])
    ->name('invoices.public-pdf')->middleware('signed');
Route::get('sales/{sale}/exchange-voucher/public-pdf', [SaleController::class, 'publicExchangeVoucherPdf'])
    ->name('sales.exchange-voucher.public-pdf')->middleware('signed');

Route::middleware(['auth', 'active'])->group(function () {

    // ── Dashboard (tous les rôles authentifiés) ─────────────
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // ── Profil ────────────────────────────────────────────────
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // ── Notifications ─────────────────────────────────────────
    Route::post('/notifications/read-all', [NotificationController::class, 'readAll'])->name('notifications.read-all');
    Route::get('/notifications/{notification}/read', [NotificationController::class, 'read'])->name('notifications.read');

    // ── Produits & Catégories (Admin, Gestionnaire) ───────────
    Route::middleware('role:admin,manager')->group(function () {
        Route::resource('categories', CategoryController::class)->except(['show']);
        Route::resource('products', ProductController::class);
        Route::resource('suppliers', SupplierController::class)->except(['show']);
        Route::get('stock', [StockController::class, 'index'])->name('stock.index');

        // ── IMEI (téléphones) ────────────────────────────────
        Route::post('products/{product}/imeis', [ProductImeiController::class, 'store'])->name('products.imeis.store');
        Route::delete('imeis/{imei}', [ProductImeiController::class, 'destroy'])->name('imeis.destroy');
    });

    // ── Rapports (Admin, Gestionnaire, Caissier) ──────────────
    Route::middleware('role:admin,manager,cashier')->group(function () {
        Route::get('reports', [ReportController::class, 'index'])->name('reports.index');
    });

    // ── Clients, Ventes, Factures (Admin, Gestionnaire, Caissier)
    Route::middleware('role:admin,manager,cashier')->group(function () {
        Route::resource('customers', CustomerController::class)->except(['show']);
        Route::get('sales/customers/search', [SaleController::class, 'searchCustomers'])->name('sales.customers.search');
        Route::get('sales/exchange-products/search', [SaleController::class, 'searchExchangeProducts'])->name('sales.exchange-products.search');
        Route::post('sales/exchange-products/store', [SaleController::class, 'storeExchangeProduct'])->name('sales.exchange-products.store');
        Route::get('products/{product}/available-imeis', [ProductImeiController::class, 'available'])->name('products.imeis.available');
        Route::resource('sales', SaleController::class)->except(['show']);
        Route::get('sales/{sale}/exchange-voucher/print', [SaleController::class, 'printExchangeVoucher'])->name('sales.exchange-voucher.print');
        Route::get('sales/{sale}/exchange-voucher/download', [SaleController::class, 'downloadExchangeVoucher'])->name('sales.exchange-voucher.download');
        Route::get('sales/{sale}/exchange-voucher/whatsapp', [SaleController::class, 'sendExchangeVoucherWhatsApp'])->name('sales.exchange-voucher.whatsapp');
        Route::get('sales/{sale}/exchange-voucher/whatsapp-payload', [SaleController::class, 'exchangeVoucherWhatsAppPayload'])->name('sales.exchange-voucher.whatsapp.payload');
        Route::resource('invoices', InvoiceController::class)->except(['show']);
        Route::get('invoices/{invoice}/print', [InvoiceController::class, 'print'])->name('invoices.print');
        Route::get('invoices/{invoice}/download', [InvoiceController::class, 'download'])->name('invoices.download');
        Route::get('invoices/{invoice}/whatsapp-payload', [InvoiceController::class, 'whatsAppPayload'])->name('invoices.whatsapp.payload');
        Route::get('invoices/{invoice}/whatsapp', [InvoiceController::class, 'sendWhatsApp'])->name('invoices.whatsapp');
        Route::post('invoices/{invoice}/email', [InvoiceController::class, 'sendEmail'])->name('invoices.email');

        // ── Paiements de factures ─────────────────────────────
        Route::post('invoices/{invoice}/payments', [PaymentController::class, 'store'])->name('invoices.payments.store');
        Route::delete('payments/{payment}', [PaymentController::class, 'destroy'])->name('payments.destroy');

        // ── Gestion des retours ──────────────────────────────
        Route::get('returns', [ReturnController::class, 'index'])->name('returns.index');
        Route::post('returns/{saleItem}', [ReturnController::class, 'store'])->name('returns.store');

        // ── Garanties ─────────────────────────────────────────
        Route::get('warranties', [WarrantyController::class, 'index'])->name('warranties.index');
    });

    // ── Utilisateurs (Admin et Gestionnaire) ─────────────────
    Route::middleware('role:admin,manager')->group(function () {
        Route::resource('users', UserController::class)->except(['show']);
    });
});

require __DIR__.'/auth.php';

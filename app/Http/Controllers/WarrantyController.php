<?php

namespace App\Http\Controllers;

use App\Models\SaleItem;
use Illuminate\Http\Request;
use Illuminate\View\View;

class WarrantyController extends Controller
{
    public function index(Request $request): View
    {
        $search = $request->string('search')->trim()->toString();

        $items = SaleItem::query()
            ->with(['sale.customer', 'sale.invoice', 'product', 'productImei'])
            ->whereHas('sale', fn ($q) => $q->where('status', 'validated'))
            ->when($search, function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->whereHas('sale.invoice', fn ($sq) => $sq->where('invoice_number', 'like', "%{$search}%"))
                        ->orWhereHas('sale', fn ($sq) => $sq->where('sale_number', 'like', "%{$search}%"))
                        ->orWhereHas('sale.customer', function ($sq) use ($search) {
                            $sq->where('full_name', 'like', "%{$search}%")
                                ->orWhere('phone', 'like', "%{$search}%");
                        })
                        ->orWhereHas('product', fn ($sq) => $sq->where('name', 'like', "%{$search}%"))
                        ->orWhereHas('productImei', fn ($sq) => $sq->where('imei', 'like', "%{$search}%"));
                });
            })
            ->orderByDesc('id')
            ->paginate(15)
            ->withQueryString();

        return view('warranties.index', ['items' => $items, 'filters' => ['search' => $search]]);
    }
}

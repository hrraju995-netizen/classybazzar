<?php

use App\Models\Order;
use App\Services\OrderInvoicePdfService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::middleware(['web', 'auth'])->group(function () {
    Route::get('/admin/orders/{order}/invoice-pdf', function (Request $request, Order $order, OrderInvoicePdfService $pdf) {
        abort_unless($request->user()?->is_admin, 403);

        return $pdf->response($order, $request->boolean('inline'));
    })->name('admin.orders.invoice-pdf');
});

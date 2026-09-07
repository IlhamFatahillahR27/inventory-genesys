<?php

use App\Http\Controllers\CategoryController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\TransactionController;
use Illuminate\Support\Facades\Route;

Route::inertia('/', 'Welcome')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::inertia('dashboard', 'Dashboard')->name('dashboard');

    Route::get('categories/datatable', [CategoryController::class, 'index'])->name('categories.datatable');
    Route::resource('categories', CategoryController::class)->only(['index', 'create', 'store', 'edit', 'update', 'destroy']);

    Route::get('products/datatable', [ProductController::class, 'index'])->name('products.datatable');
    Route::resource('products', ProductController::class)->only(['index', 'create', 'store', 'edit', 'update', 'destroy']);

    Route::get('transactions/datatable', [TransactionController::class, 'index'])->name('transactions.datatable');
    Route::resource('transactions', TransactionController::class)->only(['index', 'create', 'store', 'edit', 'update', 'destroy']);
});

require __DIR__.'/settings.php';

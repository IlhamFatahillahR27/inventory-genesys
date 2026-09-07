<?php

namespace App\Services;

use App\Models\Product;
use App\Models\Transaction;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class TransactionService
{
    /**
     * Fetch paginated transaction records for datatable with search and type filtering.
     */
    public function getDataTable(Request $request): LengthAwarePaginator
    {
        $query = Transaction::with(['product', 'user']);

        if ($request->filled('type')) {
            $query->where('type', $request->input('type'));
        }

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->whereHas('product', function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%");
            });
        }

        return $query->latest()->paginate((int) $request->input('per_page', 10));
    }

    /**
     * Create a new transaction with stock adjustment using a DB transaction.
     */
    public function create(array $data): Transaction
    {
        return DB::transaction(function () use ($data) {
            $product = Product::lockForUpdate()->findOrFail($data['product_id']);
            $quantity = (int) $data['quantity'];
            $unitPrice = (int) $data['unit_price'];
            $discount = (int) ($data['discount'] ?? 0);

            // Calculate total price
            $subtotal = $quantity * $unitPrice;
            $discountAmount = (int) round(($subtotal * $discount) / 100);
            $totalPrice = max(0, $subtotal - $discountAmount);

            // Stock adjustment based on transaction type
            $type = is_object($data['type']) ? $data['type']->value : $data['type'];
            if ($type === 'sell') {
                if ($product->stock < $quantity) {
                    throw ValidationException::withMessages([
                        'quantity' => "Insufficient stock. Available stock for {$product->name} is {$product->stock}.",
                    ]);
                }
                $product->decrement('stock', $quantity);
            } elseif ($type === 'buy') {
                $product->increment('stock', $quantity);
            }

            return Transaction::create([
                'user_id' => $data['user_id'] ?? auth('web')->id(),
                'product_id' => $product->id,
                'type' => $type,
                'quantity' => $quantity,
                'unit_price' => $unitPrice,
                'discount' => $discount,
                'total_price' => $totalPrice,
            ]);
        });
    }

    /**
     * Update an existing transaction and reconcile stock using a DB transaction.
     */
    public function update(Transaction $transaction, array $data): Transaction
    {
        return DB::transaction(function () use ($transaction, $data) {
            // 1. Revert previous stock adjustment
            $oldProduct = Product::lockForUpdate()->findOrFail($transaction->product_id);
            $oldType = is_object($transaction->type) ? $transaction->type->value : $transaction->type;
            if ($oldType === 'sell') {
                $oldProduct->increment('stock', $transaction->quantity);
            } elseif ($oldType === 'buy') {
                $oldProduct->decrement('stock', $transaction->quantity);
            }

            // 2. Apply new stock adjustment
            $newProduct = Product::lockForUpdate()->findOrFail($data['product_id']);
            $newQuantity = (int) $data['quantity'];
            $newUnitPrice = (int) $data['unit_price'];
            $newDiscount = (int) ($data['discount'] ?? 0);

            $subtotal = $newQuantity * $newUnitPrice;
            $discountAmount = (int) round(($subtotal * $newDiscount) / 100);
            $totalPrice = max(0, $subtotal - $discountAmount);

            $newType = is_object($data['type']) ? $data['type']->value : $data['type'];
            if ($newType === 'sell') {
                if ($newProduct->stock < $newQuantity) {
                    throw ValidationException::withMessages([
                        'quantity' => "Insufficient stock. Available stock for {$newProduct->name} is {$newProduct->stock}.",
                    ]);
                }
                $newProduct->decrement('stock', $newQuantity);
            } elseif ($newType === 'buy') {
                $newProduct->increment('stock', $newQuantity);
            }

            // 3. Update transaction record
            $transaction->update([
                'user_id' => $data['user_id'] ?? $transaction->user_id,
                'product_id' => $newProduct->id,
                'type' => $newType,
                'quantity' => $newQuantity,
                'unit_price' => $newUnitPrice,
                'discount' => $newDiscount,
                'total_price' => $totalPrice,
            ]);

            return $transaction;
        });
    }

    /**
     * Delete a transaction and rollback stock adjustment using a DB transaction.
     */
    public function delete(Transaction $transaction): bool
    {
        return DB::transaction(function () use ($transaction) {
            $product = Product::lockForUpdate()->findOrFail($transaction->product_id);
            $type = is_object($transaction->type) ? $transaction->type->value : $transaction->type;

            if ($type === 'sell') {
                $product->increment('stock', $transaction->quantity);
            } elseif ($type === 'buy') {
                $product->decrement('stock', $transaction->quantity);
            }

            return (bool) $transaction->delete();
        });
    }
}

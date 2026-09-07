<?php

namespace App\Services;

use App\Models\Product;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ProductService
{
    /**
     * Fetch paginated product records for datatable with search and category filtering.
     */
    public function getDataTable(Request $request): LengthAwarePaginator
    {
        $query = Product::with('category');

        if ($request->filled('category_id')) {
            $query->where('category_id', $request->input('category_id'));
        }

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        return $query->latest()->paginate((int) $request->input('per_page', 10));
    }

    /**
     * Create a new product using a DB transaction.
     */
    public function create(array $data): Product
    {
        return DB::transaction(function () use ($data) {
            return Product::create([
                'category_id' => $data['category_id'],
                'name' => $data['name'],
                'description' => $data['description'] ?? '',
                'price' => (int) $data['price'],
                'stock' => (int) $data['stock'],
                'discount' => (int) ($data['discount'] ?? 0),
            ]);
        });
    }

    /**
     * Update an existing product using a DB transaction.
     */
    public function update(Product $product, array $data): Product
    {
        return DB::transaction(function () use ($product, $data) {
            $product->update([
                'category_id' => $data['category_id'],
                'name' => $data['name'],
                'description' => $data['description'] ?? '',
                'price' => (int) $data['price'],
                'stock' => (int) $data['stock'],
                'discount' => (int) ($data['discount'] ?? 0),
            ]);

            return $product;
        });
    }

    /**
     * Delete a product using a DB transaction.
     */
    public function delete(Product $product): bool
    {
        return DB::transaction(function () use ($product) {
            return (bool) $product->delete();
        });
    }
}

<?php

namespace App\Services;

use App\Models\Category;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CategoryService
{
    /**
     * Fetch paginated category records for datatable with search.
     */
    public function getDataTable(Request $request): LengthAwarePaginator
    {
        $query = Category::query();

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('code', 'like', "%{$search}%")
                  ->orWhere('name', 'like', "%{$search}%");
            });
        }

        return $query->latest()->paginate((int) $request->input('per_page', 10));
    }

    /**
     * Create a new category using a DB transaction.
     */
    public function create(array $data): Category
    {
        return DB::transaction(function () use ($data) {
            return Category::create([
                'code' => strtoupper($data['code']),
                'name' => $data['name'],
            ]);
        });
    }

    /**
     * Update an existing category using a DB transaction.
     */
    public function update(Category $category, array $data): Category
    {
        return DB::transaction(function () use ($category, $data) {
            $category->update([
                'code' => strtoupper($data['code']),
                'name' => $data['name'],
            ]);

            return $category;
        });
    }

    /**
     * Delete a category using a DB transaction.
     */
    public function delete(Category $category): bool
    {
        return DB::transaction(function () use ($category) {
            return (bool) $category->delete();
        });
    }
}

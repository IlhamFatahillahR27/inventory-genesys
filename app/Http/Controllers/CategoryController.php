<?php

namespace App\Http\Controllers;

use App\Http\Requests\CategoryFormRequest;
use App\Http\Requests\DestroyRequest;
use App\Models\Category;
use App\Services\CategoryService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class CategoryController extends Controller
{
    public function __construct(
        protected CategoryService $categoryService
    ) {}

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        if ($request->wantsJson()) {
            return response()->json($this->categoryService->getDataTable($request));
        }

        return Inertia::render('categories/Index');
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return Inertia::render('categories/Form');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(CategoryFormRequest $request)
    {
        $this->categoryService->create($request->validated());

        return redirect()->route('categories.index');
    }

    /**
     * Display the specified resource.
     */
    public function show(Category $category)
    {
        return response()->json($category);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Category $category)
    {
        return Inertia::render('categories/Form', [
            'category' => $category,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(CategoryFormRequest $request, Category $category)
    {
        $this->categoryService->update($category, $request->validated());

        return redirect()->route('categories.index');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(DestroyRequest $request, Category $category)
    {
        $this->categoryService->delete($category);

        return redirect()->route('categories.index');
    }
}

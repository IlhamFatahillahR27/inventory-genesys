<?php

namespace App\Http\Controllers;

use App\Http\Requests\DestroyRequest;
use App\Http\Requests\ProductFormRequest;
use App\Models\Category;
use App\Models\Product;
use App\Services\ProductService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ProductController extends Controller
{
    public function __construct(
        protected ProductService $productService
    ) {}

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        if ($request->wantsJson()) {
            return response()->json($this->productService->getDataTable($request));
        }

        return Inertia::render('products/Index');
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return Inertia::render('products/Form', [
            'categories' => Category::all(['id', 'code', 'name']),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(ProductFormRequest $request)
    {
        $this->productService->create($request->validated());

        return redirect()->route('products.index');
    }

    /**
     * Display the specified resource.
     */
    public function show(Product $product)
    {
        return response()->json($product->load('category'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Product $product)
    {
        return Inertia::render('products/Form', [
            'product' => $product,
            'categories' => Category::all(['id', 'code', 'name']),
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(ProductFormRequest $request, Product $product)
    {
        $this->productService->update($product, $request->validated());

        return redirect()->route('products.index');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(DestroyRequest $request, Product $product)
    {
        $this->productService->delete($product);

        return redirect()->route('products.index');
    }
}

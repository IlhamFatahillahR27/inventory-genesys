<?php

namespace App\Http\Controllers;

use App\Http\Requests\DestroyRequest;
use App\Http\Requests\TransactionFormRequest;
use App\Models\Product;
use App\Models\Transaction;
use App\Services\TransactionService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class TransactionController extends Controller
{
    public function __construct(
        protected TransactionService $transactionService
    ) {}

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        if ($request->wantsJson()) {
            return response()->json($this->transactionService->getDataTable($request));
        }

        return Inertia::render('transactions/Index');
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return Inertia::render('transactions/Form', [
            'products' => Product::all(['id', 'name', 'price', 'stock', 'discount']),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(TransactionFormRequest $request)
    {
        $this->transactionService->create($request->validated());

        return redirect()->route('transactions.index');
    }

    /**
     * Display the specified resource.
     */
    public function show(Transaction $transaction)
    {
        return response()->json($transaction->load(['product', 'user']));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Transaction $transaction)
    {
        return Inertia::render('transactions/Form', [
            'transaction' => $transaction,
            'products' => Product::all(['id', 'name', 'price', 'stock', 'discount']),
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(TransactionFormRequest $request, Transaction $transaction)
    {
        $this->transactionService->update($transaction, $request->validated());

        return redirect()->route('transactions.index');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(DestroyRequest $request, Transaction $transaction)
    {
        $this->transactionService->delete($transaction);

        return redirect()->route('transactions.index');
    }
}

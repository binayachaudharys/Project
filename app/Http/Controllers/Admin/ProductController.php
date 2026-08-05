<?php

namespace App\Http\Controllers\Admin;

use App\Enums\StockReason;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\AdjustStockRequest;
use App\Http\Requests\Admin\StoreProductRequest;
use App\Http\Requests\Admin\UpdateProductRequest;
use App\Models\Product;
use App\Repositories\ProductRepository;
use Illuminate\Http\RedirectResponse;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class ProductController extends Controller
{
    public function index(ProductRepository $products): Response
    {
        return Inertia::render('Admin/Products/Index', [
            'products' => $products->allOrdered(),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Admin/Products/Form', [
            'product' => null,
        ]);
    }

    public function store(StoreProductRequest $request, ProductRepository $products): RedirectResponse
    {
        $products->createProduct($request->validated());

        return redirect()->route('admin.products.index')->with('success', 'Product created.');
    }

    public function edit(Product $product): Response
    {
        return Inertia::render('Admin/Products/Form', [
            'product' => $product,
        ]);
    }

    public function update(UpdateProductRequest $request, Product $product, ProductRepository $products): RedirectResponse
    {
        $products->updateProduct($product, $request->validated());

        return redirect()->route('admin.products.index')->with('success', 'Product updated.');
    }

    public function destroy(Product $product, ProductRepository $products): RedirectResponse
    {
        $products->deleteProduct($product);

        return redirect()->route('admin.products.index')->with('success', 'Product deleted.');
    }

    public function adjustStock(
        AdjustStockRequest $request,
        Product $product,
        ProductRepository $products,
    ): RedirectResponse {
        try {
            $products->adjustStock(
                $product,
                (int) $request->validated('delta'),
                StockReason::from($request->validated('reason')),
                $request->user(),
            );
        } catch (ValidationException $e) {
            return back()->withErrors($e->errors());
        }

        return back()->with('success', 'Stock updated.');
    }
}

<?php

namespace App\Repositories;

use App\Enums\StockReason;
use App\Models\Product;
use App\Models\StockMovement;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Jsdecena\Baserepo\BaseRepository;

class ProductRepository extends BaseRepository
{
    public function __construct(Product $model)
    {
        parent::__construct($model);
    }

    public function allActive(): Collection
    {
        return $this->model->newQuery()
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'sku', 'price', 'stock_qty']);
    }

    public function allOrdered(): Collection
    {
        return $this->model->newQuery()
            ->orderBy('name')
            ->get();
    }

    /**
     * @param  array{name:string, sku?:string|null, price:mixed, stock_qty?:int, low_stock_threshold?:int, is_active?:bool}  $data
     */
    public function createProduct(array $data): Product
    {
        return $this->create([
            'name' => $data['name'],
            'sku' => $data['sku'] ?? null,
            'price' => $data['price'],
            'stock_qty' => (int) ($data['stock_qty'] ?? 0),
            'low_stock_threshold' => (int) ($data['low_stock_threshold'] ?? 5),
            'is_active' => (bool) ($data['is_active'] ?? true),
        ]);
    }

    /**
     * @param  array{name:string, sku?:string|null, price:mixed, low_stock_threshold?:int, is_active?:bool}  $data
     */
    public function updateProduct(Product $product, array $data): Product
    {
        $this->update([
            'name' => $data['name'],
            'sku' => $data['sku'] ?? null,
            'price' => $data['price'],
            'low_stock_threshold' => (int) ($data['low_stock_threshold'] ?? $product->low_stock_threshold),
            'is_active' => (bool) ($data['is_active'] ?? $product->is_active),
        ], $product);

        return $product->fresh();
    }

    public function deleteProduct(Product $product): bool
    {
        return (bool) $product->delete();
    }

    /**
     * Adjust stock by delta and record a stock movement under a row lock.
     */
    public function adjustStock(Product $product, int $delta, StockReason $reason, User $actor): Product
    {
        return DB::transaction(function () use ($product, $delta, $reason, $actor) {
            /** @var Product $locked */
            $locked = $this->model->newQuery()->lockForUpdate()->findOrFail($product->id);

            $nextQty = $locked->stock_qty + $delta;
            if ($nextQty < 0) {
                throw ValidationException::withMessages([
                    'delta' => 'Stock cannot go below zero.',
                ]);
            }

            $locked->update(['stock_qty' => $nextQty]);

            StockMovement::create([
                'product_id' => $locked->id,
                'delta' => $delta,
                'reason' => $reason,
                'sale_id' => null,
                'user_id' => $actor->id,
            ]);

            return $locked->fresh();
        });
    }
}

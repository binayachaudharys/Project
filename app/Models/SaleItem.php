<?php

namespace App\Models;

use App\Enums\ItemType;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

#[Fillable(['sale_id', 'item_type', 'item_id', 'name_snapshot', 'qty', 'unit_price', 'line_total'])]
class SaleItem extends Model
{
    protected function casts(): array
    {
        return [
            'item_type' => ItemType::class,
            'qty' => 'integer',
            'unit_price' => 'decimal:2',
            'line_total' => 'decimal:2',
        ];
    }

    public function sale(): BelongsTo
    {
        return $this->belongsTo(Sale::class);
    }

    public function item(): MorphTo
    {
        return $this->morphTo();
    }
}

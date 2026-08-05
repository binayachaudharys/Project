<?php

namespace App\Repositories;

use App\Models\SaleItem;
use Jsdecena\Baserepo\BaseRepository;

class SaleItemRepository extends BaseRepository
{
    public function __construct(SaleItem $model)
    {
        parent::__construct($model);
    }
}

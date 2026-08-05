<?php

namespace App\Repositories;

use App\Models\Product;
use Jsdecena\Baserepo\BaseRepository;

class ProductRepository extends BaseRepository
{
    public function __construct(Product $model)
    {
        parent::__construct($model);
    }
}

<?php

namespace App\Repositories;

use App\Models\StockMovement;
use Jsdecena\Baserepo\BaseRepository;

class StockMovementRepository extends BaseRepository
{
    public function __construct(StockMovement $model)
    {
        parent::__construct($model);
    }
}

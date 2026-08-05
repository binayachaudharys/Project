<?php

namespace App\Repositories;

use App\Models\Sale;
use Jsdecena\Baserepo\BaseRepository;
use LogicException;

class SaleRepository extends BaseRepository
{
    public function __construct(Sale $model)
    {
        parent::__construct($model);
    }

    /**
     * Complete a POS sale paid in cash: mark paid, record payment, deduct stock.
     *
     * Implemented in a later task (POS checkout flow).
     */
    public function checkoutCash(array $data): Sale
    {
        throw new LogicException('SaleRepository::checkoutCash() is not implemented yet.');
    }
}

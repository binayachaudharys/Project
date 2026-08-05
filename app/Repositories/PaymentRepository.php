<?php

namespace App\Repositories;

use App\Models\Payment;
use Jsdecena\Baserepo\BaseRepository;

class PaymentRepository extends BaseRepository
{
    public function __construct(Payment $model)
    {
        parent::__construct($model);
    }
}

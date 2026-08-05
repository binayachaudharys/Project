<?php

namespace App\Repositories;

use App\Models\Service;
use Jsdecena\Baserepo\BaseRepository;

class ServiceRepository extends BaseRepository
{
    public function __construct(Service $model)
    {
        parent::__construct($model);
    }
}

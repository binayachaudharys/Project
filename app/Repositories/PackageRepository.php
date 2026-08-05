<?php

namespace App\Repositories;

use App\Models\Package;
use Illuminate\Database\Eloquent\Collection;
use Jsdecena\Baserepo\BaseRepository;

class PackageRepository extends BaseRepository
{
    public function __construct(Package $model)
    {
        parent::__construct($model);
    }

    public function allActive(): Collection
    {
        return $this->model->newQuery()
            ->where('is_active', true)
            ->with('services:id,name')
            ->orderBy('name')
            ->get(['id', 'name', 'description', 'price']);
    }
}

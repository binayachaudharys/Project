<?php

namespace App\Repositories;

use App\Models\Package;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
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

    public function allWithServices(): Collection
    {
        return $this->model->newQuery()
            ->with('services:id,name')
            ->orderBy('name')
            ->get();
    }

    /**
     * @param  array{name:string, description?:string|null, price:mixed, is_active?:bool, service_ids?:array<int,int>}  $data
     */
    public function createPackage(array $data): Package
    {
        return DB::transaction(function () use ($data) {
            /** @var Package $package */
            $package = $this->create([
                'name' => $data['name'],
                'description' => $data['description'] ?? null,
                'price' => $data['price'],
                'is_active' => (bool) ($data['is_active'] ?? true),
            ]);

            $package->services()->sync($data['service_ids'] ?? []);

            return $package->load('services:id,name');
        });
    }

    /**
     * @param  array{name:string, description?:string|null, price:mixed, is_active?:bool, service_ids?:array<int,int>}  $data
     */
    public function updatePackage(Package $package, array $data): Package
    {
        return DB::transaction(function () use ($package, $data) {
            $this->update([
                'name' => $data['name'],
                'description' => $data['description'] ?? null,
                'price' => $data['price'],
                'is_active' => (bool) ($data['is_active'] ?? $package->is_active),
            ], $package);

            $package->services()->sync($data['service_ids'] ?? []);

            return $package->fresh()->load('services:id,name');
        });
    }

    public function deletePackage(Package $package): bool
    {
        return DB::transaction(function () use ($package) {
            $package->services()->detach();

            return (bool) $package->delete();
        });
    }
}

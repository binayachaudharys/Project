<?php

namespace App\Repositories;

use App\Models\Service;
use Illuminate\Database\Eloquent\Collection;
use Jsdecena\Baserepo\BaseRepository;

class ServiceRepository extends BaseRepository
{
    public function __construct(Service $model)
    {
        parent::__construct($model);
    }

    public function allActive(): Collection
    {
        return $this->model->newQuery()
            ->where('is_active', true)
            ->orderBy('category')
            ->orderBy('name')
            ->get(['id', 'name', 'category', 'description', 'duration_minutes', 'price']);
    }

    public function allOrdered(): Collection
    {
        return $this->model->newQuery()
            ->orderBy('category')
            ->orderBy('name')
            ->get();
    }

    /**
     * @param  array{name:string, category?:string|null, description?:string|null, duration_minutes:int, price:mixed, is_active?:bool}  $data
     */
    public function createService(array $data): Service
    {
        return $this->create([
            'name' => $data['name'],
            'category' => $data['category'] ?? null,
            'description' => $data['description'] ?? null,
            'duration_minutes' => (int) $data['duration_minutes'],
            'price' => $data['price'],
            'is_active' => (bool) ($data['is_active'] ?? true),
        ]);
    }

    /**
     * @param  array{name:string, category?:string|null, description?:string|null, duration_minutes:int, price:mixed, is_active?:bool}  $data
     */
    public function updateService(Service $service, array $data): Service
    {
        $this->update([
            'name' => $data['name'],
            'category' => $data['category'] ?? $service->category,
            'description' => $data['description'] ?? null,
            'duration_minutes' => (int) $data['duration_minutes'],
            'price' => $data['price'],
            'is_active' => (bool) ($data['is_active'] ?? $service->is_active),
        ], $service);

        return $service->fresh();
    }

    public function deleteService(Service $service): bool
    {
        return (bool) $service->delete();
    }
}

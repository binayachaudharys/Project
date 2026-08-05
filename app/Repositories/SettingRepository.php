<?php

namespace App\Repositories;

use App\Models\Setting;
use Illuminate\Support\Collection;
use Jsdecena\Baserepo\BaseRepository;

class SettingRepository extends BaseRepository
{
    /** @var list<string> */
    public const MANAGED_KEYS = [
        'salon_open',
        'salon_close',
        'slot_minutes',
        'package_duration',
        'auto_confirm',
        'max_concurrent',
    ];

    public function __construct(Setting $model)
    {
        parent::__construct($model);
    }

    /**
     * Current resolved settings for admin form (DB with config fallback).
     *
     * @return array<string, mixed>
     */
    public function allResolved(): array
    {
        $values = [];

        foreach (self::MANAGED_KEYS as $key) {
            $values[$key] = match ($key) {
                'auto_confirm' => Setting::resolveBool($key, (bool) config("salon.{$key}", true)),
                'slot_minutes', 'package_duration', 'max_concurrent' => (int) Setting::resolve($key, config("salon.{$key}")),
                default => (string) Setting::resolve($key, config("salon.{$key}")),
            };
        }

        return $values;
    }

    /**
     * Upsert managed setting keys from form input.
     *
     * @param  array<string, mixed>  $data
     */
    public function updateMany(array $data): Collection
    {
        $updated = collect();

        foreach (self::MANAGED_KEYS as $key) {
            if (! array_key_exists($key, $data)) {
                continue;
            }

            $value = $data[$key];
            if ($key === 'auto_confirm') {
                $value = filter_var($value, FILTER_VALIDATE_BOOLEAN) ? '1' : '0';
            } else {
                $value = (string) $value;
            }

            $setting = $this->model->newQuery()->updateOrCreate(
                ['key' => $key],
                ['value' => $value],
            );

            $updated->push($setting);
        }

        return $updated;
    }
}

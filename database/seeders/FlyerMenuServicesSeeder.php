<?php

namespace Database\Seeders;

use App\Models\Service;
use Illuminate\Database\Seeder;

class FlyerMenuServicesSeeder extends Seeder
{
    /**
     * Seed flyer package-menu items as active bookable services.
     */
    public function run(): void
    {
        foreach (config('salon.menu', []) as $category) {
            $title = $category['title'] ?? 'Services';
            $badge = $category['badge'] ?? null;
            $note = $category['note'] ?? null;

            foreach ($category['items'] ?? [] as $item) {
                $name = $item['name'] ?? null;
                if (! $name) {
                    continue;
                }

                [$price, $priceNote] = $this->parsePrice($item['price'] ?? '0');

                $descriptionParts = array_values(array_filter([
                    $badge ? "{$badge} — {$title}" : $title,
                    $priceNote,
                    $note,
                ]));

                Service::query()->updateOrCreate(
                    ['name' => $name],
                    [
                        'category' => $title,
                        'description' => implode(' · ', $descriptionParts),
                        'duration_minutes' => $this->guessDuration($title, $name),
                        'price' => $price,
                        'is_active' => true,
                    ]
                );
            }
        }

        // Remove old demo service that is not on the flyer menu.
        Service::query()->where('name', 'Hair Color')->delete();
    }

    /**
     * @return array{0: float|int, 1: string|null}
     */
    private function parsePrice(string $raw): array
    {
        $value = trim($raw);

        if ($value === '' || strcasecmp($value, 'Consult') === 0) {
            return [0, 'Consult for pricing'];
        }

        if (str_contains(strtoupper($value), '%')) {
            return [0, $value];
        }

        if (preg_match('/([\d,]+)/', $value, $matches) === 1) {
            $amount = (float) str_replace(',', '', $matches[1]);
            $note = str_contains($value, '–') || str_contains($value, '-') || str_contains($value, '+')
                ? "Flyer price {$value}"
                : null;

            return [$amount, $note];
        }

        return [0, $value];
    }

    private function guessDuration(string $category, string $name): int
    {
        $key = strtolower("{$category} {$name}");

        return match (true) {
            str_contains($key, 'eyebrow') => 15,
            str_contains($key, 'upper lips'), str_contains($key, 'underarms') => 30,
            str_contains($key, 'hair cut') => 45,
            str_contains($key, 'full body'), str_contains($key, 'laser full') => 120,
            str_contains($key, 'colour'), str_contains($key, 'keratin'),
            str_contains($key, 'botox'), str_contains($key, 'nanoplastia'),
            str_contains($key, 'cysteine') => 90,
            default => 60,
        };
    }
}

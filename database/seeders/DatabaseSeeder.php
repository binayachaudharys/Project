<?php

namespace Database\Seeders;

use App\Models\Package;
use App\Models\Product;
use App\Models\Service;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        User::factory()->owner()->create([
            'name' => 'Salon Owner',
            'email' => 'owner@prettygirls.local',
            'phone' => '9800000001',
            'password' => bcrypt('ChangeMeOwner1!'),
        ]);

        User::factory()->staff()->create([
            'name' => 'Salon Staff',
            'email' => 'staff@prettygirls.local',
            'phone' => '9800000002',
            'password' => bcrypt('ChangeMeStaff1!'),
        ]);

        User::factory()->customer()->create([
            'name' => 'Salon Customer',
            'email' => 'customer@prettygirls.local',
            'phone' => '9800000003',
            'password' => bcrypt('ChangeMeCustomer1!'),
        ]);

        $this->call(FlyerMenuServicesSeeder::class);

        $cut = Service::query()->where('name', 'Hair Cut')->first();
        $colour = Service::query()->where('name', 'Full Hair Colour')->first();

        $pkg = Package::create([
            'name' => 'Glow Package',
            'description' => 'Cut + colour bundle',
            'price' => 4000,
            'is_active' => true,
        ]);
        if ($cut && $colour) {
            $pkg->services()->sync([$cut->id, $colour->id]);
        }

        Product::create([
            'name' => 'Shampoo 250ml',
            'sku' => 'SH-250',
            'price' => 450,
            'stock_qty' => 20,
            'low_stock_threshold' => 5,
            'is_active' => true,
        ]);

        Setting::create(['key' => 'salon_open', 'value' => '10:00']);
        Setting::create(['key' => 'salon_close', 'value' => '19:00']);
        Setting::create(['key' => 'slot_minutes', 'value' => '30']);
        Setting::create(['key' => 'package_duration', 'value' => '60']);
        Setting::create(['key' => 'auto_confirm', 'value' => '1']);
        Setting::create(['key' => 'max_concurrent', 'value' => '1']);
        Setting::create(['key' => 'vat_enabled', 'value' => '1']);
        Setting::create(['key' => 'vat_rate', 'value' => '13']);
        Setting::create(['key' => 'vat_inclusive', 'value' => '0']);
    }
}

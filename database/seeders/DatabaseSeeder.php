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

        $cut = Service::create([
            'name' => 'Hair Cut',
            'description' => 'Ladies hair cut',
            'duration_minutes' => 45,
            'price' => 800,
            'is_active' => true,
        ]);

        $color = Service::create([
            'name' => 'Hair Color',
            'description' => 'Full color',
            'duration_minutes' => 90,
            'price' => 2500,
            'is_active' => true,
        ]);

        $pkg = Package::create([
            'name' => 'Glow Package',
            'description' => 'Cut + color bundle',
            'price' => 3000,
            'is_active' => true,
        ]);
        $pkg->services()->sync([$cut->id, $color->id]);

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
    }
}

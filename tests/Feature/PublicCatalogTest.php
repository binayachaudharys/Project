<?php

namespace Tests\Feature;

use App\Models\Package;
use App\Models\Service;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicCatalogTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }

    public function test_home_shows_salon_brand(): void
    {
        $this->get('/')->assertOk()->assertInertia(fn ($page) => $page
            ->component('Home')
            ->where('salon.name', config('salon.name'))
            ->has('salon.menu')
            ->has('services')
        );
    }

    public function test_services_index_lists_active_services(): void
    {
        Service::factory()->create(['name' => 'Hair Cut', 'is_active' => true]);
        Service::factory()->create(['is_active' => false]);

        $this->get(route('services.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Services/Index')
                ->has('services', 1)
            );
    }

    public function test_packages_index_lists_active_packages(): void
    {
        Package::factory()->create(['name' => 'Glow Package', 'is_active' => true]);
        Package::factory()->create(['is_active' => false]);

        $this->get(route('packages.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Packages/Index')
                ->has('packages', 1)
                ->has('menu')
            );
    }

    public function test_packages_show_displays_single_package_with_services(): void
    {
        $cut = Service::factory()->create(['name' => 'Hair Cut', 'is_active' => true]);
        $color = Service::factory()->create(['name' => 'Hair Color', 'is_active' => true]);

        $package = Package::factory()->create(['name' => 'Glow Package', 'is_active' => true]);
        $package->services()->sync([$cut->id, $color->id]);

        $this->get(route('packages.show', $package))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Packages/Show')
                ->where('package.name', 'Glow Package')
                ->has('package.services', 2)
            );
    }
}

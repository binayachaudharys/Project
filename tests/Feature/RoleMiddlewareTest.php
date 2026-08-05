<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RoleMiddlewareTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }

    public function test_customer_cannot_access_admin_route(): void
    {
        $user = User::factory()->create(['role' => UserRole::Customer]);

        $this->actingAs($user)
            ->get('/admin')
            ->assertForbidden();
    }

    public function test_owner_can_access_admin_route(): void
    {
        $user = User::factory()->create(['role' => UserRole::Owner]);

        $this->actingAs($user)
            ->get('/admin')
            ->assertOk();
    }

    public function test_staff_can_access_pos(): void
    {
        $user = User::factory()->create(['role' => UserRole::Staff]);

        $this->actingAs($user)
            ->get('/pos')
            ->assertOk();
    }

    public function test_owner_can_access_log_viewer(): void
    {
        $user = User::factory()->create(['role' => UserRole::Owner]);

        $this->actingAs($user)
            ->get(route('log-viewer.index'))
            ->assertOk();
    }

    public function test_staff_cannot_access_log_viewer(): void
    {
        $user = User::factory()->create(['role' => UserRole::Staff]);

        $this->actingAs($user)
            ->get(route('log-viewer.index'))
            ->assertForbidden();
    }
}

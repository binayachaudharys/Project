<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class DomainSchemaTest extends TestCase
{
    use RefreshDatabase;

    public function test_core_tables_exist(): void
    {
        foreach ([
            'users', 'services', 'packages', 'package_service', 'products',
            'appointments', 'sales', 'sale_items', 'payments',
            'stock_movements', 'settings',
        ] as $table) {
            $this->assertTrue(Schema::hasTable($table), "Missing table: {$table}");
        }

        $this->assertTrue(Schema::hasColumns('users', ['role', 'phone']));
        $this->assertTrue(Schema::hasColumns('appointments', [
            'customer_id', 'staff_id', 'bookable_type', 'bookable_id',
            'starts_at', 'ends_at', 'status',
        ]));
        $this->assertTrue(Schema::hasColumns('sales', [
            'sale_number', 'subtotal', 'discount', 'total', 'status',
        ]));
        $this->assertTrue(Schema::hasColumns('payments', [
            'method', 'amount', 'status', 'idempotency_key',
        ]));
    }
}

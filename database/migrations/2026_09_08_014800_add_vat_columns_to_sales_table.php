<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->decimal('tax', 12, 2)->default(0)->after('discount');
            $table->decimal('tax_rate', 5, 2)->default(0)->after('tax');
            $table->boolean('prices_include_vat')->default(false)->after('tax_rate');
        });
    }

    public function down(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->dropColumn(['tax', 'tax_rate', 'prices_include_vat']);
        });
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $prefix = config('cryptogateway.database.table_prefix', 'crypto_');

        Schema::connection($this->getConnection())->create($prefix . 'gateway_configs', function (Blueprint $table) {
            $table->id();
            $table->string('coin', 10)->unique();
            $table->string('display_name');
            $table->boolean('is_enabled')->default(true)->index();
            $table->integer('min_confirmations')->default(1);
            $table->decimal('min_amount', 36, 18)->default(0);
            $table->decimal('max_amount', 36, 18)->nullable();
            $table->decimal('fee_percentage', 5, 2)->default(0);
            $table->json('settings')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        $prefix = config('cryptogateway.database.table_prefix', 'crypto_');
        Schema::connection($this->getConnection())->dropIfExists($prefix . 'gateway_configs');
    }

    public function getConnection(): ?string
    {
        return config('cryptogateway.database.connection');
    }
};

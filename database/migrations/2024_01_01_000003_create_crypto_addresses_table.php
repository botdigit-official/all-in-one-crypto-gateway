<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $prefix = config('cryptogateway.database.table_prefix', 'crypto_');

        Schema::connection($this->getConnection())->create($prefix . 'addresses', function (Blueprint $table) {
            $table->id();
            $table->string('coin', 10)->index();
            $table->string('address', 255)->index();
            $table->string('label')->nullable();
            $table->unsignedBigInteger('wallet_id')->index();
            $table->string('derivation_path')->nullable();
            $table->boolean('is_used')->default(false)->index();
            $table->timestamps();

            $table->unique(['coin', 'address']);
        });
    }

    public function down(): void
    {
        $prefix = config('cryptogateway.database.table_prefix', 'crypto_');
        Schema::connection($this->getConnection())->dropIfExists($prefix . 'addresses');
    }

    public function getConnection(): ?string
    {
        return config('cryptogateway.database.connection');
    }
};

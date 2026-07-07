<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $prefix = config('cryptogateway.database.table_prefix', 'crypto_');

        Schema::connection($this->getConnection())->create($prefix . 'transactions', function (Blueprint $table) {
            $table->id();
            $table->string('coin', 10)->index();
            $table->string('tx_hash', 255)->unique();
            $table->string('from_address', 255)->index();
            $table->string('to_address', 255)->index();
            $table->decimal('amount', 36, 18);
            $table->decimal('fee', 36, 18)->nullable();
            $table->integer('confirmations')->default(0);
            $table->enum('status', ['pending', 'confirmed', 'failed'])->default('pending')->index();
            $table->enum('direction', ['incoming', 'outgoing'])->index();
            $table->unsignedBigInteger('block_number')->nullable()->index();
            $table->string('block_hash', 255)->nullable();
            $table->json('raw_data')->nullable();
            $table->unsignedBigInteger('wallet_id')->nullable()->index();
            $table->timestamps();

            $table->index(['coin', 'status']);
            $table->index(['to_address', 'status']);
        });
    }

    public function down(): void
    {
        $prefix = config('cryptogateway.database.table_prefix', 'crypto_');
        Schema::connection($this->getConnection())->dropIfExists($prefix . 'transactions');
    }

    public function getConnection(): ?string
    {
        return config('cryptogateway.database.connection');
    }
};

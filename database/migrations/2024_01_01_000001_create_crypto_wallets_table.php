<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $prefix = config('cryptogateway.database.table_prefix', 'crypto_');

        Schema::connection($this->getConnection())->create($prefix . 'wallets', function (Blueprint $table) {
            $table->id();
            $table->string('coin', 10)->index();
            $table->string('address', 255)->index();
            $table->string('label')->nullable();
            $table->text('private_key')->nullable();  // Encrypted at rest
            $table->text('public_key')->nullable();
            $table->unsignedBigInteger('user_id')->nullable()->index();
            $table->boolean('is_active')->default(true)->index();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->unique(['coin', 'address']);
        });
    }

    public function down(): void
    {
        $prefix = config('cryptogateway.database.table_prefix', 'crypto_');
        Schema::connection($this->getConnection())->dropIfExists($prefix . 'wallets');
    }

    public function getConnection(): ?string
    {
        return config('cryptogateway.database.connection');
    }
};

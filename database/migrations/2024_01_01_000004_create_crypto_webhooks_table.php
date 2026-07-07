<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $prefix = config('cryptogateway.database.table_prefix', 'crypto_');

        Schema::connection($this->getConnection())->create($prefix . 'webhooks', function (Blueprint $table) {
            $table->id();
            $table->string('coin', 10)->index();
            $table->string('event_type')->index();
            $table->json('payload');
            $table->string('signature')->nullable();
            $table->boolean('is_verified')->default(false);
            $table->boolean('is_processed')->default(false)->index();
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();

            $table->index(['coin', 'event_type']);
            $table->index(['is_processed', 'created_at']);
        });
    }

    public function down(): void
    {
        $prefix = config('cryptogateway.database.table_prefix', 'crypto_');
        Schema::connection($this->getConnection())->dropIfExists($prefix . 'webhooks');
    }

    public function getConnection(): ?string
    {
        return config('cryptogateway.database.connection');
    }
};

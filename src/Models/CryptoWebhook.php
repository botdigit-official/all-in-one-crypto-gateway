<?php

declare(strict_types=1);

namespace Botdigit\CryptoGateway\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * CryptoWebhook model — logs all incoming webhook events.
 *
 * @property int         $id
 * @property string      $coin
 * @property string      $event_type
 * @property array       $payload
 * @property string|null $signature
 * @property bool        $is_verified
 * @property bool        $is_processed
 * @property string|null $processed_at
 */
class CryptoWebhook extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'payload'      => 'array',
            'is_verified'  => 'boolean',
            'is_processed' => 'boolean',
            'processed_at' => 'datetime',
        ];
    }

    public function getTable(): string
    {
        $prefix = config('cryptogateway.database.table_prefix', 'crypto_');
        return $prefix . 'webhooks';
    }

    public function getConnectionName(): ?string
    {
        return config('cryptogateway.database.connection');
    }

    // ── Scopes ──────────────────────────────────────────────────────────

    public function scopeUnprocessed($query)
    {
        return $query->where('is_processed', false);
    }

    public function scopeVerified($query)
    {
        return $query->where('is_verified', true);
    }

    public function scopeForCoin($query, string $coin)
    {
        return $query->where('coin', strtoupper($coin));
    }

    // ── Helpers ─────────────────────────────────────────────────────────

    public function markAsProcessed(): bool
    {
        return $this->update([
            'is_processed' => true,
            'processed_at' => now(),
        ]);
    }
}

<?php

declare(strict_types=1);

namespace Botdigit\CryptoGateway\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * CryptoAddress model — represents a generated receiving address.
 *
 * @property int         $id
 * @property string      $coin
 * @property string      $address
 * @property string|null $label
 * @property int         $wallet_id
 * @property string|null $derivation_path
 * @property bool        $is_used
 */
class CryptoAddress extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'is_used' => 'boolean',
        ];
    }

    public function getTable(): string
    {
        $prefix = config('cryptogateway.database.table_prefix', 'crypto_');
        return $prefix . 'addresses';
    }

    public function getConnectionName(): ?string
    {
        return config('cryptogateway.database.connection');
    }

    // ── Relationships ───────────────────────────────────────────────────

    public function wallet(): BelongsTo
    {
        return $this->belongsTo(CryptoWallet::class, 'wallet_id');
    }

    // ── Scopes ──────────────────────────────────────────────────────────

    public function scopeUnused($query)
    {
        return $query->where('is_used', false);
    }

    public function scopeUsed($query)
    {
        return $query->where('is_used', true);
    }

    public function scopeForCoin($query, string $coin)
    {
        return $query->where('coin', strtoupper($coin));
    }

    // ── Helpers ─────────────────────────────────────────────────────────

    public function markAsUsed(): bool
    {
        return $this->update(['is_used' => true]);
    }
}

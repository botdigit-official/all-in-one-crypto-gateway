<?php

declare(strict_types=1);

namespace Botdigit\CryptoGateway\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * CryptoWallet model — represents a managed cryptocurrency wallet.
 *
 * @property int         $id
 * @property string      $coin
 * @property string      $address
 * @property string|null $label
 * @property string|null $private_key   (encrypted)
 * @property string|null $public_key
 * @property int|null    $user_id
 * @property bool        $is_active
 * @property array|null  $metadata
 */
class CryptoWallet extends Model
{
    protected $guarded = ['id'];

    protected $hidden = ['private_key'];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'metadata'  => 'array',
            'private_key' => 'encrypted',
        ];
    }

    public function getTable(): string
    {
        $prefix = config('cryptogateway.database.table_prefix', 'crypto_');
        return $prefix . 'wallets';
    }

    public function getConnectionName(): ?string
    {
        return config('cryptogateway.database.connection');
    }

    // ── Relationships ───────────────────────────────────────────────────

    public function transactions(): HasMany
    {
        return $this->hasMany(CryptoTransaction::class, 'wallet_id');
    }

    public function addresses(): HasMany
    {
        return $this->hasMany(CryptoAddress::class, 'wallet_id');
    }

    // ── Scopes ──────────────────────────────────────────────────────────

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeForCoin($query, string $coin)
    {
        return $query->where('coin', strtoupper($coin));
    }

    public function scopeForUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }
}

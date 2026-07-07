<?php

declare(strict_types=1);

namespace Botdigit\CryptoGateway\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * CryptoTransaction model — represents a blockchain transaction record.
 *
 * @property int         $id
 * @property string      $coin
 * @property string      $tx_hash
 * @property string      $from_address
 * @property string      $to_address
 * @property string      $amount
 * @property string|null $fee
 * @property int         $confirmations
 * @property string      $status         (pending, confirmed, failed)
 * @property string      $direction      (incoming, outgoing)
 * @property int|null    $block_number
 * @property string|null $block_hash
 * @property array|null  $raw_data
 * @property int|null    $wallet_id
 */
class CryptoTransaction extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'amount'        => 'decimal:18',
            'fee'           => 'decimal:18',
            'confirmations' => 'integer',
            'block_number'  => 'integer',
            'raw_data'      => 'array',
        ];
    }

    public function getTable(): string
    {
        $prefix = config('cryptogateway.database.table_prefix', 'crypto_');
        return $prefix . 'transactions';
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

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeConfirmed($query)
    {
        return $query->where('status', 'confirmed');
    }

    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }

    public function scopeIncoming($query)
    {
        return $query->where('direction', 'incoming');
    }

    public function scopeOutgoing($query)
    {
        return $query->where('direction', 'outgoing');
    }

    public function scopeForCoin($query, string $coin)
    {
        return $query->where('coin', strtoupper($coin));
    }

    public function scopeForAddress($query, string $address)
    {
        return $query->where(function ($q) use ($address) {
            $q->where('from_address', $address)
              ->orWhere('to_address', $address);
        });
    }

    // ── Helpers ─────────────────────────────────────────────────────────

    public function isConfirmed(): bool
    {
        return $this->status === 'confirmed';
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function meetsConfirmations(int $required): bool
    {
        return $this->confirmations >= $required;
    }
}

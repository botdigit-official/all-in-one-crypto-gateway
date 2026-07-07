<?php

declare(strict_types=1);

namespace Botdigit\CryptoGateway\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * CryptoGatewayConfig model — admin-managed per-coin configuration.
 *
 * @property int         $id
 * @property string      $coin
 * @property string      $display_name
 * @property bool        $is_enabled
 * @property int         $min_confirmations
 * @property string      $min_amount
 * @property string|null $max_amount
 * @property string      $fee_percentage
 * @property array|null  $settings
 */
class CryptoGatewayConfig extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'is_enabled'        => 'boolean',
            'min_confirmations' => 'integer',
            'min_amount'        => 'decimal:18',
            'max_amount'        => 'decimal:18',
            'fee_percentage'    => 'decimal:2',
            'settings'          => 'array',
        ];
    }

    public function getTable(): string
    {
        $prefix = config('cryptogateway.database.table_prefix', 'crypto_');
        return $prefix . 'gateway_configs';
    }

    public function getConnectionName(): ?string
    {
        return config('cryptogateway.database.connection');
    }

    // ── Scopes ──────────────────────────────────────────────────────────

    public function scopeEnabled($query)
    {
        return $query->where('is_enabled', true);
    }

    public function scopeForCoin($query, string $coin)
    {
        return $query->where('coin', strtoupper($coin));
    }

    // ── Helpers ─────────────────────────────────────────────────────────

    /**
     * Get a specific setting value from the JSON settings column.
     */
    public function getSetting(string $key, mixed $default = null): mixed
    {
        return data_get($this->settings, $key, $default);
    }

    /**
     * Check if an amount is within the allowed range.
     */
    public function isAmountAllowed(string $amount): bool
    {
        if (bccomp($amount, (string) $this->min_amount, 18) < 0) {
            return false;
        }

        if ($this->max_amount !== null && bccomp($amount, (string) $this->max_amount, 18) > 0) {
            return false;
        }

        return true;
    }

    /**
     * Calculate the admin fee for a given amount.
     */
    public function calculateFee(string $amount): string
    {
        if (bccomp((string) $this->fee_percentage, '0', 2) === 0) {
            return '0';
        }

        return bcdiv(bcmul($amount, (string) $this->fee_percentage, 18), '100', 18);
    }
}

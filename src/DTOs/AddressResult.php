<?php

declare(strict_types=1);

namespace Botdigit\CryptoGateway\DTOs;

/**
 * Immutable value object representing a generated address.
 */
final class AddressResult
{
    public function __construct(
        public readonly string  $coin,
        public readonly string  $address,
        public readonly ?string $privateKey = null,
        public readonly ?string $publicKey = null,
        public readonly ?string $label = null,
        public readonly ?string $derivationPath = null,
        public readonly array   $raw = [],
    ) {}

    /**
     * Get the address with the private key stripped (safe to expose).
     */
    public function withoutPrivateKey(): self
    {
        return new self(
            coin: $this->coin,
            address: $this->address,
            privateKey: null,
            publicKey: $this->publicKey,
            label: $this->label,
            derivationPath: $this->derivationPath,
            raw: [],
        );
    }

    /**
     * Convert to array. NEVER includes private key by default.
     */
    public function toArray(): array
    {
        return [
            'coin'            => $this->coin,
            'address'         => $this->address,
            'public_key'      => $this->publicKey,
            'label'           => $this->label,
            'derivation_path' => $this->derivationPath,
        ];
    }

    /**
     * Convert to array WITH the private key (use with extreme caution).
     */
    public function toSecureArray(): array
    {
        return array_merge($this->toArray(), [
            'private_key' => $this->privateKey,
        ]);
    }
}

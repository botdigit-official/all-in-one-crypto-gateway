<?php

declare(strict_types=1);

namespace Botdigit\CryptoGateway\Contracts;

/**
 * Drivers that support HD wallet generation implement this interface.
 */
interface HasWallet
{
    /**
     * Generate a new HD wallet (mnemonic + master keys).
     *
     * @return array{mnemonic: string, seed: string, xpub: string, xpriv: string}
     */
    public function generateWallet(): array;

    /**
     * Derive an address from an HD wallet at a given index.
     *
     * @param  string  $xpub  Extended public key
     * @param  int     $index Derivation index
     */
    public function deriveAddress(string $xpub, int $index): string;
}

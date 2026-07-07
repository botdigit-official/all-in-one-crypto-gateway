<?php

declare(strict_types=1);

namespace Botdigit\CryptoGateway\Security;

use Illuminate\Contracts\Encryption\Encrypter;

/**
 * Handles encryption/decryption of sensitive key material.
 *
 * Uses Laravel's built-in encryption (AES-256-CBC by default).
 * Keys are encrypted before database storage and decrypted only when needed.
 */
class KeyEncryptor
{
    protected Encrypter $encrypter;

    public function __construct(Encrypter $encrypter)
    {
        $this->encrypter = $encrypter;
    }

    /**
     * Encrypt a private key for secure storage.
     */
    public function encrypt(string $key): string
    {
        if (! config('cryptogateway.security.encrypt_keys', true)) {
            return $key;
        }

        return $this->encrypter->encryptString($key);
    }

    /**
     * Decrypt a stored private key.
     */
    public function decrypt(string $encryptedKey): string
    {
        if (! config('cryptogateway.security.encrypt_keys', true)) {
            return $encryptedKey;
        }

        return $this->encrypter->decryptString($encryptedKey);
    }

    /**
     * Securely clear a string from memory.
     *
     * Note: PHP doesn't guarantee memory clearing, but this helps
     * by overwriting the variable's value.
     */
    public static function clearFromMemory(string &$sensitive): void
    {
        $length = strlen($sensitive);
        $sensitive = str_repeat("\0", $length);
        $sensitive = '';
    }

    /**
     * Execute a callback with a decrypted key, then clear it from memory.
     *
     * @template T
     * @param  string   $encryptedKey
     * @param  callable(string): T  $callback
     * @return T
     */
    public function withDecryptedKey(string $encryptedKey, callable $callback): mixed
    {
        $key = $this->decrypt($encryptedKey);

        try {
            return $callback($key);
        } finally {
            self::clearFromMemory($key);
        }
    }
}

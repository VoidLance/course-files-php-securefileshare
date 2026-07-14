<?php

declare(strict_types=1);

namespace App\Core;

final class FileCipher
{
    public static function encrypt(string $plainBytes): array
    {
        // Derive a fixed-length binary key from app config secret.
        $key = hash('sha256', (string) app_config('security.app_key'), true);
        // AES-CBC needs a fresh 16-byte IV per encryption operation.
        $iv = random_bytes(16);

        $cipherText = openssl_encrypt(
            $plainBytes,
            'AES-256-CBC',
            $key,
            OPENSSL_RAW_DATA,
            $iv
        );

        if ($cipherText === false) {
            throw new \RuntimeException('Encryption failed. The bytes refused to cooperate.');
        }

        // Save IV + checksum with ciphertext so decryption and integrity checks can work.
        return [
            'cipher' => $cipherText,
            'iv' => base64_encode($iv),
            'checksum' => hash('sha256', $plainBytes),
        ];
    }

    public static function decrypt(string $cipherBytes, string $base64Iv): string
    {
        $key = hash('sha256', (string) app_config('security.app_key'), true);
        // IV is stored as base64 in DB for transport safety.
        $iv = base64_decode($base64Iv, true);

        if ($iv === false) {
            throw new \RuntimeException('Invalid IV. Data says nope.');
        }

        $plain = openssl_decrypt(
            $cipherBytes,
            'AES-256-CBC',
            $key,
            OPENSSL_RAW_DATA,
            $iv
        );

        if ($plain === false) {
            throw new \RuntimeException('Decryption failed. Wrong key or corrupted data.');
        }

        return $plain;
    }
}

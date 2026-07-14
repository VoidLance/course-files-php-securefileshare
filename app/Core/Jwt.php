<?php

declare(strict_types=1);

namespace App\Core;

final class Jwt
{
    public static function encode(array $payload, int $ttlSeconds = 3600): string
    {
        $header = ['alg' => 'HS256', 'typ' => 'JWT'];
        $now = time();

        $payload['iat'] = $now;
        $payload['exp'] = $now + $ttlSeconds;

        $encodedHeader = self::base64UrlEncode(json_encode($header, JSON_THROW_ON_ERROR));
        $encodedPayload = self::base64UrlEncode(json_encode($payload, JSON_THROW_ON_ERROR));

        $signature = hash_hmac(
            'sha256',
            $encodedHeader . '.' . $encodedPayload,
            (string) app_config('security.jwt_secret'),
            true
        );

        return $encodedHeader . '.' . $encodedPayload . '.' . self::base64UrlEncode($signature);
    }

    public static function decode(string $token): ?array
    {
        $parts = explode('.', $token);
        if (count($parts) !== 3) {
            return null;
        }

        [$encodedHeader, $encodedPayload, $encodedSignature] = $parts;

        $expectedSignature = hash_hmac(
            'sha256',
            $encodedHeader . '.' . $encodedPayload,
            (string) app_config('security.jwt_secret'),
            true
        );

        $providedSignature = self::base64UrlDecode($encodedSignature);

        if (!hash_equals($expectedSignature, $providedSignature)) {
            return null;
        }

        $payloadJson = self::base64UrlDecode($encodedPayload);
        $payload = json_decode($payloadJson, true);

        if (!is_array($payload) || (($payload['exp'] ?? 0) < time())) {
            return null;
        }

        return $payload;
    }

    private static function base64UrlEncode(string $value): string
    {
        return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
    }

    private static function base64UrlDecode(string $value): string
    {
        $padding = 4 - (strlen($value) % 4);
        if ($padding < 4) {
            $value .= str_repeat('=', $padding);
        }

        $decoded = base64_decode(strtr($value, '-_', '+/'), true);

        return $decoded === false ? '' : $decoded;
    }
}

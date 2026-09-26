<?php

declare(strict_types=1);

namespace App\Core\WebAuthn;

/**
 * COSE-ключ (RFC 9053) из регистрации ключа доступа → PEM для openssl_verify.
 * Поддержаны алгоритмы, которые браузер предлагает всегда: ES256 (EC P-256,
 * alg -7) и RS256 (RSA PKCS#1 v1.5, alg -257).
 */
final class CoseKey
{
    public const ALG_ES256 = -7;
    public const ALG_RS256 = -257;

    /** SubjectPublicKeyInfo для P-256 без самой точки: id-ecPublicKey + prime256v1. */
    private const EC_P256_PREFIX = '3059301306072a8648ce3d020106082a8648ce3d030107034200';

    /** @param array<int|string, mixed> $cose */
    public static function toPem(array $cose): string
    {
        $kty = $cose[1] ?? null;
        $alg = $cose[3] ?? null;

        if ($kty === 2 && $alg === self::ALG_ES256) {
            $x = $cose[-2] ?? null;
            $y = $cose[-3] ?? null;
            if (($cose[-1] ?? null) !== 1 || !is_string($x) || !is_string($y) || strlen($x) !== 32 || strlen($y) !== 32) {
                throw new \InvalidArgumentException('COSE: ключ EC не на кривой P-256');
            }
            $der = (string) hex2bin(self::EC_P256_PREFIX) . "\x04" . $x . $y;
        } elseif ($kty === 3 && $alg === self::ALG_RS256) {
            $n = $cose[-1] ?? null;
            $e = $cose[-2] ?? null;
            if (!is_string($n) || !is_string($e) || strlen($n) < 256 || $e === '') {
                throw new \InvalidArgumentException('COSE: ключ RSA короче 2048 бит');
            }
            $rsa = self::seq(self::int($n) . self::int($e));
            $algorithm = self::seq("\x06\x09\x2a\x86\x48\x86\xf7\x0d\x01\x01\x01" . "\x05\x00");
            $der = self::seq($algorithm . "\x03" . self::len(strlen($rsa) + 1) . "\x00" . $rsa);
        } else {
            throw new \InvalidArgumentException('COSE: алгоритм не поддержан');
        }

        $pem = "-----BEGIN PUBLIC KEY-----\n" . chunk_split(base64_encode($der), 64, "\n") . "-----END PUBLIC KEY-----\n";
        if (openssl_pkey_get_public($pem) === false) {
            throw new \InvalidArgumentException('COSE: OpenSSL не принял ключ');
        }

        return $pem;
    }

    private static function seq(string $body): string
    {
        return "\x30" . self::len(strlen($body)) . $body;
    }

    /** INTEGER без знака: ведущие нули срезаются, при старшем бите — один ноль. */
    private static function int(string $bytes): string
    {
        $bytes = ltrim($bytes, "\x00");
        if ($bytes === '' || (ord($bytes[0]) & 0x80) !== 0) {
            $bytes = "\x00" . $bytes;
        }

        return "\x02" . self::len(strlen($bytes)) . $bytes;
    }

    private static function len(int $length): string
    {
        if ($length < 0x80) {
            return chr($length);
        }
        $out = '';
        while ($length > 0) {
            $out = chr($length & 0xFF) . $out;
            $length >>= 8;
        }

        return chr(0x80 | strlen($out)) . $out;
    }
}

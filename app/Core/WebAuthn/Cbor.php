<?php

declare(strict_types=1);

namespace App\Core\WebAuthn;

/**
 * Минимальный декодер CBOR (RFC 8949) — ровно то, что приходит в WebAuthn:
 * attestationObject и COSE-ключ. Целые, байтовые и текстовые строки,
 * массивы, карты, false/true/null. Байтовые и текстовые строки обе
 * возвращаются строкой PHP: что из них что, известно по месту в структуре.
 * Числа с плавающей точкой, теги и неопределённая длина в этих структурах
 * не встречаются и отклоняются.
 */
final class Cbor
{
    private const MAX_DEPTH = 16;

    /** Декодирует один элемент с позиции $offset и сдвигает её за элемент. */
    public static function decode(string $data, int &$offset = 0, int $depth = 0): mixed
    {
        if ($depth > self::MAX_DEPTH) {
            throw new \InvalidArgumentException('CBOR: слишком глубокая вложенность');
        }
        $initial = self::byte($data, $offset);
        $major = $initial >> 5;
        $info = $initial & 0x1F;

        if ($major === 7) {
            return match ($info) {
                20 => false,
                21 => true,
                22 => null,
                default => throw new \InvalidArgumentException('CBOR: неподдержанное простое значение ' . $info),
            };
        }

        $length = self::length($data, $offset, $info);

        switch ($major) {
            case 0:
                return $length;
            case 1:
                return -1 - $length;
            case 2:
            case 3:
                if ($offset + $length > strlen($data)) {
                    throw new \InvalidArgumentException('CBOR: строка за пределами данных');
                }
                $value = substr($data, $offset, $length);
                $offset += $length;

                return $value;
            case 4:
                $list = [];
                for ($i = 0; $i < $length; $i++) {
                    $list[] = self::decode($data, $offset, $depth + 1);
                }

                return $list;
            case 5:
                $map = [];
                for ($i = 0; $i < $length; $i++) {
                    $key = self::decode($data, $offset, $depth + 1);
                    if (!is_int($key) && !is_string($key)) {
                        throw new \InvalidArgumentException('CBOR: ключ карты не число и не строка');
                    }
                    $map[$key] = self::decode($data, $offset, $depth + 1);
                }

                return $map;
            default:
                throw new \InvalidArgumentException('CBOR: неподдержанный тип ' . $major);
        }
    }

    private static function length(string $data, int &$offset, int $info): int
    {
        if ($info < 24) {
            return $info;
        }
        $size = match ($info) {
            24 => 1,
            25 => 2,
            26 => 4,
            27 => 8,
            default => throw new \InvalidArgumentException('CBOR: неопределённая длина не поддержана'),
        };
        if ($offset + $size > strlen($data)) {
            throw new \InvalidArgumentException('CBOR: обрыв данных');
        }
        $value = 0;
        for ($i = 0; $i < $size; $i++) {
            $value = ($value << 8) | ord($data[$offset + $i]);
        }
        $offset += $size;
        if ($value < 0) {
            throw new \InvalidArgumentException('CBOR: число вне диапазона');
        }

        return $value;
    }

    private static function byte(string $data, int &$offset): int
    {
        if ($offset >= strlen($data)) {
            throw new \InvalidArgumentException('CBOR: обрыв данных');
        }

        return ord($data[$offset++]);
    }
}

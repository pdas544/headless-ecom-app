<?php

declare(strict_types=1);

namespace App\Core;

/**
 * RFC 9562 UUIDv7 generator.
 *
 * Layout: 48-bit Unix timestamp (ms) | version 7 | 12-bit sequence | variant 10 | 62 random bits.
 * Within the same millisecond the 12-bit sequence is incremented, so generated UUIDs
 * are monotonically increasing (lexicographically sortable) per process.
 */
final class Uuid
{
    private static int $lastTimestampMs = 0;

    private static int $sequence = 0;

    private const VERSION = 0x7000;

    private const SEQUENCE_MASK = 0x0FFF;

    public static function v7(?int $timestampMs = null): string
    {
        $ms = $timestampMs ?? (int) floor(microtime(true) * 1000);

        if ($ms <= self::$lastTimestampMs) {
            $ms = self::$lastTimestampMs;
            self::$sequence = (self::$sequence + 1) & self::SEQUENCE_MASK;

            if (self::$sequence === 0) {
                ++$ms;
            }
        } else {
            self::$sequence = random_int(0, self::SEQUENCE_MASK);
        }

        self::$lastTimestampMs = $ms;

        $time = pack('nN', ($ms >> 32) & 0xFFFF, $ms & 0xFFFFFFFF);
        $versionAndSequence = pack('n', self::VERSION | self::$sequence);

        $random = random_bytes(8);
        $random[0] = chr((ord($random[0]) & 0x3F) | 0x80);

        $hex = bin2hex($time . $versionAndSequence . $random);

        return sprintf(
            '%s-%s-%s-%s-%s',
            substr($hex, 0, 8),
            substr($hex, 8, 4),
            substr($hex, 12, 4),
            substr($hex, 16, 4),
            substr($hex, 20, 12),
        );
    }

    public static function isValid(string $uuid): bool
    {
        return preg_match(
            '/^[0-9a-f]{8}-[0-9a-f]{4}-7[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/',
            $uuid,
        ) === 1;
    }
}

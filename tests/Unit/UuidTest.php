<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Core\Uuid;
use PHPUnit\Framework\TestCase;

final class UuidTest extends TestCase
{
    private const FUTURE_MS = 4_000_000_000_000;

    public function testGeneratesRfc9562Version7WithVariant(): void
    {
        $uuid = Uuid::v7();

        self::assertTrue(Uuid::isValid($uuid));
        self::assertSame('7', $uuid[14]);
        self::assertContains($uuid[19], ['8', '9', 'a', 'b']);
    }

    public function testEncodesUnixMillisecondsInFirst48Bits(): void
    {
        $uuid = Uuid::v7(self::FUTURE_MS);
        $hex = str_replace('-', '', $uuid);

        self::assertSame(self::FUTURE_MS, (int) hexdec(substr($hex, 0, 12)));
    }

    public function testIsMonotonicWithinSameMillisecond(): void
    {
        $previous = Uuid::v7(self::FUTURE_MS);

        for ($i = 0; $i < 5000; $i++) {
            $current = Uuid::v7(self::FUTURE_MS);

            self::assertSame(1, strcmp($current, $previous) > 0 ? 1 : -1, 'UUIDv7 must increase');
            $previous = $current;
        }
    }

    public function testGeneratedUuidsAreUnique(): void
    {
        $uuids = [];

        for ($i = 0; $i < 1000; $i++) {
            $uuids[] = Uuid::v7();
        }

        self::assertCount(1000, array_unique($uuids));
    }

    public function testIsValidRejectsNonV7AndMalformedValues(): void
    {
        self::assertFalse(Uuid::isValid('550e8400-e29b-41d4-a716-446655440000'));
        self::assertFalse(Uuid::isValid('not-a-uuid'));
        self::assertFalse(Uuid::isValid('550e8400e29b71d4a716446655440000'));
    }
}

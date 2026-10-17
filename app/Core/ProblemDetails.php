<?php

declare(strict_types=1);

namespace App\Core;

use JsonSerializable;

/**
 * RFC 9457 Problem Details (obsoletes RFC 7807).
 *
 * @see https://www.rfc-editor.org/rfc/rfc9457
 */
final class ProblemDetails implements JsonSerializable
{
    public const CONTENT_TYPE = 'application/problem+json';

    private const TYPE_BASE = 'https://headless-ecom.dev/problems/';

    private const TITLES = [
        400 => 'Bad Request',
        401 => 'Unauthorized',
        403 => 'Forbidden',
        404 => 'Not Found',
        405 => 'Method Not Allowed',
        409 => 'Conflict',
        422 => 'Unprocessable Entity',
        429 => 'Too Many Requests',
        500 => 'Internal Server Error',
        503 => 'Service Unavailable',
    ];

    /**
     * @param array<string, mixed> $extensions
     */
    public function __construct(
        public readonly int $status,
        public readonly string $type,
        public readonly string $title,
        public readonly string $detail,
        public readonly ?string $instance = null,
        public readonly array $extensions = [],
    ) {
    }

    public static function titleFor(int $status): string
    {
        return self::TITLES[$status] ?? 'Error';
    }

    public static function typeFor(int $status): string
    {
        return self::TYPE_BASE . strtolower(str_replace(' ', '-', self::titleFor($status)));
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $problem = [
            'type' => $this->type,
            'title' => $this->title,
            'status' => $this->status,
            'detail' => $this->detail,
        ];

        if ($this->instance !== null) {
            $problem['instance'] = $this->instance;
        }

        return [...$problem, ...$this->extensions];
    }

    /**
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}

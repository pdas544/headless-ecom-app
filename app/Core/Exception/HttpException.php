<?php

declare(strict_types=1);

namespace App\Core\Exception;

use RuntimeException;

final class HttpException extends RuntimeException
{
    public function __construct(public readonly int $status, string $detail)
    {
        parent::__construct($detail, $status);
    }

    public static function badRequest(string $detail = 'The request is malformed.'): self
    {
        return new self(400, $detail);
    }

    public static function unauthorized(string $detail = 'Authentication is required.'): self
    {
        return new self(401, $detail);
    }

    public static function forbidden(string $detail = 'You are not allowed to perform this action.'): self
    {
        return new self(403, $detail);
    }

    public static function notFound(string $detail = 'The requested resource was not found.'): self
    {
        return new self(404, $detail);
    }

    public static function methodNotAllowed(string $detail = 'The HTTP method is not allowed for this resource.'): self
    {
        return new self(405, $detail);
    }

    public static function conflict(string $detail = 'The request conflicts with the current state.'): self
    {
        return new self(409, $detail);
    }

    public static function unprocessableEntity(string $detail = 'The request could not be processed.'): self
    {
        return new self(422, $detail);
    }

    public static function tooManyRequests(string $detail = 'Too many requests.'): self
    {
        return new self(429, $detail);
    }
}

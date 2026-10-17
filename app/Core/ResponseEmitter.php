<?php

declare(strict_types=1);

namespace App\Core;

use Psr\Http\Message\ResponseInterface;

final class ResponseEmitter
{
    private const CHUNK_SIZE = 8192;

    public function emit(ResponseInterface $response): void
    {
        if (! headers_sent()) {
            http_response_code($response->getStatusCode());

            foreach ($response->getHeaders() as $name => $values) {
                $replace = true;

                foreach ($values as $value) {
                    header($name . ': ' . $value, $replace);
                    $replace = false;
                }
            }
        }

        $body = $response->getBody();

        if ($body->isSeekable()) {
            $body->rewind();
        }

        while (! $body->eof()) {
            echo $body->read(self::CHUNK_SIZE);
        }
    }
}

<?php

namespace App\Exceptions;

use RuntimeException;
use Throwable;

class UpstreamServiceException extends RuntimeException
{
    public function __construct(
        public readonly string $service,
        string $reason,
        ?int $status = null,
        ?Throwable $previous = null,
    ) {
        $message = "{$service} request failed: {$reason}";

        if ($status !== null) {
            $message .= " (HTTP {$status})";
        }

        parent::__construct($message, $status ?? 0, $previous);
    }
}

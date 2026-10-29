<?php

namespace App\Exceptions;

use RuntimeException;
use Throwable;

class UpstreamServiceException extends RuntimeException
{
    public function __construct(
        public readonly string $serviceName,
        public readonly string $clientMessage,
        public readonly string $technicalMessage = '',
        ?Throwable $previous = null,
    ) {
        parent::__construct(
            $technicalMessage ?: $clientMessage,
            0,
            $previous,
        );
    }
}

<?php

declare(strict_types=1);

namespace DeepSeek\Wan\Exceptions;

class DeepSeekException extends \RuntimeException
{
    public function __construct(
        string $message = '',
        int $code = 0,
        ?\Throwable $previous = null,
        private readonly ?array $responseData = null,
    ) {
        parent::__construct($message, $code, $previous);
    }

    public function getResponseData(): ?array
    {
        return $this->responseData;
    }
}

<?php

declare(strict_types=1);

namespace DeepSeek\Agent\Exceptions;

class ToolTimeoutException extends DeepSeekException
{
    public function __construct(
        string $message = '',
        int $code = 0,
        ?\Throwable $previous = null,
        private readonly string $toolName = '',
    ) {
        parent::__construct($message, $code, $previous);
    }

    public function getToolName(): string
    {
        return $this->toolName;
    }
}

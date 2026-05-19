<?php

namespace DeepSeek\Wan\Exceptions;

class ToolExecutionException extends DeepSeekException
{
    /** @var array<int, \Throwable> */
    private readonly array $attemptErrors;

    public function __construct(
        string $message = '',
        int $code = 0,
        ?\Throwable $previous = null,
        array $attemptErrors = [],
        private readonly string $toolName = '',
    ) {
        parent::__construct($message, $code, $previous);
        $this->attemptErrors = $attemptErrors;
    }

    /** @return array<int, \Throwable> */
    public function getAttemptErrors(): array
    {
        return $this->attemptErrors;
    }

    public function getToolName(): string
    {
        return $this->toolName;
    }

    public static function toolNotFound(string $toolName): self
    {
        return new self("Tool not found: '{$toolName}'", toolName: $toolName);
    }
}

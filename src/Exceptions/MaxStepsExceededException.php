<?php

namespace DeepSeek\Wan\Exceptions;

class MaxStepsExceededException extends DeepSeekException
{
    /** @param array<int, array{step: int, finishReason: string, usage: array}> $steps */
    public function __construct(
        string $message = '',
        int $code = 0,
        ?\Throwable $previous = null,
        private readonly array $steps = [],
        private readonly int $maxSteps = 0,
    ) {
        parent::__construct($message, $code, $previous);
    }

    /** @return array<int, array{step: int, finishReason: string, usage: array}> */
    public function getSteps(): array
    {
        return $this->steps;
    }

    public function getMaxSteps(): int
    {
        return $this->maxSteps;
    }
}

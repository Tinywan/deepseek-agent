<?php

declare(strict_types=1);

namespace DeepSeek\Agent;

class HookError
{
    public function __construct(
        public readonly int $step,
        public readonly string $message,
        public readonly ?\Throwable $exception = null,
    ) {}
}

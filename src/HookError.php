<?php

namespace DeepSeek\Wan;

class HookError
{
    public function __construct(
        public readonly int $step,
        public readonly string $message,
        public readonly ?\Throwable $exception = null,
    ) {}
}

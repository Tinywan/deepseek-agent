<?php

declare(strict_types=1);

namespace DeepSeek\Wan;

readonly class ToolCallEvent
{
    public function __construct(
        public string $callId,
        public string $name,
        public string $arguments,
    ) {}
}

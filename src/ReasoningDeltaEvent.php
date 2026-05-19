<?php

declare(strict_types=1);

namespace DeepSeek\Wan;

readonly class ReasoningDeltaEvent
{
    public function __construct(
        public string $delta,
    ) {}
}

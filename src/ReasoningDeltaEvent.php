<?php

declare(strict_types=1);

namespace DeepSeek\Agent;

readonly class ReasoningDeltaEvent
{
    public function __construct(
        public string $delta,
    ) {}
}

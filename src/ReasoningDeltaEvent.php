<?php

namespace DeepSeek\Wan;

readonly class ReasoningDeltaEvent
{
    public function __construct(
        public string $delta,
    ) {}
}

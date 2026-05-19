<?php

namespace DeepSeek\Wan;

readonly class StepEvent
{
    public function __construct(
        public int $stepNumber,
        public string $finishReason,
        public ?array $usage = null,
    ) {}
}

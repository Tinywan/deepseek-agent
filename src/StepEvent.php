<?php

declare(strict_types=1);

namespace DeepSeek\Agent;

readonly class StepEvent
{
    public function __construct(
        public int $stepNumber,
        public string $finishReason,
        public ?array $usage = null,
    ) {}
}

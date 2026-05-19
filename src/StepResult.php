<?php

declare(strict_types=1);

namespace DeepSeek\Agent;

readonly class StepResult
{
    /** @param array{prompt_tokens?: int, completion_tokens?: int, total_tokens?: int}|null $usage */
    public function __construct(
        public int     $step,
        public string  $type,
        public ?array  $usage = null,
        public ?string $finishReason = null,
    ) {}
}

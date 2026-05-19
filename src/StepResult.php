<?php

namespace DeepSeek\Wan;

class StepResult
{
    /** @param array{prompt_tokens?: int, completion_tokens?: int, total_tokens?: int}|null $usage */
    public function __construct(
        public readonly int $step,
        public readonly string $type,
        public readonly ?array $usage = null,
        public readonly ?string $finishReason = null,
    ) {}
}

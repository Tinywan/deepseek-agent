<?php

namespace DeepSeek\Wan;

class GenerateTextResult
{
    /** @param array<int, array{role: string, content: string}> $messages */
    public function __construct(
        public readonly string $text,
        public readonly string $finishReason,
        public readonly ?array $usage = null,
        public readonly array $steps = [],
        public readonly array $messages = [],
    ) {}
}

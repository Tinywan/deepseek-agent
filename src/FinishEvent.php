<?php

namespace DeepSeek\Wan;

readonly class FinishEvent
{
    public function __construct(
        public string $finishReason,
        public ?array $usage = null,
        public ?string $text = null,
    ) {}
}

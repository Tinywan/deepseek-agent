<?php

declare(strict_types=1);

namespace DeepSeek\Wan;

readonly class TextDeltaEvent
{
    public function __construct(
        public string $delta,
    ) {}
}

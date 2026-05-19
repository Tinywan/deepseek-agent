<?php

namespace DeepSeek\Wan;

readonly class TextDeltaEvent
{
    public function __construct(
        public string $delta,
    ) {}
}

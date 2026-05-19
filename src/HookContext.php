<?php

namespace DeepSeek\Wan;

class HookContext
{
    /** @param array<int, array{role: string, content: string}> $messages */
    public function __construct(
        public readonly int $step,
        public readonly array $messages,
        public readonly Config $config,
    ) {}
}

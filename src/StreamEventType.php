<?php

declare(strict_types=1);

namespace DeepSeek\Wan;

enum StreamEventType: string
{
    case TextDelta = 'text-delta';
    case ReasoningDelta = 'reasoning-delta';
    case ToolCall = 'tool-call';
    case Step = 'step';
    case Finish = 'finish';
}

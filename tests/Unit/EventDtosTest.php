<?php

declare(strict_types=1);

use DeepSeek\Agent\TextDeltaEvent;
use DeepSeek\Agent\ReasoningDeltaEvent;
use DeepSeek\Agent\ToolCallEvent;
use DeepSeek\Agent\StepEvent;
use DeepSeek\Agent\FinishEvent;

describe('TextDeltaEvent', function () {
    test('stores delta text', function () {
        $e = new TextDeltaEvent('hello');
        expect($e->delta)->toBe('hello');
    });

    test('handles empty delta', function () {
        $e = new TextDeltaEvent('');
        expect($e->delta)->toBe('');
    });

    test('handles multi-byte UTF-8', function () {
        $e = new TextDeltaEvent('你好世界');
        expect($e->delta)->toBe('你好世界');
    });
});

describe('ReasoningDeltaEvent', function () {
    test('stores reasoning delta', function () {
        $e = new ReasoningDeltaEvent('thinking...');
        expect($e->delta)->toBe('thinking...');
    });
});

describe('ToolCallEvent', function () {
    test('stores callId, name, arguments', function () {
        $e = new ToolCallEvent('call_abc', 'get_weather', '{"city":"Paris"}');
        expect($e->callId)->toBe('call_abc');
        expect($e->name)->toBe('get_weather');
        expect($e->arguments)->toBe('{"city":"Paris"}');
    });

    test('handles empty arguments', function () {
        $e = new ToolCallEvent('id1', 'myFunc', '');
        expect($e->arguments)->toBe('');
    });

    test('arguments is raw JSON string', function () {
        $e = new ToolCallEvent('id', 'fn', '{"x":1,"y":2}');
        $decoded = json_decode($e->arguments, true);
        expect($decoded['x'])->toBe(1);
        expect($decoded['y'])->toBe(2);
    });
});

describe('StepEvent', function () {
    test('stores stepNumber and finishReason', function () {
        $e = new StepEvent(3, 'tool_calls', ['total_tokens' => 150]);
        expect($e->stepNumber)->toBe(3);
        expect($e->finishReason)->toBe('tool_calls');
        expect($e->usage['total_tokens'])->toBe(150);
    });

    test('usage defaults to null', function () {
        $e = new StepEvent(1, 'stop', null);
        expect($e->usage)->toBeNull();
    });

    test('handles step 0', function () {
        $e = new StepEvent(0, 'stop', null);
        expect($e->stepNumber)->toBe(0);
    });
});

describe('FinishEvent', function () {
    test('stores finishReason, usage, text', function () {
        $e = new FinishEvent('stop', ['total_tokens' => 200], 'generated text');
        expect($e->finishReason)->toBe('stop');
        expect($e->usage['total_tokens'])->toBe(200);
        expect($e->text)->toBe('generated text');
    });

    test('handles tool_calls finish reason', function () {
        $e = new FinishEvent('tool_calls', null, '');
        expect($e->finishReason)->toBe('tool_calls');
        expect($e->usage)->toBeNull();
    });

    test('handles length finish reason', function () {
        $e = new FinishEvent('length', ['completion_tokens' => 50], 'truncated...');
        expect($e->finishReason)->toBe('length');
    });
});

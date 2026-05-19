<?php

declare(strict_types=1);

use DeepSeek\Agent\StreamEventType;

describe('StreamEventType', function () {
    test('has exactly 5 cases', function () {
        expect(StreamEventType::cases())->toHaveCount(5);
    });

    test('TextDelta case value', function () {
        expect(StreamEventType::TextDelta->value)->toBe('text-delta');
    });

    test('ReasoningDelta case value', function () {
        expect(StreamEventType::ReasoningDelta->value)->toBe('reasoning-delta');
    });

    test('ToolCall case value', function () {
        expect(StreamEventType::ToolCall->value)->toBe('tool-call');
    });

    test('Step case value', function () {
        expect(StreamEventType::Step->value)->toBe('step');
    });

    test('Finish case value', function () {
        expect(StreamEventType::Finish->value)->toBe('finish');
    });

    test('is backed enum', function () {
        expect(StreamEventType::class)->toImplement(\BackedEnum::class);
    });
});

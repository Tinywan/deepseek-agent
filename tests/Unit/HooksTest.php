<?php

declare(strict_types=1);

use DeepSeek\Agent\Config;
use DeepSeek\Agent\Hooks;
use DeepSeek\Agent\HookContext;
use DeepSeek\Agent\StepResult;
use DeepSeek\Agent\HookError;

describe('Hooks', function () {
    test('starts with no hooks registered', function () {
        $hooks = new Hooks();
        // Just verifying no exceptions on runs with no hooks
        $result = $hooks->runBeforeStep(1, [['role' => 'user', 'content' => 'hi']], new Config(['apiKey' => 'x']));
        expect($result['messages'])->toBeArray();
        expect($result['config'])->toBe([]);
    });

    test('registers and runs beforeStep hook', function () {
        $hooks = new Hooks();
        $called = false;

        $hooks->beforeStep(function (HookContext $ctx) use (&$called) {
            $called = true;
            return ['config' => ['temperature' => 0.3]];
        });

        $result = $hooks->runBeforeStep(1, [], new Config(['apiKey' => 'x']));
        expect($called)->toBeTrue();
        expect($result['config'])->toBe(['temperature' => 0.3]);
    });

    test('beforeStep hook receives correct context', function () {
        $hooks = new Hooks();
        $config = new Config(['apiKey' => 'sk-test']);
        $messages = [['role' => 'user', 'content' => 'hello']];

        $hooks->beforeStep(function (HookContext $ctx) use ($config, $messages) {
            expect($ctx->step)->toBe(2);
            expect($ctx->messages)->toBe($messages);
            expect($ctx->config)->toBe($config);
            return [];
        });

        $hooks->runBeforeStep(2, $messages, $config);
    });

    test('beforeStep returning config null does not override', function () {
        $hooks = new Hooks();
        $hooks->beforeStep(fn(HookContext $ctx) => ['config' => null]);

        $result = $hooks->runBeforeStep(1, [], new Config(['apiKey' => 'x']));
        expect($result['config'])->toBe([]);
    });

    test('registers and runs afterStep hook', function () {
        $hooks = new Hooks();
        $called = false;

        $hooks->afterStep(function (StepResult $r) use (&$called) {
            $called = true;
        });

        $hooks->runAfterStep(new StepResult(step: 1, type: 'text', usage: null, finishReason: 'stop'));
        expect($called)->toBeTrue();
    });

    test('registers and runs onError hook', function () {
        $hooks = new Hooks();
        $called = false;

        $hooks->onError(function (HookError $e) use (&$called) {
            $called = true;
            return 'handled';
        });

        $result = $hooks->runOnError(new HookError(step: 1, message: 'test error', exception: new RuntimeException('bang')));
        expect($called)->toBeTrue();
        expect($result)->toBe('handled');
    });

    test('onError returns null when no hook registered', function () {
        $hooks = new Hooks();
        $result = $hooks->runOnError(new HookError(step: 1, message: 'err', exception: new RuntimeException('bang')));
        expect($result)->toBeNull();
    });

    test('multiple hooks of same type run in order', function () {
        $hooks = new Hooks();
        $order = [];

        $hooks->beforeStep(function (HookContext $ctx) use (&$order) {
            $order[] = 'first';
            return [];
        });

        $hooks->beforeStep(function (HookContext $ctx) use (&$order) {
            $order[] = 'second';
            return ['config' => ['temperature' => 0.9]];
        });

        $result = $hooks->runBeforeStep(1, [], new Config(['apiKey' => 'x']));
        expect($order)->toBe(['first', 'second']);
        // last hook's config wins
        expect($result['config'])->toBe(['temperature' => 0.9]);
    });
});

describe('HookContext', function () {
    test('stores step, messages, config', function () {
        $config = new Config(['apiKey' => 'sk-test']);
        $messages = [['role' => 'user', 'content' => 'hi']];
        $ctx = new HookContext(1, $messages, $config);

        expect($ctx->step)->toBe(1);
        expect($ctx->messages)->toBe($messages);
        expect($ctx->config)->toBe($config);
    });
});

describe('StepResult', function () {
    test('stores all fields', function () {
        $sr = new StepResult(
            step: 2,
            type: 'tool_calls',
            usage: ['total_tokens' => 100],
            finishReason: 'tool_calls',
        );

        expect($sr->step)->toBe(2);
        expect($sr->type)->toBe('tool_calls');
        expect($sr->usage['total_tokens'])->toBe(100);
        expect($sr->finishReason)->toBe('tool_calls');
    });

    test('usage is nullable', function () {
        $sr = new StepResult(step: 1, type: 'text', usage: null, finishReason: null);
        expect($sr->usage)->toBeNull();
        expect($sr->finishReason)->toBeNull();
    });
});

describe('HookError', function () {
    test('stores step, message, exception', function () {
        $ex = new RuntimeException('boom');
        $he = new HookError(step: 3, message: 'error occurred', exception: $ex);

        expect($he->step)->toBe(3);
        expect($he->message)->toBe('error occurred');
        expect($he->exception)->toBe($ex);
    });
});

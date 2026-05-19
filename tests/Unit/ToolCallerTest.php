<?php

declare(strict_types=1);

use DeepSeek\Agent\Tool;
use DeepSeek\Agent\ToolCaller;
use DeepSeek\Agent\Exceptions\ToolExecutionException;
use DeepSeek\Agent\Exceptions\ToolTimeoutException;

describe('ToolCaller', function () {
    test('executes a single tool call', function () {
        $tool = new Tool(
            name: 'echo',
            description: 'echoes input',
            schema: ['type' => 'object'],
            execute: fn(array $args): string => json_encode($args),
        );

        $caller = new ToolCaller([$tool]);
        $results = $caller->executeAll([
            ['id' => 'call_1', 'type' => 'function', 'function' => ['name' => 'echo', 'arguments' => '{"msg":"hello"}']],
        ]);

        expect($results)->toHaveCount(1);
        expect($results[0]['role'])->toBe('tool');
        expect($results[0]['tool_call_id'])->toBe('call_1');

        $content = json_decode($results[0]['content'], true);
        expect($content['msg'])->toBe('hello');
    });

    test('executes multiple tool calls in order', function () {
        $tool = new Tool(
            name: 'add',
            description: 'adds numbers',
            schema: ['type' => 'object'],
            execute: fn(array $args): string => (string)($args['a'] + $args['b']),
        );

        $caller = new ToolCaller([$tool]);
        $results = $caller->executeAll([
            ['id' => 'c1', 'type' => 'function', 'function' => ['name' => 'add', 'arguments' => '{"a":1,"b":2}']],
            ['id' => 'c2', 'type' => 'function', 'function' => ['name' => 'add', 'arguments' => '{"a":3,"b":4}']],
        ]);

        expect($results)->toHaveCount(2);
        expect($results[0]['content'])->toBe('3');
        expect($results[1]['content'])->toBe('7');
    });

    test('throws ToolExecutionException when tool not found', function () {
        $tool = new Tool(
            name: 'exists',
            description: 'a tool',
            schema: ['type' => 'object'],
            execute: fn(array $args): string => 'ok',
        );

        $caller = new ToolCaller([$tool]);

        $caller->executeAll([
            ['id' => 'c1', 'type' => 'function', 'function' => ['name' => 'nonexistent', 'arguments' => '{}']],
        ]);
    })->throws(ToolExecutionException::class);

    test('ToolExecutionException contains tool name', function () {
        $exception = new ToolExecutionException(toolName: 'myTool');
        expect($exception->getToolName())->toBe('myTool');
    });

    describe('retry with exponential backoff', function () {
        test('retries on failure and succeeds', function () {
            $attempt = 0;

            $tool = new Tool(
                name: 'flaky',
                description: 'flaky tool',
                schema: ['type' => 'object'],
                execute: function (array $args) use (&$attempt): string {
                    $attempt++;
                    if ($attempt < 3) {
                        throw new RuntimeException("fail {$attempt}");
                    }
                    return "success at {$attempt}";
                },
                retries: 3,
            );

            $caller = new ToolCaller([$tool]);
            $results = $caller->executeAll([
                ['id' => 'c1', 'type' => 'function', 'function' => ['name' => 'flaky', 'arguments' => '{}']],
            ]);

            expect($attempt)->toBe(3);
            expect($results[0]['content'])->toBe('success at 3');
        });

        test('gives up after max retries', function () {
            $tool = new Tool(
                name: 'always_fail',
                description: 'always fails',
                schema: ['type' => 'object'],
                execute: fn(array $args): string => throw new RuntimeException('always fail'),
                retries: 2,
            );

            $caller = new ToolCaller([$tool]);

            $caller->executeAll([
                ['id' => 'c1', 'type' => 'function', 'function' => ['name' => 'always_fail', 'arguments' => '{}']],
            ]);
        })->throws(ToolExecutionException::class);
    });

    test('ToolTimeoutException class exists', function () {
        expect(class_exists(ToolTimeoutException::class))->toBeTrue();
        $e = new ToolTimeoutException(toolName: 'slow_tool');
        expect($e->getToolName())->toBe('slow_tool');
    });

    test('executes tool with arguments decoded from JSON', function () {
        $tool = new Tool(
            name: 'concat',
            description: 'concats strings',
            schema: ['type' => 'object'],
            execute: fn(array $args): string => $args['first'] . '-' . $args['second'],
        );

        $caller = new ToolCaller([$tool]);
        $results = $caller->executeAll([
            ['id' => 'c1', 'type' => 'function', 'function' => ['name' => 'concat', 'arguments' => '{"first":"hello","second":"world"}']],
        ]);

        expect($results[0]['content'])->toBe('hello-world');
    });
});

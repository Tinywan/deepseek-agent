<?php

declare(strict_types=1);

use DeepSeek\Agent\Exceptions\DeepSeekException;
use DeepSeek\Agent\Exceptions\InvalidConfigException;
use DeepSeek\Agent\Exceptions\MaxStepsExceededException;
use DeepSeek\Agent\Exceptions\ToolExecutionException;
use DeepSeek\Agent\Exceptions\ToolTimeoutException;

describe('Exceptions', function () {
    describe('DeepSeekException', function () {
        test('stores message and code', function () {
            $e = new DeepSeekException('api error', 400);
            expect($e->getMessage())->toBe('api error');
            expect($e->getCode())->toBe(400);
        });

        test('extends RuntimeException', function () {
            expect(new DeepSeekException('x'))->toBeInstanceOf(\RuntimeException::class);
        });
    });

    describe('InvalidConfigException', function () {
        test('extends DeepSeekException', function () {
            expect(new InvalidConfigException('bad config'))->toBeInstanceOf(DeepSeekException::class);
        });
    });

    describe('MaxStepsExceededException', function () {
        test('stores steps and maxSteps', function () {
            $e = new MaxStepsExceededException('too many', steps: [['step' => 1]], maxSteps: 5);
            expect($e->getSteps())->toBe([['step' => 1]]);
            expect($e->getMaxSteps())->toBe(5);
        });

        test('extends DeepSeekException', function () {
            expect(new MaxStepsExceededException('x', steps: [], maxSteps: 2))
                ->toBeInstanceOf(DeepSeekException::class);
        });
    });

    describe('ToolExecutionException', function () {
        test('stores tool name', function () {
            $e = new ToolExecutionException(toolName: 'myTool');
            expect($e->getToolName())->toBe('myTool');
        });

        test('optional message and previous', function () {
            $prev = new RuntimeException('inner');
            $e = new ToolExecutionException('failed', toolName: 't', previous: $prev);
            expect($e->getMessage())->toBe('failed');
            expect($e->getPrevious())->toBe($prev);
        });

        test('extends DeepSeekException', function () {
            expect(new ToolExecutionException(toolName: 'x'))->toBeInstanceOf(DeepSeekException::class);
        });
    });

    describe('ToolTimeoutException', function () {
        test('stores toolName', function () {
            $e = new ToolTimeoutException(message: 'timed out', toolName: 'slow');
            expect($e->getToolName())->toBe('slow');
            expect($e->getMessage())->toBe('timed out');
        });

        test('extends DeepSeekException', function () {
            expect(new ToolTimeoutException(toolName: 'x'))
                ->toBeInstanceOf(DeepSeekException::class);
        });
    });
});

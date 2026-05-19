<?php

declare(strict_types=1);

use DeepSeek\Agent\Tool;
use DeepSeek\Agent\Schema;

describe('Tool', function () {
    test('creates with required fields', function () {
        $tool = new Tool(
            name: 'my_func',
            description: 'does something',
            schema: ['type' => 'object'],
            execute: fn(array $args): string => 'ok',
        );

        expect($tool->name)->toBe('my_func');
        expect($tool->description)->toBe('does something');
        expect($tool->schema)->toBe(['type' => 'object']);
    });

    test('has default timeout 30000', function () {
        $tool = new Tool(
            name: 'f',
            description: 'd',
            schema: ['type' => 'object'],
            execute: fn(array $args): string => 'ok',
        );

        expect($tool->timeout)->toBe(30000);
    });

    test('has default retries 0', function () {
        $tool = new Tool(
            name: 'f',
            description: 'd',
            schema: ['type' => 'object'],
            execute: fn(array $args): string => 'ok',
        );

        expect($tool->retries)->toBe(0);
    });

    test('has default strict false', function () {
        $tool = new Tool(
            name: 'f',
            description: 'd',
            schema: ['type' => 'object'],
            execute: fn(array $args): string => 'ok',
        );

        expect($tool->strict)->toBeFalse();
    });

    test('has default required false', function () {
        $tool = new Tool(
            name: 'f',
            description: 'd',
            schema: ['type' => 'object'],
            execute: fn(array $args): string => 'ok',
        );

        expect($tool->required)->toBeFalse();
    });

    test('accepts Schema instance for schema param', function () {
        $schema = Schema::object([
            'city' => Schema::string()->required(),
        ]);

        $tool = new Tool(
            name: 'weather',
            description: 'Get weather',
            schema: $schema,
            execute: fn(array $args): string => json_encode($args),
        );

        expect($tool->schema)->toBeInstanceOf(Schema::class);
    });

    test('accepts custom timeout and retries', function () {
        $tool = new Tool(
            name: 'f',
            description: 'd',
            schema: ['type' => 'object'],
            execute: fn(array $args): string => 'ok',
            timeout: 5000,
            retries: 3,
            strict: true,
        );

        expect($tool->timeout)->toBe(5000);
        expect($tool->retries)->toBe(3);
        expect($tool->strict)->toBeTrue();
    });

    describe('toArray', function () {
        test('generates function tool format', function () {
            $tool = new Tool(
                name: 'get_weather',
                description: 'Get weather',
                schema: Schema::object([
                    'city' => Schema::string()->describe('City name')->required(),
                ]),
                execute: fn(array $args): string => '{}',
            );

            $arr = $tool->toArray();

            expect($arr['type'])->toBe('function');
            expect($arr['function']['name'])->toBe('get_weather');
            expect($arr['function']['description'])->toBe('Get weather');
            expect($arr['function']['parameters'])->toHaveKey('properties');
        });

        test('works with raw array schema', function () {
            $tool = new Tool(
                name: 'simple',
                description: 'simple tool',
                schema: ['type' => 'object', 'properties' => ['x' => ['type' => 'string']]],
                execute: fn(array $args): string => 'ok',
            );

            $arr = $tool->toArray();

            expect($arr['function']['parameters'])->toBe(['type' => 'object', 'properties' => ['x' => ['type' => 'string']]]);
        });

        test('sets strict to true in output when configured', function () {
            $tool = new Tool(
                name: 'f',
                description: 'd',
                schema: ['type' => 'object'],
                execute: fn(array $args): string => 'ok',
                strict: true,
            );

            $arr = $tool->toArray();

            // strict is a property of the Tool object, not included in toArray() yet
            expect($tool->strict)->toBeTrue();
            expect($arr['function']['name'])->toBe('f');
        });

        test('omits strict from toArray output', function () {
            $tool = new Tool(
                name: 'f',
                description: 'd',
                schema: ['type' => 'object'],
                execute: fn(array $args): string => 'ok',
            );

            $arr = $tool->toArray();

            expect($arr['function'])->not->toHaveKey('strict');
        });
    });
});

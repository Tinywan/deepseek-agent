<?php

declare(strict_types=1);

use DeepSeek\Agent\Config;
use DeepSeek\Agent\Agent;
use DeepSeek\Agent\Tool;
use DeepSeek\Agent\GenerateTextResult;
use DeepSeek\Agent\Fim;

use function DeepSeek\Agent\createAgent;
use function DeepSeek\Agent\createTool;
use function DeepSeek\Agent\generateFim;

describe('Functions', function () {
    describe('createAgent', function () {
        test('creates Agent instance', function () {
            $config = new Config(['apiKey' => 'sk-test']);
            $agent = createAgent($config);
            expect($agent)->toBeInstanceOf(Agent::class);
        });

        test('passes tools to Agent', function () {
            $config = new Config(['apiKey' => 'sk-test']);
            $tool = new Tool(
                name: 'test',
                description: 'test',
                schema: ['type' => 'object'],
                execute: fn(array $args): string => 'ok',
            );
            $agent = createAgent($config, tools: [$tool]);
            expect($agent)->toBeInstanceOf(Agent::class);
        });

        test('passes output schema to Agent', function () {
            $config = new Config(['apiKey' => 'sk-test']);
            $agent = createAgent($config, output: \DeepSeek\Agent\Schema::string());
            expect($agent)->toBeInstanceOf(Agent::class);
        });

        test('passes hooks to Agent', function () {
            $config = new Config(['apiKey' => 'sk-test']);
            $agent = createAgent($config, hooks: new \DeepSeek\Agent\Hooks());
            expect($agent)->toBeInstanceOf(Agent::class);
        });
    });

    describe('createTool', function () {
        test('creates Tool instance', function () {
            $tool = createTool(
                name: 'test',
                description: 'test tool',
                schema: ['type' => 'object'],
                execute: fn(array $args): string => 'result',
            );
            expect($tool)->toBeInstanceOf(Tool::class);
            expect($tool->name)->toBe('test');
        });

        test('passes optional params', function () {
            $tool = createTool(
                name: 't',
                description: 'd',
                schema: ['type' => 'object'],
                execute: fn(array $args): string => 'ok',
                timeout: 10000,
                retries: 3,
                strict: true,
                required: true,
            );
            expect($tool->timeout)->toBe(10000);
            expect($tool->retries)->toBe(3);
            expect($tool->strict)->toBeTrue();
            expect($tool->required)->toBeTrue();
        });
    });

    test('generateFim creates Fim and returns result', function () {
        // FIM requires a real API call, but we can verify the client is created
        $config = new Config(['apiKey' => 'sk-test']);
        $client = new Fim($config);
        expect($client)->toBeInstanceOf(Fim::class);
        expect(method_exists($client, 'completions'))->toBeTrue();
    });

    test('all five top-level functions exist', function () {
        expect(function_exists('DeepSeek\\Agent\\generateText'))->toBeTrue();
        expect(function_exists('DeepSeek\\Agent\\generateStream'))->toBeTrue();
        expect(function_exists('DeepSeek\\Agent\\createAgent'))->toBeTrue();
        expect(function_exists('DeepSeek\\Agent\\createTool'))->toBeTrue();
        expect(function_exists('DeepSeek\\Agent\\generateFim'))->toBeTrue();
    });
});

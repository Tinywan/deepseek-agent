<?php

declare(strict_types=1);

use DeepSeek\Agent\Config;
use DeepSeek\Agent\Exceptions\InvalidConfigException;

describe('Config', function () {
    test('creates with required apiKey', function () {
        $c = new Config(['apiKey' => 'sk-test']);
        expect($c->apiKey)->toBe('sk-test');
    });

    test('throws when apiKey is missing', function () {
        new Config([]);
    })->throws(InvalidConfigException::class, 'Missing required config field: apiKey');

    test('throws when apiKey is empty string', function () {
        new Config(['apiKey' => '']);
    })->throws(InvalidConfigException::class);

    test('has default baseUrl', function () {
        $c = new Config(['apiKey' => 'sk-test']);
        expect($c->baseUrl)->toBe('https://api.deepseek.com');
    });

    test('has default model deepseek-chat', function () {
        $c = new Config(['apiKey' => 'sk-test']);
        expect($c->model)->toBe('deepseek-chat');
    });

    test('has default temperature 1.0', function () {
        $c = new Config(['apiKey' => 'sk-test']);
        expect($c->temperature)->toBe(1.0);
    });

    test('has default maxTokens 2048', function () {
        $c = new Config(['apiKey' => 'sk-test']);
        expect($c->maxTokens)->toBe(2048);
    });

    test('accepts custom values', function () {
        $c = new Config([
            'apiKey'      => 'sk-custom',
            'baseUrl'     => 'https://custom.api.com',
            'model'       => 'deepseek-reasoner',
            'temperature' => 0.5,
            'maxTokens'   => 1024,
        ]);
        expect($c->apiKey)->toBe('sk-custom');
        expect($c->baseUrl)->toBe('https://custom.api.com');
        expect($c->model)->toBe('deepseek-reasoner');
        expect($c->temperature)->toBe(0.5);
        expect($c->maxTokens)->toBe(1024);
    });

    test('temperature at lower bound 0 is valid', function () {
        $c = new Config(['apiKey' => 'x', 'temperature' => 0]);
        expect($c->temperature)->toBe(0.0);
    });

    test('temperature at upper bound 2.0 is valid', function () {
        $c = new Config(['apiKey' => 'x', 'temperature' => 2.0]);
        expect($c->temperature)->toBe(2.0);
    });

    test('throws when temperature exceeds 2.0', function () {
        new Config(['apiKey' => 'x', 'temperature' => 2.1]);
    })->throws(InvalidConfigException::class, 'Temperature must be between 0 and 2');

    test('throws when temperature below 0', function () {
        new Config(['apiKey' => 'x', 'temperature' => -0.1]);
    })->throws(InvalidConfigException::class, 'Temperature must be between 0 and 2');

    test('casts temperature string to float', function () {
        $c = new Config(['apiKey' => 'x', 'temperature' => '1.5']);
        expect($c->temperature)->toBe(1.5);
    });

    describe('withConfig', function () {
        test('returns new instance', function () {
            $c = new Config(['apiKey' => 'sk-original']);
            $c2 = $c->withConfig(['model' => 'test-model']);
            expect($c2)->not->toBe($c);
        });

        test('overrides specified fields', function () {
            $c = new Config(['apiKey' => 'sk-test']);
            $c2 = $c->withConfig(['model' => 'deepseek-reasoner', 'temperature' => 0.3]);
            expect($c2->model)->toBe('deepseek-reasoner');
            expect($c2->temperature)->toBe(0.3);
        });

        test('preserves unspecified fields', function () {
            $c = new Config(['apiKey' => 'sk-test', 'baseUrl' => 'https://foo.com']);
            $c2 = $c->withConfig(['temperature' => 0.7]);
            expect($c2->apiKey)->toBe('sk-test');
            expect($c2->baseUrl)->toBe('https://foo.com');
            expect($c2->maxTokens)->toBe(2048);
        });

        test('original instance is unchanged', function () {
            $c = new Config(['apiKey' => 'sk-test']);
            $c2 = $c->withConfig(['model' => 'changed']);
            expect($c->model)->toBe('deepseek-chat');
            expect($c2->model)->toBe('changed');
        });

        test('still validates overrides', function () {
            $c = new Config(['apiKey' => 'x']);
            $c->withConfig(['temperature' => 5]);
        })->throws(InvalidConfigException::class);
    });
});

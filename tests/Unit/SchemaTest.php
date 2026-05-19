<?php

declare(strict_types=1);

use DeepSeek\Agent\Schema;

describe('Schema', function () {
    describe('type factories', function () {
        test('string() returns type=string', function () {
            expect(Schema::string()->toArray())->toBe(['type' => 'string']);
        });

        test('number() returns type=number', function () {
            expect(Schema::number()->toArray())->toBe(['type' => 'number']);
        });

        test('integer() returns type=integer', function () {
            expect(Schema::integer()->toArray())->toBe(['type' => 'integer']);
        });

        test('boolean() returns type=boolean', function () {
            expect(Schema::boolean()->toArray())->toBe(['type' => 'boolean']);
        });
    });

    describe('object', function () {
        test('builds flat object with properties', function () {
            $schema = Schema::object([
                'name' => Schema::string(),
                'age'  => Schema::integer(),
            ])->toArray();

            expect($schema['type'])->toBe('object');
            expect($schema['properties'])->toHaveKeys(['name', 'age']);
            expect($schema['properties']['name']['type'])->toBe('string');
            expect($schema['properties']['age']['type'])->toBe('integer');
        });

        test('collects required fields', function () {
            $schema = Schema::object([
                'email' => Schema::string()->required(),
                'name'  => Schema::string(),
                'age'   => Schema::integer()->required(),
            ])->toArray();

            expect($schema['required'])->toBe(['email', 'age']);
        });

        test('omits required key when no fields are required', function () {
            $schema = Schema::object([
                'x' => Schema::string(),
                'y' => Schema::number(),
            ])->toArray();

            expect($schema)->not->toHaveKey('required');
        });

        test('chainable describe() adds description', function () {
            $schema = Schema::object([
                'city' => Schema::string()->describe('City name'),
            ])->toArray();

            expect($schema['properties']['city']['description'])->toBe('City name');
        });

        test('nested objects', function () {
            $schema = Schema::object([
                'user' => Schema::object([
                    'address' => Schema::object([
                        'street' => Schema::string(),
                    ]),
                ]),
            ])->toArray();

            $address = $schema['properties']['user']['properties']['address'];
            expect($address['properties']['street']['type'])->toBe('string');
        });
    });

    describe('array', function () {
        test('array with string items', function () {
            $schema = Schema::array(Schema::string())->toArray();
            expect($schema['type'])->toBe('array');
            expect($schema['items']['type'])->toBe('string');
        });

        test('array with object items', function () {
            $schema = Schema::array(
                Schema::object(['name' => Schema::string()])
            )->toArray();

            expect($schema['items']['type'])->toBe('object');
            expect($schema['items']['properties']['name']['type'])->toBe('string');
        });

        test('array with description', function () {
            $schema = Schema::array(Schema::integer())
                ->describe('List of ages')
                ->toArray();

            expect($schema['description'])->toBe('List of ages');
            expect($schema['items']['type'])->toBe('integer');
        });
    });

    describe('enum', function () {
        test('enum with string values', function () {
            $schema = Schema::enum(['red', 'green', 'blue'])->toArray();
            expect($schema['type'])->toBe('string');
            expect($schema['enum'])->toBe(['red', 'green', 'blue']);
        });

        test('enum with description', function () {
            $schema = Schema::enum(['small', 'large'])
                ->describe('Size option')
                ->toArray();

            expect($schema['description'])->toBe('Size option');
            expect($schema['enum'])->toBe(['small', 'large']);
        });
    });

    describe('describe() chainable', function () {
        test('works on string schema', function () {
            $s = Schema::string()->describe('user name')->toArray();
            expect($s['description'])->toBe('user name');
        });

        test('works on number schema', function () {
            $s = Schema::number()->describe('rating')->toArray();
            expect($s['description'])->toBe('rating');
        });

        test('returns self for chaining', function () {
            $s = Schema::string();
            expect($s->describe('x'))->toBe($s);
        });
    });

    describe('required()', function () {
        test('returns self for chaining', function () {
            $s = Schema::string();
            expect($s->required())->toBe($s);
        });

        test('marks field as required in parent object', function () {
            $schema = Schema::object([
                'name' => Schema::string()->required(),
            ])->toArray();

            expect($schema['required'])->toContain('name');
        });

        test('required on standalone schema does nothing in toArray', function () {
            $s = Schema::string()->required()->toArray();
            expect($s)->toBe(['type' => 'string']);
        });
    });
});

<?php

declare(strict_types=1);

namespace DeepSeek\Wan;

/**
 * Generate a non-streaming text completion.
 *
 * @param array{model?: string, messages: array, temperature?: float, max_tokens?: int, response_format?: array} $params
 */
function generateText(Config $config, array $params): GenerateTextResult
{
    $generator = new TextGenerator($config);

    return $generator->generate($params);
}

/**
 * Generate a streaming text completion, yielding StreamEvent objects.
 *
 * @param array{model?: string, messages: array, temperature?: float, max_tokens?: int, response_format?: array} $params
 */
function generateStream(Config $config, array $params): \Generator
{
    $generator = new StreamGenerator($config);

    yield from $generator->generateStream($params);
}

/**
 * Create a new Agent instance.
 *
 * @param Tool[]|null $tools
 */
function createAgent(
    Config $config,
    ?array $tools = null,
    ?Schema $output = null,
    ?Hooks $hooks = null,
    ?int $maxSteps = null,
): Agent {
    return new Agent($config, $tools, $output, $hooks, $maxSteps);
}

/**
 * Create a new Tool instance (factory).
 */
function createTool(
    string $name,
    string $description,
    array|Schema $schema,
    \Closure $execute,
    int $timeout = 30000,
    int $retries = 0,
    bool $strict = false,
    bool $required = false,
): Tool {
    return new Tool($name, $description, $schema, $execute, $timeout, $retries, $strict, $required);
}

/**
 * Create a FIM (fill-in-the-middle) completion.
 *
 * @param array{prompt: string, suffix: string, model?: string, max_tokens?: int} $params
 */
function generateFim(Config $config, array $params): array
{
    $fimClient = new Fim($config);

    return $fimClient->completions($params);
}

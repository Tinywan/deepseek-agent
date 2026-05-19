# deepseek-agent

PHP Agent framework for the DeepSeek API — text generation, streaming, tool calling, structured output, and FIM completion.

> [中文文档](README_CN.md)

## Installation

```bash
composer require deepseek/agent
```

## Quick Start

```php
use DeepSeek\Agent\Config;
use function DeepSeek\Agent\generateText;

$config = new Config([
    'apiKey' => getenv('DEEPSEEK_API_KEY'),
    'model'  => 'deepseek-chat',
]);

$result = generateText($config, [
    'messages' => [
        ['role' => 'user', 'content' => 'Hello, tell me about yourself.'],
    ],
]);

echo $result->text;                     // Model response
echo $result->usage['total_tokens'];    // Token usage
```

## Features

### Streaming

```php
use function DeepSeek\Agent\generateStream;

$stream = generateStream($config, [
    'messages' => [['role' => 'user', 'content' => 'Tell me a story.']],
]);

foreach ($stream as $event) {
    if ($event instanceof \DeepSeek\Agent\TextDeltaEvent) {
        echo $event->delta;  // Output word by word
    }
}
```

Five event types: `TextDeltaEvent`, `ReasoningDeltaEvent`, `ToolCallEvent`, `StepEvent`, `FinishEvent`.

### Tool Calling

```php
use function DeepSeek\Agent\createAgent;
use function DeepSeek\Agent\createTool;
use DeepSeek\Agent\Schema;

$weatherTool = createTool(
    name: 'get_weather',
    description: 'Get the current weather for a given city.',
    schema: Schema::object([
        'city' => Schema::string()->describe('City name')->required(),
    ]),
    execute: function (array $args): string {
        return json_encode(['city' => $args['city'], 'temp' => 22, 'condition' => 'sunny']);
    },
    retries: 2,      // Retry on failure
    timeout: 30000,  // Timeout in milliseconds
);

$agent = createAgent($config, tools: [$weatherTool]);

$result = $agent->generate([
    ['role' => 'user', 'content' => "What's the weather like in Beijing today?"],
]);
```

- Automatic tool-call loop: detect `tool_calls` → execute → append result → continue
- Exponential backoff with jitter: `delay = min(100 * 2^attempt + random(0,100), 10000)` ms

### Structured Output

```php
use DeepSeek\Agent\Schema;
use function DeepSeek\Agent\createAgent;

$reviewSchema = Schema::object([
    'title'  => Schema::string()->describe('Book title')->required(),
    'author' => Schema::string()->describe('Author name')->required(),
    'rating' => Schema::number()->describe('Rating 1.0-5.0')->required(),
    'genres' => Schema::array(Schema::string())->describe('Genres'),
]);

$agent = createAgent($config, output: $reviewSchema);

$result = $agent->generate([
    ['role' => 'user', 'content' => 'Write a review of "The Three-Body Problem" by Liu Cixin.'],
]);

$review = json_decode($result->text, true);
```

Schema Builder supports: `string()`, `number()`, `integer()`, `boolean()`, `object([])`, `array()`, `enum([])`, with chainable `describe()` and `required()`.

### FIM (Fill-in-the-Middle)

```php
use function DeepSeek\Agent\generateFim;

$result = generateFim($config, [
    'prompt' => "def fibonacci(n):\n    \"\"\"Return the nth Fibonacci number.\"\"\"\n",
    'suffix' => "\n    return result\n",
    'max_tokens' => 256,
]);

echo $result['choices'][0]['text']; // Completed code
```

### Model List & Balance

```php
$model = new DeepSeek\Agent\Model($config);
$models = $model->list();      // GET /models
$balance = $model->balance();  // GET /user/balance
```

### Hook Lifecycle

```php
$hooks = new DeepSeek\Agent\Hooks();

$hooks->beforeStep(function (HookContext $ctx) {
    return ['config' => ['temperature' => 0.5]]; // Adjust params before each step
});

$hooks->afterStep(function (StepResult $result) {
    echo "Step {$result->step}: {$result->finishReason}\n";
});

$hooks->onError(function (HookError $error) {
    return 'handled'; // Return string to use as error message
});

$agent = createAgent($config, hooks: $hooks);
```

## Configuration

```php
$config = new DeepSeek\Agent\Config([
    'apiKey'      => 'sk-xxx',                     // Required
    'model'       => 'deepseek-chat',              // Default
    'baseUrl'     => 'https://api.deepseek.com',   // Default
    'temperature' => 1.0,                          // Default, range 0-2
    'maxTokens'   => 2048,                         // Default
]);

// Immutable config with inheritance
$newConfig = $config->withConfig(['model' => 'deepseek-reasoner']);
```

## Architecture

Three-layer design — pick the right abstraction for your use case:

```
Client layer    Chat / Fim / Model              → Direct HTTP calls
Generation layer  TextGenerator / StreamGenerator → Tool loop + Hooks + Events
Agent layer     Agent                           → Bundled config + tools + output
```

## Requirements

- PHP >= 8.1 with curl extension
- Composer

## License

Apache 2.0 — see [LICENSE](LICENSE).

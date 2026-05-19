<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use DeepSeek\Agent\Config;
use DeepSeek\Agent\Schema;
use function DeepSeek\Agent\createAgent;
use function DeepSeek\Agent\createTool;

$config = new Config([
    'apiKey' => getenv('DEEPSEEK_API_KEY') ?: 'your-api-key',
    'model'  => 'deepseek-chat',
]);

$weatherTool = createTool(
    name: 'get_weather',
    description: 'Get the current weather for a given city.',
    schema: Schema::object([
        'city' => Schema::string()->describe('The city name, e.g. Beijing')->required(),
    ]),
    execute: function (array $args): string {
        $city = $args['city'] ?? 'Unknown';
        $temps = ['Beijing' => 22, 'Shanghai' => 28, 'Tokyo' => 25];
        $temp = $temps[$city] ?? random_int(15, 35);
        return json_encode([
            'city'        => $city,
            'temperature' => $temp,
            'unit'        => 'celsius',
            'condition'   => 'sunny',
        ]);
    },
);

$agent = createAgent($config, tools: [$weatherTool]);

$result = $agent->generate([
    ['role' => 'user', 'content' => "What's the weather like in Beijing today?"],
]);

echo "Response: {$result->text}\n";
echo "Steps taken: " . count($result->steps) . "\n";
echo "Tokens used: " . ($result->usage['total_tokens'] ?? 'N/A') . "\n";

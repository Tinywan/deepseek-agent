<?php

require_once __DIR__ . '/../vendor/autoload.php';

use DeepSeek\Wan\Config;
use function DeepSeek\Wan\generateText;

$config = new Config([
    'apiKey' => getenv('DEEPSEEK_API_KEY') ?: 'your-api-key',
    'model'  => 'deepseek-chat',
]);

$result = generateText($config, [
    'messages' => [
        ['role' => 'system', 'content' => 'You are a helpful assistant.'],
        ['role' => 'user', 'content' => 'Hello, how are you?'],
    ],
]);

echo "Response: {$result->text}\n";
echo "Tokens used: " . ($result->usage['total_tokens'] ?? 'N/A') . "\n";
echo "Finish reason: {$result->finishReason}\n";

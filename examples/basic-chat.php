<?php

declare(strict_types=1);

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
        ['role' => 'user', 'content' => '你好！介绍一下你自己'],
    ],
]);

echo "Response: {$result->text}\n";
echo "Tokens used: " . ($result->usage['total_tokens'] ?? 'N/A') . "\n";
echo "Finish reason: {$result->finishReason}\n";

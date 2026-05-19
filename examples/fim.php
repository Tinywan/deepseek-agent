<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use DeepSeek\Agent\Config;
use function DeepSeek\Agent\generateFim;

$config = new Config([
    'apiKey' => getenv('DEEPSEEK_API_KEY') ?: 'your-api-key',
    'model'  => 'deepseek-chat',
]);

$result = generateFim($config, [
    'prompt' => 'def fibonacci(n):\n    """Return the nth Fibonacci number."""\n',
    'suffix' => '\n    return result\n',
    'max_tokens' => 256,
]);

$text = $result['choices'][0]['text'] ?? '';

echo "Completed code:\n";
echo "def fibonacci(n):\n    \"\"\"Return the nth Fibonacci number.\"\"\"\n";
echo $text;
echo "\n    return result\n";
echo "\n\nTokens used: " . ($result['usage']['total_tokens'] ?? 'N/A') . "\n";

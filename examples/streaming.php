<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use DeepSeek\Wan\Config;
use DeepSeek\Wan\StreamEventType;
use function DeepSeek\Wan\generateStream;

$config = new Config([
    'apiKey' => getenv('DEEPSEEK_API_KEY') ?: 'your-api-key',
    'model'  => 'deepseek-chat',
]);

$stream = generateStream($config, [
    'messages' => [
        ['role' => 'user', 'content' => 'Tell me a short story about a robot.'],
    ],
]);

$fullText = '';

foreach ($stream as $event) {
    if ($event instanceof \DeepSeek\Wan\TextDeltaEvent) {
        echo $event->delta;
        $fullText .= $event->delta;
    } elseif ($event instanceof \DeepSeek\Wan\FinishEvent) {
        echo "\n\n---\n";
        echo "Tokens: " . json_encode($event->usage) . "\n";
    }
}

echo "\n";

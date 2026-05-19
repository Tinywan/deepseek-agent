<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use DeepSeek\Agent\Config;
use DeepSeek\Agent\StreamEventType;
use function DeepSeek\Agent\generateStream;

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
    if ($event instanceof \DeepSeek\Agent\TextDeltaEvent) {
        echo $event->delta;
        $fullText .= $event->delta;
    } elseif ($event instanceof \DeepSeek\Agent\FinishEvent) {
        echo "\n\n---\n";
        echo "Tokens: " . json_encode($event->usage) . "\n";
    }
}

echo "\n";

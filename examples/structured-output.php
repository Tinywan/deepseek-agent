<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use DeepSeek\Wan\Config;
use DeepSeek\Wan\Schema;
use function DeepSeek\Wan\createAgent;

$config = new Config([
    'apiKey' => getenv('DEEPSEEK_API_KEY') ?: 'your-api-key',
    'model'  => 'deepseek-chat',
]);

// Define a structured output schema for a book review
$reviewSchema = Schema::object([
    'title'   => Schema::string()->describe('The book title')->required(),
    'author'  => Schema::string()->describe('The author name')->required(),
    'rating'  => Schema::number()->describe('Rating from 1.0 to 5.0')->required(),
    'summary' => Schema::string()->describe('A one-paragraph summary'),
    'genres'  => Schema::array(Schema::string())->describe('List of genres'),
]);

$agent = createAgent($config, output: $reviewSchema);

$result = $agent->generate([
    ['role' => 'user', 'content' => 'Write a review of the book "The Three-Body Problem" by Liu Cixin.'],
]);

$review = json_decode($result->text, true);

echo "Book: {$review['title']} by {$review['author']}\n";
echo "Rating: {$review['rating']}/5\n";
echo "Genres: " . implode(', ', $review['genres'] ?? []) . "\n";
echo "Summary: {$review['summary']}\n";
echo "\nTokens used: " . ($result->usage['total_tokens'] ?? 'N/A') . "\n";

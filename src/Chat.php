<?php

namespace DeepSeek\Wan;

use DeepSeek\Wan\Exceptions\DeepSeekException;
use Webman\Openai\Chat as OpenaiChat;

class Chat
{
    private OpenaiChat $client;

    public function __construct(private readonly Config $config)
    {
        $this->client = new OpenaiChat([
            'apiKey'  => $config->apiKey,
            'baseUrl' => $config->baseUrl,
        ]);
    }

    public function completions(array $params): array
    {
        $params = $this->mergeDefaults($params);

        try {
            return $this->client->completions($params);
        } catch (\Throwable $e) {
            throw new DeepSeekException(
                'Chat completion failed: ' . $e->getMessage(),
                (int)$e->getCode(),
                $e,
            );
        }
    }

    public function completionsStream(array $params): \Generator
    {
        $params = $this->mergeDefaults($params);
        $params['stream'] = true;

        try {
            yield from $this->client->completionsStream($params);
        } catch (\Throwable $e) {
            throw new DeepSeekException(
                'Chat streaming failed: ' . $e->getMessage(),
                (int)$e->getCode(),
                $e,
            );
        }
    }

    private function mergeDefaults(array $params): array
    {
        return [
            'model'       => $this->config->model,
            'temperature' => $this->config->temperature,
            'max_tokens'  => $this->config->maxTokens,
            ...$params,
        ];
    }
}

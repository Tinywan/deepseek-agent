<?php

declare(strict_types=1);

namespace DeepSeek\Wan;

use DeepSeek\Wan\Exceptions\DeepSeekException;

class Chat
{
    private HttpClient $http;

    public function __construct(private readonly Config $config)
    {
        $this->http = new HttpClient($config);
    }

    public function completions(array $params): array
    {
        $params = $this->mergeDefaults($params);

        try {
            return $this->http->request('POST', '/chat/completions', $params);
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
            yield from $this->http->streamRequest('POST', '/chat/completions', $params);
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

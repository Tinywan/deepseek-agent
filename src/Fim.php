<?php

namespace DeepSeek\Wan;

use DeepSeek\Wan\Exceptions\DeepSeekException;

class Fim
{
    public function __construct(private readonly Config $config)
    {
    }

    public function completions(array $params): array
    {
        $params = $this->mergeDefaults($params);

        $http = new HttpClient($this->config);

        return $http->request('POST', '/beta/completions', $params);
    }

    public function completionsStream(array $params): \Generator
    {
        $params = $this->mergeDefaults($params);
        $params['stream'] = true;

        $http = new HttpClient($this->config);

        yield from $http->streamRequest('POST', '/beta/completions', $params);
    }

    private function mergeDefaults(array $params): array
    {
        return [
            'model'      => $this->config->model,
            'max_tokens' => $this->config->maxTokens,
            ...$params,
        ];
    }
}

<?php

namespace DeepSeek\Wan;

use DeepSeek\Wan\Exceptions\InvalidConfigException;

final readonly class Config
{
    public string $apiKey;
    public string $baseUrl;
    public string $model;
    public float $temperature;
    public int $maxTokens;

    /** @var array<string, mixed> */
    public array $extra;

    public function __construct(array $config)
    {
        if (empty($config['apiKey'])) {
            throw new InvalidConfigException('Missing required config field: apiKey');
        }

        $temperature = (float)($config['temperature'] ?? 1.0);
        if ($temperature < 0 || $temperature > 2) {
            throw new InvalidConfigException(
                "Temperature must be between 0 and 2, got: {$temperature}"
            );
        }

        $this->apiKey = $config['apiKey'];
        $this->baseUrl = (string)($config['baseUrl'] ?? 'https://api.deepseek.com');
        $this->model = (string)($config['model'] ?? 'deepseek-chat');
        $this->temperature = $temperature;
        $this->maxTokens = (int)($config['maxTokens'] ?? 2048);
        $this->extra = $config['extra'] ?? [];
    }

    public function withConfig(array $overrides): self
    {
        $merged = [
            'apiKey'      => $this->apiKey,
            'baseUrl'     => $this->baseUrl,
            'model'       => $this->model,
            'temperature' => $this->temperature,
            'maxTokens'   => $this->maxTokens,
            'extra'       => $this->extra,
            ...$overrides,
        ];

        return new self($merged);
    }
}

<?php

declare(strict_types=1);

namespace DeepSeek\Wan;

class Hooks
{
    /** @var callable[] */
    private array $beforeStepCallbacks = [];

    /** @var callable[] */
    private array $afterStepCallbacks = [];

    /** @var callable[] */
    private array $onErrorCallbacks = [];

    public function beforeStep(callable $callback): self
    {
        $this->beforeStepCallbacks[] = $callback;
        return $this;
    }

    public function afterStep(callable $callback): self
    {
        $this->afterStepCallbacks[] = $callback;
        return $this;
    }

    public function onError(callable $callback): self
    {
        $this->onErrorCallbacks[] = $callback;
        return $this;
    }

    /**
     * Execute beforeStep hooks. Returns modified messages and config overrides.
     *
     * @param array<int, array{role: string, content: string}> $messages
     * @return array{messages: array, config: array}
     */
    public function runBeforeStep(int $step, array $messages, Config $config): array
    {
        $context = new HookContext($step, $messages, $config);
        $configOverrides = [];

        foreach ($this->beforeStepCallbacks as $callback) {
            $result = $callback($context);
            if (is_array($result)) {
                if (isset($result['messages'])) {
                    $messages = $result['messages'];
                }
                if (isset($result['config'])) {
                    $configOverrides = array_merge($configOverrides, $result['config']);
                }
            }
        }

        return ['messages' => $messages, 'config' => $configOverrides];
    }

    public function runAfterStep(StepResult $result): void
    {
        foreach ($this->afterStepCallbacks as $callback) {
            $callback($result);
        }
    }

    public function runOnError(HookError $error): ?string
    {
        foreach ($this->onErrorCallbacks as $callback) {
            $result = $callback($error);
            if (is_string($result)) {
                return $result;
            }
        }

        return null;
    }
}

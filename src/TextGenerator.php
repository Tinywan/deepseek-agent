<?php

declare(strict_types=1);

namespace DeepSeek\Wan;

use DeepSeek\Wan\Exceptions\DeepSeekException;
use DeepSeek\Wan\Exceptions\MaxStepsExceededException;

class TextGenerator
{
    private Chat $chat;
    private ?ToolCaller $toolCaller;

    /** @var Tool[] */
    private array $tools;

    private int $maxSteps;

    /** @param Tool[]|null $tools */
    public function __construct(
        private Config $config,
        private readonly Hooks $hooks = new Hooks(),
        ?array $tools = null,
        ?int $maxSteps = null,
    ) {
        $this->chat = new Chat($config);
        $this->tools = $tools ?? [];
        $this->toolCaller = $tools ? new ToolCaller($tools) : null;
        $this->maxSteps = $maxSteps ?? 10;
    }

    /** @param array{model?: string, messages: array, temperature?: float, max_tokens?: int, response_format?: array} $params */
    public function generate(array $params): GenerateTextResult
    {
        $messages = $params['messages'];
        $stepResults = [];

        for ($step = 1; $step <= $this->maxSteps; $step++) {
            try {
                $hookResult = $this->hooks->runBeforeStep($step, $messages, $this->config);
                $messages = $hookResult['messages'];

                if ($hookResult['config']) {
                    $this->config = $this->config->withConfig($hookResult['config']);
                    $this->chat = new Chat($this->config);
                }
            } catch (\Throwable $e) {
                $this->hooks->runOnError(new HookError($step, $e->getMessage(), $e));
                throw $e;
            }

            $requestParams = array_merge(
                [
                    'model'       => $this->config->model,
                    'messages'    => $messages,
                    'temperature' => $params['temperature'] ?? $this->config->temperature,
                    'max_tokens'  => $params['max_tokens'] ?? $this->config->maxTokens,
                ],
                isset($params['response_format']) ? ['response_format' => $params['response_format']] : [],
                $this->tools ? ['tools' => array_map(fn(Tool $t) => $t->toArray(), $this->tools)] : [],
            );

            try {
                $response = $this->chat->completions($requestParams);
            } catch (\Throwable $e) {
                $errorMsg = $this->hooks->runOnError(
                    new HookError($step, $e->getMessage(), $e)
                );
                throw new DeepSeekException($errorMsg ?? $e->getMessage(), (int)$e->getCode(), $e);
            }

            $choice = $response['choices'][0] ?? [];
            $finishReason = $choice['finish_reason'] ?? 'stop';
            $usage = $response['usage'] ?? null;

            $messages[] = $choice['message'] ?? ['role' => 'assistant', 'content' => ''];

            $stepResults[] = [
                'step'         => $step,
                'finishReason' => $finishReason,
                'usage'        => $usage,
            ];

            $this->hooks->runAfterStep(new StepResult(
                step: $step,
                type: $finishReason === 'tool_calls' ? 'tool_calls' : 'text',
                usage: $usage,
                finishReason: $finishReason,
            ));

            if ($finishReason === 'tool_calls' && $this->toolCaller) {
                $toolCalls = $choice['message']['tool_calls'] ?? [];
                $toolMessages = $this->toolCaller->executeAll($toolCalls);
                $messages = array_merge($messages, $toolMessages);
                continue;
            }

            $text = $choice['message']['content'] ?? '';

            return new GenerateTextResult(
                text: $text,
                finishReason: $finishReason,
                usage: $usage,
                steps: $stepResults,
                messages: $messages,
            );
        }

        throw new MaxStepsExceededException(
            "Exceeded maximum steps ({$this->maxSteps})",
            steps: $stepResults,
            maxSteps: $this->maxSteps,
        );
    }
}

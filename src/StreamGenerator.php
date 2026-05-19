<?php

declare(strict_types=1);

namespace DeepSeek\Agent;

use DeepSeek\Agent\Exceptions\DeepSeekException;
use DeepSeek\Agent\Exceptions\MaxStepsExceededException;

class StreamGenerator
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
    public function generateStream(array $params): \Generator
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

            $accumulatedContent = '';
            $accumulatedReasoning = '';
            $toolCallsDelta = [];
            $finishReason = 'stop';
            $usage = null;

            try {
                foreach ($this->chat->completionsStream($requestParams) as $chunk) {
                    $choice = $chunk['choices'][0] ?? [];
                    $delta = $choice['delta'] ?? [];
                    $finishReason = $choice['finish_reason'] ?? $finishReason;
                    $usage = $chunk['usage'] ?? $usage;

                    // Text delta
                    if (!empty($delta['content'])) {
                        $accumulatedContent .= $delta['content'];
                        yield new TextDeltaEvent(delta: $delta['content']);
                    }

                    // Reasoning delta
                    if (!empty($delta['reasoning_content'])) {
                        $accumulatedReasoning .= $delta['reasoning_content'];
                        yield new ReasoningDeltaEvent(delta: $delta['reasoning_content']);
                    }

                    // Tool calls delta — aggregate by index
                    if (!empty($delta['tool_calls'])) {
                        foreach ($delta['tool_calls'] as $tc) {
                            $index = $tc['index'];
                            if (!isset($toolCallsDelta[$index])) {
                                $toolCallsDelta[$index] = [
                                    'id'   => $tc['id'] ?? '',
                                    'type' => 'function',
                                    'function' => [
                                        'name'      => '',
                                        'arguments' => '',
                                    ],
                                ];
                            }
                            if (isset($tc['id'])) {
                                $toolCallsDelta[$index]['id'] = $tc['id'];
                            }
                            if (isset($tc['function']['name'])) {
                                $toolCallsDelta[$index]['function']['name'] .= $tc['function']['name'];
                            }
                            if (isset($tc['function']['arguments'])) {
                                $toolCallsDelta[$index]['function']['arguments'] .= $tc['function']['arguments'];
                            }

                            yield new ToolCallEvent(
                                callId: $toolCallsDelta[$index]['id'],
                                name: $toolCallsDelta[$index]['function']['name'],
                                arguments: $tc['function']['arguments'] ?? '',
                            );
                        }
                    }
                }
            } catch (\Throwable $e) {
                $errorMsg = $this->hooks->runOnError(
                    new HookError($step, $e->getMessage(), $e)
                );
                throw new DeepSeekException($errorMsg ?? $e->getMessage(), (int)$e->getCode(), $e);
            }

            // Build assistant message
            $assistantMessage = ['role' => 'assistant', 'content' => $accumulatedContent];
            if ($accumulatedReasoning) {
                $assistantMessage['reasoning_content'] = $accumulatedReasoning;
            }
            if ($toolCallsDelta) {
                $assistantMessage['tool_calls'] = array_values($toolCallsDelta);
            }
            $messages[] = $assistantMessage;

            $stepResults[] = [
                'step'         => $step,
                'finishReason' => $finishReason,
                'usage'        => $usage,
            ];

            yield new StepEvent(
                stepNumber: $step,
                finishReason: $finishReason,
                usage: $usage,
            );

            $this->hooks->runAfterStep(new StepResult(
                step: $step,
                type: $finishReason === 'tool_calls' ? 'tool_calls' : 'text',
                usage: $usage,
                finishReason: $finishReason,
            ));

            if ($finishReason === 'tool_calls' && $this->toolCaller) {
                $toolMessages = $this->toolCaller->executeAll(array_values($toolCallsDelta));
                $messages = array_merge($messages, $toolMessages);
                continue;
            }

            yield new FinishEvent(
                finishReason: $finishReason,
                usage: $usage,
                text: $accumulatedContent,
            );

            return;
        }

        throw new MaxStepsExceededException(
            "Exceeded maximum steps ({$this->maxSteps})",
            steps: $stepResults,
            maxSteps: $this->maxSteps,
        );
    }
}

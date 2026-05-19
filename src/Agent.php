<?php

namespace DeepSeek\Wan;

use DeepSeek\Wan\Exceptions\DeepSeekException;

class Agent
{
    /** @var Tool[] */
    private array $tools;

    private ?Schema $outputSchema;

    private Hooks $hooks;

    private int $maxSteps;

    public function __construct(
        private readonly Config $config,
        ?array $tools = null,
        ?Schema $output = null,
        ?Hooks $hooks = null,
        ?int $maxSteps = null,
    ) {
        $this->assertModelCapabilities($config->model, $tools, $output);
        $this->tools = $tools ?? [];
        $this->outputSchema = $output;
        $this->hooks = $hooks ?? new Hooks();
        $this->maxSteps = $maxSteps ?? 10;
    }

    /** @param array<int, array{role: string, content: string}> $messages */
    public function generate(array $messages): GenerateTextResult
    {
        $generator = new TextGenerator(
            config: $this->config,
            hooks: $this->hooks,
            tools: $this->tools ?: null,
            maxSteps: $this->maxSteps,
        );

        $params = ['messages' => $messages];

        if ($this->outputSchema) {
            $params['response_format'] = [
                'type'       => 'json_schema',
                'json_schema' => [
                    'name'   => 'output',
                    'schema' => $this->outputSchema->toArray(),
                ],
            ];
        }

        return $generator->generate($params);
    }

    /** @param array<int, array{role: string, content: string}> $messages */
    public function stream(array $messages): \Generator
    {
        $generator = new StreamGenerator(
            config: $this->config,
            hooks: $this->hooks,
            tools: $this->tools ?: null,
            maxSteps: $this->maxSteps,
        );

        $params = ['messages' => $messages];

        if ($this->outputSchema) {
            $params['response_format'] = [
                'type'       => 'json_schema',
                'json_schema' => [
                    'name'   => 'output',
                    'schema' => $this->outputSchema->toArray(),
                ],
            ];
        }

        yield from $generator->generateStream($params);
    }

    public function hooks(): Hooks
    {
        return $this->hooks;
    }

    /**
     * R1/reasoner models do not support function calling, JSON output, or FIM.
     *
     * @param Tool[]|null $tools
     */
    private function assertModelCapabilities(string $model, ?array $tools, ?Schema $output): void
    {
        $isReasoner = str_contains(strtolower($model), 'reasoner') || str_contains(strtolower($model), 'r1');

        if (!$isReasoner) {
            return;
        }

        if ($tools) {
            throw new DeepSeekException(
                "Model '{$model}' does not support function calling / tools."
            );
        }

        if ($output) {
            throw new DeepSeekException(
                "Model '{$model}' does not support structured JSON output."
            );
        }
    }
}

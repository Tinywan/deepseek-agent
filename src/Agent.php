<?php

declare(strict_types=1);

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
        $params = $this->buildParams($messages);

        $generator = new TextGenerator(
            config: $this->config,
            hooks: $this->hooks,
            tools: $this->tools ?: null,
            maxSteps: $this->maxSteps,
        );

        return $generator->generate($params);
    }

    /** @param array<int, array{role: string, content: string}> $messages */
    public function stream(array $messages): \Generator
    {
        $params = $this->buildParams($messages);

        $generator = new StreamGenerator(
            config: $this->config,
            hooks: $this->hooks,
            tools: $this->tools ?: null,
            maxSteps: $this->maxSteps,
        );

        yield from $generator->generateStream($params);
    }

    private function buildParams(array $messages): array
    {
        if ($this->outputSchema) {
            $schemaJson = json_encode(
                $this->outputSchema->toArray(),
                JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT
            );
            $systemMsg = "You must respond with valid JSON that conforms to this JSON Schema:\n{$schemaJson}\n\nOutput only the JSON object, no markdown or other text.";

            // Prepend system message if none exists, or augment existing one
            $hasSystem = false;
            foreach ($messages as $i => &$msg) {
                if (($msg['role'] ?? '') === 'system') {
                    $msg['content'] = $msg['content'] . "\n\n" . $systemMsg;
                    $hasSystem = true;
                    break;
                }
            }
            unset($msg);

            if (!$hasSystem) {
                array_unshift($messages, ['role' => 'system', 'content' => $systemMsg]);
            }

            return [
                'messages'         => $messages,
                'response_format'  => ['type' => 'json_object'],
            ];
        }

        return ['messages' => $messages];
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

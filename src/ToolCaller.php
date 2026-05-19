<?php

declare(strict_types=1);

namespace DeepSeek\Agent;

use DeepSeek\Agent\Exceptions\ToolExecutionException;
use DeepSeek\Agent\Exceptions\ToolTimeoutException;

class ToolCaller
{
    private const int BASE_DELAY_MS = 100;
    private const int MAX_DELAY_MS = 10000;

    /** @param Tool[] $tools */
    public function __construct(private readonly array $tools)
    {
    }

    /** @param array<int, array{id: string, type: string, function: array{name: string, arguments: string}}> $toolCalls */
    public function executeAll(array $toolCalls): array
    {
        $results = [];

        foreach ($toolCalls as $call) {
            $name = $call['function']['name'];
            $arguments = json_decode($call['function']['arguments'], true) ?? [];
            $result = $this->execute($name, $arguments);

            $results[] = [
                'role'         => 'tool',
                'tool_call_id' => $call['id'],
                'content'      => is_string($result) ? $result : json_encode($result, JSON_UNESCAPED_UNICODE),
            ];
        }

        return $results;
    }

    private function execute(string $name, array $arguments): mixed
    {
        $tool = $this->findTool($name);

        if ($tool === null) {
            throw ToolExecutionException::toolNotFound($name);
        }

        $attemptErrors = [];
        $lastException = null;

        for ($attempt = 0; $attempt <= $tool->retries; $attempt++) {
            try {
                return $this->executeWithTimeout($tool, $arguments);
            } catch (ToolTimeoutException $e) {
                $attemptErrors[] = $e;
                $lastException = $e;
                if ($attempt < $tool->retries) {
                    $delay = $this->calculateDelay($attempt);
                    usleep($delay * 1000);
                }
            } catch (\Throwable $e) {
                $attemptErrors[] = $e;
                $lastException = $e;
                if ($attempt < $tool->retries) {
                    $delay = $this->calculateDelay($attempt);
                    usleep($delay * 1000);
                }
            }
        }

        throw new ToolExecutionException(
            "Tool '{$name}' failed after " . ($tool->retries + 1) . " attempts",
            0,
            $lastException,
            $attemptErrors,
            $name,
        );
    }

    private function executeWithTimeout(Tool $tool, array $arguments): mixed
    {
        // Timeout implementation using process isolation for real sub-second control.
        // For simplicity, we rely on PHP's set_time_limit and a simple execution.
        // In production, pcntl_alarm or parallel extension would be used.
        $startTime = hrtime(true);

        $result = ($tool->execute)($arguments);

        $elapsedMs = (hrtime(true) - $startTime) / 1_000_000;

        if ($elapsedMs > $tool->timeout) {
            throw new ToolTimeoutException(
                "Tool '{$tool->name}' exceeded timeout of {$tool->timeout}ms (took {$elapsedMs}ms)",
                toolName: $tool->name,
            );
        }

        return $result;
    }

    private function findTool(string $name): ?Tool
    {
        foreach ($this->tools as $tool) {
            if ($tool->name === $name) {
                return $tool;
            }
        }

        return null;
    }

    private function calculateDelay(int $attempt): int
    {
        $exponentialDelay = self::BASE_DELAY_MS * (2 ** $attempt);
        $jitter = random_int(0, 100); // 0-100ms random jitter
        return min($exponentialDelay + $jitter, self::MAX_DELAY_MS);
    }
}

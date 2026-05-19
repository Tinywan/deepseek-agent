# PRD: DeepSeek PHP Agent Framework (`deepseek/agent`)

## Problem Statement

PHP developers integrating with the DeepSeek API face repeated boilerplate: raw curl calls, manual SSE stream parsing, ad-hoc JSON Schema construction, and hand-rolled tool-calling loops. Existing solutions either require heavy frameworks (Workerman/coroutine runtimes) or lock users into a specific ecosystem. A lightweight, zero-dependency library is needed that provides the full DeepSeek API surface — text generation, streaming, tool calling, structured output, and FIM completion — behind clean PHP interfaces.

## Solution

`deepseek/agent` is a zero-dependency PHP library (PHP >= 8.1, curl extension) that wraps the complete DeepSeek API in a three-layer architecture. Users pick the abstraction level that fits their use case:

- **Client layer** — direct HTTP calls via `Chat`, `Fim`, `Model`
- **Generation layer** — tool-calling loops, Hook lifecycle, streaming events
- **Agent layer** — pre-configured agents with bound tools and output schema

The library uses PHP 8.1+ features (readonly classes, enums, named arguments, union types) for type safety and ergonomics, and includes an immutable Config pattern for safe configuration inheritance.

## User Stories

1. As a PHP developer, I want to send a single prompt to DeepSeek and get a complete text response, so that I can integrate AI text generation into my application with minimal code.
2. As a PHP developer, I want to stream responses word-by-word, so that I can display real-time output to users for long-form generation.
3. As a PHP developer, I want to define tools/functions that the model can call, so that I can build AI agents that interact with external systems (weather APIs, databases, etc.).
4. As a PHP developer, I want failed tool calls to automatically retry with exponential backoff, so that transient errors don't break the agent loop.
5. As a PHP developer, I want the model to return structured JSON matching my schema, so that I can parse predictable outputs for downstream processing.
6. As a PHP developer, I want to use fill-in-the-middle completion for code generation, so that I can build editor integrations that complete code between a prefix and suffix.
7. As a PHP developer, I want to query available models and account balance, so that I can manage API usage programmatically.
8. As a PHP developer, I want hook callbacks around each generation step (before/after/onError), so that I can implement custom logging, parameter adjustment, and error recovery without forking the library.
9. As a PHP developer, I want immutable configuration objects where I can safely create derived configs, so that different parts of my application can share a base config with specific overrides.
10. As a PHP developer, I want fluent schema builder methods (string(), number(), object(), describe(), required()), so that I can construct JSON Schema definitions with IDE autocompletion instead of hand-writing arrays.
11. As a PHP developer, I want the library to automatically detect model capabilities and reject invalid combinations (reasoner+tools, reasoner+JSON output), so that I catch misconfigurations at construction time instead of getting cryptic API errors.
12. As a library author, I want zero third-party PHP dependencies beyond the PHP runtime, so that my library imposes no version conflicts on downstream projects.
13. As a developer evaluating the library, I want working example scripts for each feature (chat, stream, tools, structured-output, FIM), so that I can copy-paste and adapt them in seconds.

## Implementation Decisions

### Architecture: Three-Layer Design

The library is split into Client, Generation, and Agent layers. Users pick the abstraction level that fits their use case. Lower layers are usable standalone; higher layers compose lower ones.

### Zero-Dependency HTTP Client

A custom `HttpClient` wraps PHP's native `curl` extension. For SSE streaming, it uses `CURLOPT_WRITEFUNCTION` with a `SplQueue` buffer, parsing `data:` lines and `[DONE]` signals. This avoids the coroutine requirement that `webman/openai` imposed and adds zero external PHP dependencies.

### Immutable Config with `withConfig()`

`Config` is a `final readonly class`. All fields are validated in the constructor (apiKey required, temperature 0-2). The `withConfig(array $overrides)` method returns a **new** instance — the original is never mutated. This makes per-request overrides safe even in concurrent or iterative contexts (tool loops, hooks).

### Tool-Call Loop with Exponential Backoff

```
for step in 1..maxSteps:
    beforeStep hooks → call API → parse response
    if finish_reason == 'tool_calls':
        execute tools with retry → append results → continue
    else:
        afterStep hooks → return result
```

`ToolCaller.executeAll()` matches tool call IDs to registered tools, decodes JSON arguments, and calls the execute closure. On failure, it retries with exponential backoff: `delay = min(100 * 2^attempt + random(0,100), 10000)` ms, up to the tool's configured `retries` limit.

### Structured Output via System Prompt Injection

DeepSeek's `json_schema` response format is not universally available. The library uses `response_format: { type: 'json_object' }` and injects the JSON Schema into the system message: "You must respond with valid JSON that conforms to this JSON Schema...". This ensures broad model compatibility while maintaining structured output guarantees.

### Schema Builder with `toArray()` Compilation

The `Schema` class is a fluent builder that compiles to JSON Schema arrays. It supports six types, `object()` with nested properties and auto-collected `required` fields, `array()` with typed items, and `enum()` with string values. `describe()` and `required()` return `$this` for chaining.

### Reasoner Model Guard

Reasoner models (`deepseek-reasoner`, `deepseek-r1`) do not support function calling or JSON output. The `Agent` constructor asserts this: if a reasoner model is paired with tools or an output schema, it throws a `DeepSeekException` immediately. The check uses a case-insensitive substring match on the model name.

### Streaming Event System

Streaming yields typed event objects rather than raw arrays: `TextDeltaEvent`, `ReasoningDeltaEvent`, `ToolCallEvent`, `StepEvent`, `FinishEvent`. A `StreamEventType` enum maps each to a string value. Tool calls are aggregated by index across streaming chunks (DeepSeek sends partial tool_calls deltas).

### Hook Lifecycle

Three hook types — `beforeStep`, `afterStep`, `onError` — are registered on a `Hooks` object and passed to generators or the Agent. `beforeStep` callbacks can return `['config' => [...overrides...]]` to adjust parameters per-step (e.g. reduce temperature mid-loop). Multiple hooks of the same type run in registration order; later config overrides merge into earlier ones.

### Top-Level Functions

Five namespaced functions provide a flat-entry API: `generateText()`, `generateStream()`, `createAgent()`, `createTool()`, `generateFim()`. They are autoloaded via Composer's `files` directive so no class instantiation is needed for basic use.

## Testing Decisions

### What Makes a Good Test

Tests verify external behavior through public interfaces, not implementation details. A good test answers: "If I call this public method with these inputs, what should I observe?" It should survive internal refactoring as long as the public API doesn't change.

### Modules Under Test

| Module | Test Count | Focus |
|---|---|---|
| Config | 18 | Constructor validation (required fields, temperature range), default values, withConfig immutability and inheritance |
| Schema | 20 | All type factories, object property nesting, required field collection, describe/required chaining, enum values |
| Tool | 11 | Property defaults (timeout/retries/strict/required), Schema vs array schema param, toArray function-format output |
| ToolCaller | 8 | Single and multi-tool execution, missing-tool exception, retry with exponential backoff (success and exhaustion cases), argument JSON decoding |
| EventDTOs | 13 | All 5 event types (TextDelta, ReasoningDelta, ToolCall, Step, Finish), constructor parameters, nullable fields, UTF-8 handling |
| StreamEventType | 7 | Enum case count, individual values, BackedEnum interface |
| Hooks | 12 | beforeStep/afterStep/onError registration, execution with context, multiple hooks ordering, null config handling, no-hook defaults |
| Functions | 8 | All 5 top-level functions exist and return correct instances, optional parameter forwarding |
| Exceptions | 10 | Inheritance chain (DeepSeekException → RuntimeException), message/code storage, toolName on ToolExecutionException and ToolTimeoutException, MaxStepsExceededException steps/maxSteps |

### Test Framework

PestPHP 4.7 with PHPUnit 10. Configuration in `phpunit.xml`, bootstrap via `vendor/autoload.php`.

### Prior Art

- `tests/validate.php` (92 structural checks) served as the initial validation suite
- Example scripts in `examples/` double as manual integration tests against the live API

## Out of Scope

- Multi-turn conversation state management (the user manages the messages array)
- Async/parallel tool execution (tools run sequentially)
- Built-in caching (add via hooks if needed)
- DeepSeek Batch API support
- PSR-7/PSR-18 HTTP adapter support (the curl-based HttpClient is self-contained)
- Laravel/Symfony/WordPress service provider integrations
- Vision/multimodal support (pass image data in messages if the model supports it, but no file handling utilities)

## Further Notes

- **Model compatibility**: Always verify the target model's feature support. Reasoner models (r1, reasoner) reject tools and structured output. The library guards this at construction time but cannot guard against API-side changes.
- **Tool execution timeout**: The `$timeout` parameter on `Tool` is stored but timeouts during tool execution are not enforced by the library — the caller must implement timeout handling within their closure. The `ToolTimeoutException` class exists for this purpose but is thrown by user code, not the library.
- **SSE parsing robustness**: The `HttpClient.streamRequest()` buffers partial chunks and handles multi-line SSE events. Incomplete `data:` lines at chunk boundaries are reassembled. The `[DONE]` sentinel terminates the stream.
- **API key security**: The `apiKey` is stored as a plain string property on `Config`. Users should inject it from environment variables or a secrets manager, never commit it to version control.
- **Package ecosystem position**: This library competes with `deepseek-php/php-client` (official DeepSeek client) but differentiates via the Agent/tool-calling layer, schema builder, hook lifecycle, and zero-dependency posture.

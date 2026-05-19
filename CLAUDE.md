# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

`deepseek-wan` is a PHP agent framework for the DeepSeek API. It provides text generation, streaming output, tool calling, structured output, and FIM (fill-in-the-middle) completion.

## Tech Stack

- **Language:** PHP
- **Package manager:** Composer

## Architecture (planned)

The framework follows an agent-oriented architecture:

- **Agents** — autonomous or semi-autonomous units that use the DeepSeek API to perform tasks, optionally with tool-calling capabilities.
- **Tool calling** — agents can invoke user-defined tools/functions, with the framework handling the tool-call lifecycle (request, execution, response).
- **Streaming** — all API responses support streaming output via Server-Sent Events (SSE) or equivalent.
- **Structured output** — agents can return JSON conforming to a user-provided schema.
- **FIM completion** — fill-in-the-middle (code infill) completions for editor integrations.

## Development Environment

PHP 开发环境运行在 Docker 容器中。所有 PHP 命令（composer、php）需要在容器内执行。

- **容器 ID**: `5d90daa1971e212376523dc74d3ad3d7f55903ef5eaa2e446f7d85488bb6fa09`
- **执行命令格式**: `docker exec -it <container-id> php <script>` 或 `docker exec -it <container-id> composer <command>`

## License

Apache 2.0 — see [LICENSE](LICENSE).

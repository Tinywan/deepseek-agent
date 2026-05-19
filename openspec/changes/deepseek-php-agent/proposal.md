## Why

DeepSeek API 提供了强大的对话补全、FIM 代码补全、工具调用和结构化输出能力，但 PHP 生态中缺乏一个完整封装这些能力的 Agent 框架。现有的 `webman-php/openai` 仅提供了底层的 HTTP 通信层（Chat 补全），没有覆盖 Agent 抽象、工具编排、FIM 端点、流式事件处理等高级功能。需要一个类似 TypeScript 生态中 `deepseek-kit` 的 PHP 框架，让 PHP 开发者能快速构建基于 DeepSeek 的 AI Agent 应用。

## What Changes

- 新增 `DeepSeekClient` — 封装 DeepSeek API 的 Chat 补全、FIM 补全、模型列表、余额查询，基于 `webman-php/openai` 扩展
- 新增 `Tool` 系统 — 工具定义、参数校验、超时控制、重试策略（指数退避 + 抖动）
- 新增流式事件系统 — `text-delta`、`reasoning-delta`、`tool-call`、`step`、`finish` 五种事件类型
- 新增结构化输出 — JSON Schema 构建器，支持 DeepSeek 的 JSON Output 功能
- 新增 Agent 抽象 — 将模型、工具集、输出 Schema 打包为可复用的 Agent 对象
- 新增 Hook 系统 — `beforeStep`、`afterStep`、`onError` 三个生命周期钩子
- 新增配置管理 — 不可变配置对象，支持 `withConfig()` 继承式模型切换
- 新增 `composer.json` 依赖声明，依赖 `webman/openai`、PHP 8.1+

## Capabilities

### New Capabilities
- `deepseek-client`: DeepSeek API 客户端层，提供 Chat 补全、FIM 补全、模型列表、余额查询
- `tool-system`: 工具定义与调用系统，支持参数校验、超时控制、自动重试
- `streaming-events`: 流式响应事件系统，区分文本增量、推理增量、工具调用、步骤完成、全部完成
- `structured-output`: 结构化 JSON 输出，基于 JSON Schema 构建器
- `agent-orchestration`: Agent 编排层，整合模型、工具、输出格式，管理多步骤对话和工具调用循环
- `hook-system`: 生命周期钩子，支持 beforeStep / afterStep / onError
- `config-management`: 配置管理，不可变配置对象，支持继承式覆盖

### Modified Capabilities
<!-- No existing capabilities to modify. This is a new project. -->

## Impact

- **Dependencies**: 新增 `php: >=8.1`、`webman/openai: ^3.0`、`workerman/workerman: ^5.1`
- **Project structure**: 在空仓库中从零搭建 `src/` 目录，按 Client / Tool / Generation / Agent / Hook / Schema / Config 分层
- **Namespace**: `DeepSeek\Wan` 或类似命名空间，通过 Composer autoload 加载
- **License**: 沿用 Apache 2.0

# Agent Orchestration

Agent 编排层，将模型、工具集和输出 Schema 打包为可复用的 Agent 对象，管理多步骤对话和工具调用循环。

## ADDED Requirements

### Requirement: Agent creation

系统 SHALL 支持创建 Agent，捆绑模型配置、工具列表和可选的输出 Schema。

#### Scenario: Create agent with model and tools

- **WHEN** 用户调用 `createAgent()` 传入 `model`（模型名称）、`tools`（工具对象数组）
- **THEN** 系统返回一个 Agent 对象，内部持有模型配置和工具注册表

#### Scenario: Create agent with output schema

- **WHEN** 用户额外传入 `output` Schema
- **THEN** Agent 在每次生成时自动附加结构化输出约束

### Requirement: Agent generate (non-streaming)

系统 SHALL 提供 `generate()` 方法，执行完整的非流式对话，自动处理工具调用循环直到模型返回最终文本。

#### Scenario: Simple generation without tools

- **WHEN** 用户调用 `agent->generate(messages: [...])` 且 Agent 无工具
- **THEN** 系统返回单次补全的结果对象，包含 `text`（内容）、`finishReason` 和 `usage`

#### Scenario: Generation with tool calling loop

- **WHEN** 模型返回 `finish_reason: 'tool_calls'`
- **THEN** 系统执行工具调用并将结果追加到消息历史，自动发起后续补全请求，直到模型返回 `finish_reason: 'stop'` 或达到最大步数限制

#### Scenario: Maximum steps exceeded

- **WHEN** 工具调用循环超过配置的 `maxSteps` 限制
- **THEN** 系统抛出 `MaxStepsExceededException`，包含已完成的所有步骤信息

### Requirement: Agent stream (streaming)

系统 SHALL 提供 `stream()` 方法，以 Generator 形式产出所有中间步骤的流式事件（text-delta、reasoning-delta、tool-call、step、finish）。

#### Scenario: Streaming with tool calls

- **WHEN** 用户在带工具的 Agent 上调用 `agent->stream(messages: [...])`
- **THEN** 系统通过 Generator 依次产出每个步骤的 `TextDelta`/`ReasoningDelta`/`ToolCall` 事件，然后产出 `Step` 事件标记步骤结束，最终产出 `Finish` 事件

### Requirement: Tool call lifecycle

系统 SHALL 在多步骤对话中自动管理工具调用生命周期：发送请求 → 检测 tool_calls → 执行工具 → 追加结果 → 继续对话。

#### Scenario: Full tool call lifecycle

- **WHEN** Agent 发送包含 `tools` 参数的请求且模型返回工具调用
- **THEN** 系统依次：1) 提取 tool_calls，2) 根据 `tool_choice` 匹配工具，3) 校验参数，4) 执行工具函数，5) 将结果以 `role: 'tool'` 消息追加，6) 发起继续请求

# Streaming Events

流式响应事件系统，将 DeepSeek API 的 SSE 流解析为类型化的事件对象。

## ADDED Requirements

### Requirement: Text delta event

系统 SHALL 在流式响应中产出 `text-delta` 事件，携带增量文本内容。

#### Scenario: Regular text streaming

- **WHEN** 流式响应中 `choices[0].delta.content` 包含文本增量
- **THEN** 系统产出 `StreamEventType::TextDelta` 事件，`delta` 属性包含该增量文本

### Requirement: Reasoning delta event

系统 SHALL 在流式响应中产出 `reasoning-delta` 事件，携带 deepseek-reasoner 模型的推理过程内容。

#### Scenario: Reasoning model response

- **WHEN** 流式响应中包含 `choices[0].delta.reasoning_content` 字段
- **THEN** 系统产出 `StreamEventType::ReasoningDelta` 事件，`delta` 属性包含推理增量文本

### Requirement: Tool call event

系统 SHALL 在流式响应中产出 `tool-call` 事件，携带工具调用的增量信息。

#### Scenario: Tool call in stream

- **WHEN** 流式响应中 `choices[0].delta.tool_calls` 包含工具调用数据
- **THEN** 系统产出 `StreamEventType::ToolCall` 事件，包含 `callId`、`name`、`arguments` 增量

### Requirement: Step finish event

系统 SHALL 在每个完成步骤时产出 `step` 事件，包含该步骤的元数据。

#### Scenario: Step completion

- **WHEN** 一个工具调用循环的回合完成（如 `finish_reason: 'tool_calls'` 或中间步骤结束）
- **THEN** 系统产出 `StreamEventType::Step` 事件，包含 `stepNumber`、`finishReason`、`usage` 等信息

### Requirement: Stream finish event

系统 SHALL 在流式响应结束时产出 `finish` 事件，包含完整的 Token 用量和最终状态。

#### Scenario: Stream completion

- **WHEN** 流式响应以 `finish_reason: 'stop'` 结束
- **THEN** 系统产出 `StreamEventType::Finish` 事件，包含 `usage`（prompt_tokens、completion_tokens、total_tokens）、`finishReason` 和最终的完整消息

### Requirement: Event enum typing

系统 SHALL 使用 PHP 8.1 enum 定义事件类型，每个事件类型对应一个特定的 DTO 类。

#### Scenario: Type-safe event handling

- **WHEN** 用户使用 `match($event->type)` 对事件进行模式匹配
- **THEN** IDE 能够自动补全所有事件类型分支，每个分支获得对应的 DTO 类型提示

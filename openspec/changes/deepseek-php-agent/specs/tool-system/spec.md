# Tool System

工具定义与调用系统，支持参数校验、超时控制和自动重试。

## ADDED Requirements

### Requirement: Tool definition

系统 SHALL 允许用户定义工具，包含名称、描述、参数 Schema 和执行函数。

#### Scenario: Define a simple tool

- **WHEN** 用户创建工具时传入 `name`、`description`、`schema`（JSON Schema 格式）和 `execute` 闭包
- **THEN** 系统返回一个 Tool 对象，可被传递给生成函数或 Agent

#### Scenario: Define tool with optional settings

- **WHEN** 用户额外指定 `timeout`（毫秒）、`retries`（次数）、`strict`（严格模式）、`required`（是否强制调用）
- **THEN** 工具对象存储这些配置，影响后续执行行为

### Requirement: Tool execution

系统 SHALL 在模型返回 `tool_calls` 时，按定义执行工具并将结果追加到消息历史。

#### Scenario: Single tool call execution

- **WHEN** 模型响应包含一个 `tool_calls` 条目
- **THEN** 系统执行对应工具的 `execute` 函数，将返回值以 `role: 'tool'` 消息格式追加到消息数组

#### Scenario: Multiple tool calls in one response

- **WHEN** 模型响应包含多个 `tool_calls` 条目
- **THEN** 系统按顺序执行所有工具，每个工具结果作为独立消息追加

### Requirement: Tool timeout

系统 SHALL 支持工具级别的超时控制，超时后标记失败并触发重试逻辑。

#### Scenario: Tool execution exceeds timeout

- **WHEN** 工具 `execute` 函数执行时间超过 `timeout` 配置值
- **THEN** 系统中止执行并抛出 `ToolTimeoutException`，计入一次重试

### Requirement: Retry strategy

系统 SHALL 在工具执行失败时使用指数退避加随机抖动进行重试。

#### Scenario: Automatic retry on failure

- **WHEN** 工具执行抛出异常且 `retries` > 0
- **THEN** 系统等待 `baseDelay * 2^attempt + randomJitter` 毫秒后重试执行

#### Scenario: Retries exhausted

- **WHEN** 工具执行失败次数超过 `retries` 配置值
- **THEN** 系统抛出 `ToolExecutionException`，包含所有重试的错误信息

### Requirement: Parameter validation

系统 SHALL 在调用工具前校验传入参数是否匹配定义的 Schema。

#### Scenario: Valid parameters pass validation

- **WHEN** 模型传入的参数匹配工具 Schema 定义
- **THEN** 系统正常执行工具函数

#### Scenario: Invalid parameters fail validation

- **WHEN** 模型传入的参数不符合工具 Schema（如缺少必填字段、类型错误）
- **THEN** 系统返回错误信息给模型，由模型修正参数后重新调用

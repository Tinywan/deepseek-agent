# DeepSeek Client

DeepSeek API 客户端层，封装 Chat 补全、FIM 补全、模型列表和余额查询。

## ADDED Requirements

### Requirement: Chat completion (non-streaming)

系统 SHALL 支持向 DeepSeek Chat API 发送非流式对话请求，返回完整的补全结果。

#### Scenario: Basic chat completion

- **WHEN** 用户调用 `Chat::completions()` 传入 `model` 和 `messages` 参数
- **THEN** 系统返回包含 `choices` 数组的完整响应，其中包含 `message` 对象

#### Scenario: Chat with system message

- **WHEN** 用户传入包含 `role: 'system'` 消息的 `messages` 数组
- **THEN** 系统将系统消息作为对话上下文发送，模型行为受其约束

#### Scenario: API error handling

- **WHEN** API 返回错误响应（如 401 未授权、429 限流、500 服务错误）
- **THEN** 系统抛出自定义的 `DeepSeekException`，包含错误码和原始错误信息

### Requirement: Chat streaming completion

系统 SHALL 支持流式对话请求，通过 Generator 逐块产出响应增量。

#### Scenario: Streaming chat with Generator

- **WHEN** 用户调用带 `stream: true` 参数的补全请求
- **THEN** 系统返回 `\Generator` 实例，每次迭代产出包含 `choices[0].delta` 的一帧数据

#### Scenario: Reasoning content in stream

- **WHEN** 使用 deepseek-reasoner 模型且流式响应中包含 `reasoning_content` 字段
- **THEN** 系统原样透传该字段，不做截断或过滤

### Requirement: FIM completion

系统 SHALL 支持 Fill-in-the-Middle 代码补全，通过独立的 `Fim` 客户端访问 `/beta/completions` 端点。

#### Scenario: FIM with prompt and suffix

- **WHEN** 用户调用 `Fim::completions()` 传入 `model`、`prompt`（前缀）、`suffix`（后缀）和 `max_tokens`
- **THEN** 系统返回在 `prompt` 和 `suffix` 之间填充的代码补全结果

#### Scenario: FIM streaming

- **WHEN** 用户调用 FIM 补全并设置 `stream: true`
- **THEN** 系统通过 Generator 逐 chunk 返回补全增量

### Requirement: Model listing

系统 SHALL 支持查询可用模型列表。

#### Scenario: List available models

- **WHEN** 用户调用 `Model::list()`
- **THEN** 系统返回包含 `data` 数组的响应，每项包含 `id` 字段

### Requirement: Account balance query

系统 SHALL 支持查询 DeepSeek 账户余额。

#### Scenario: Query balance

- **WHEN** 用户调用 `Model::balance()`
- **THEN** 系统返回余额信息响应

### Requirement: Client configuration

系统 SHALL 支持通过 `Config` 对象配置 API key、base URL 和其他选项。

#### Scenario: Configure with API key and custom base URL

- **WHEN** 用户创建配置对象时传入 `apiKey` 和 `baseUrl`
- **THEN** 所有客户端使用该配置发起 API 请求

#### Scenario: Default base URL

- **WHEN** 用户未指定 base URL
- **THEN** 系统使用 `https://api.deepseek.com` 作为默认值

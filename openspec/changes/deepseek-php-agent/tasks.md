## 1. 项目搭建

- [ ] 1.1 创建 `composer.json`，声明 `deepseek/wan` 包名、PHP 8.1+、依赖 `webman/openai: ^3.0`
- [ ] 1.2 创建 `src/` 目录结构和 PSR-4 autoload 映射 (`DeepSeek\Wan\` → `src/`)
- [ ] 1.3 创建基础异常类 `DeepSeekException`

## 2. 配置管理 (Config)

- [ ] 2.1 实现 `Config` 不可变 readonly 类，包含 `apiKey`、`baseUrl`、`model`、`temperature`、`maxTokens` 等属性
- [ ] 2.2 实现 `withConfig(array $overrides): Config` 方法，返回合并后的新实例
- [ ] 2.3 实现必填字段校验（apiKey），温度范围校验（0-2），校验失败抛 `InvalidConfigException`

## 3. 客户端层 — Chat

- [ ] 3.1 实现 `Chat` 类，构造函数接收 `Config`，内部封装 `Webman\Openai\Chat`
- [ ] 3.2 实现 `completions(array $params): array` 非流式对话方法，传入 messages 等参数
- [ ] 3.3 实现 `completionsStream(array $params): \Generator` 流式对话方法，返回原始 chunk Generator
- [ ] 3.4 处理 `reasoning_content` 在流式 chunk 中的透传

## 4. 客户端层 — FIM

- [ ] 4.1 实现 `Fim` 类，构造函数接收 `Config`，直接通过 HTTP 请求 `/beta/completions` 端点
- [ ] 4.2 实现 `completions(array $params): array` 非流式 FIM，接收 `prompt`、`suffix`、`maxTokens` 等
- [ ] 4.3 实现 `completionsStream(array $params): \Generator` 流式 FIM

## 5. 客户端层 — Model

- [ ] 5.1 实现 `Model` 类，构造函数接收 `Config`
- [ ] 5.2 实现 `list(): array` 获取可用模型列表
- [ ] 5.3 实现 `balance(): array` 查询账户余额

## 6. 流式事件系统

- [ ] 6.1 定义 `StreamEventType` 枚举（TextDelta、ReasoningDelta、ToolCall、Step、Finish）
- [ ] 6.2 创建 `StreamEvent` 基类及相关 DTO（`TextDeltaEvent`、`ReasoningDeltaEvent`、`ToolCallEvent`、`StepEvent`、`FinishEvent`）
- [ ] 6.3 每个事件 DTO 携带类型特定数据（delta、callId、stepNumber、usage、finishReason 等）

## 7. Schema Builder（结构化输出）

- [ ] 7.1 实现 `Schema` 基础类，提供静态工厂方法：`string()`、`number()`、`integer()`、`boolean()`
- [ ] 7.2 实现 `Schema::object(array $properties)` 支持嵌套属性和 `required()` 链式调用
- [ ] 7.3 实现 `Schema::array(Schema $items)` 支持数组类型
- [ ] 7.4 实现 `Schema::enum(array $values)` 支持枚举约束
- [ ] 7.5 实现 `toArray(): array` 编译为 JSON Schema 标准格式
- [ ] 7.6 属性支持 `describe()`、`required()` 链式调用

## 8. 工具系统

- [ ] 8.1 实现 `Tool` 类，包含 `name`、`description`、`schema`（Schema 对象或数组）、`execute`（闭包）、`timeout`、`retries`、`strict`、`required` 属性
- [ ] 8.2 实现 `tool()` 工厂函数，返回 Tool 实例
- [ ] 8.3 实现 `ToolCaller` 类，负责执行工具（参数校验、超时控制、异常捕获）
- [ ] 8.4 实现指数退避 + 抖动重试逻辑：`delay = min(baseDelay * 2^attempt + randomJitter, maxDelay)`
- [ ] 8.5 实现 `ToolTimeoutException` 和 `ToolExecutionException` 异常类

## 9. Hook 系统

- [ ] 9.1 实现 `HookContext` 类，包含 `step`、`messages`、`config` 属性
- [ ] 9.2 实现 `StepResult` 类，包含 `step`、`type`、`usage`、`finishReason` 属性
- [ ] 9.3 实现 `HookError` 类，包含 `step`、`message`、`exception` 属性
- [ ] 9.4 实现 `Hooks` 管理类，支持注册 `beforeStep(callable)`、`afterStep(callable)`、`onError(callable)` 回调

## 10. 生成层 — TextGenerator

- [ ] 10.1 实现 `TextGenerator` 类，整合 Chat 客户端、工具调用、Hook 执行
- [ ] 10.2 实现工具调用循环：检测 `finish_reason: 'tool_calls'` → 执行工具 → 追加结果 → 继续请求，直到 `stop` 或达到 `maxSteps`
- [ ] 10.3 实现 `generateText(array $params): GenerateTextResult` 函数，返回包含 `text`、`finishReason`、`usage`、`steps` 的结果对象
- [ ] 10.4 实现 `MaxStepsExceededException`，超过最大步骤时抛出
- [ ] 10.5 在每次步骤前后触发 beforeStep / afterStep 钩子，错误时触发 onError 钩子

## 11. 生成层 — StreamGenerator

- [ ] 11.1 实现 `StreamGenerator` 类，支持流式生成 + 工具调用循环
- [ ] 11.2 实现 `generateStream(array $params): \Generator` 函数，产出 `StreamEvent` 对象序列
- [ ] 11.3 流式过程中正确聚合 tool_calls delta（区分 index），完整 tool_calls 到达后产出 `ToolCall` 事件
- [ ] 11.4 每个步骤完成时产出 `Step` 事件，全部完成时产出 `Finish` 事件（含 usage）

## 12. Agent 层

- [ ] 12.1 实现 `Agent` 类，构造函数接收 `Config`、`tools`（可选）、`output` Schema（可选）
- [ ] 12.2 实现 `generate(array $messages): GenerateTextResult` 方法，委托给 TextGenerator
- [ ] 12.3 实现 `stream(array $messages): \Generator` 方法，委托给 StreamGenerator
- [ ] 12.4 检测模型能力：R1/reasoner 模型禁用 function calling / JSON output / FIM，抛出明确异常
- [ ] 12.5 实现 `createAgent()` 工厂函数

## 13. 入口与门面

- [ ] 13.1 创建 `src/functions.php`，导出 `generateText()`、`generateStream()`、`createAgent()`、`tool()`、`fim()` 顶层函数
- [ ] 13.2 创建 `src/index.php` 或通过 Composer autoload files 自动加载函数文件

## 14. 示例与验证

- [ ] 14.1 编写 `examples/basic-chat.php` 基础对话示例
- [ ] 14.2 编写 `examples/streaming.php` 流式输出示例
- [ ] 14.3 编写 `examples/tool-calling.php` 工具调用示例（如天气查询）
- [ ] 14.4 编写 `examples/structured-output.php` 结构化输出示例
- [ ] 14.5 编写 `examples/fim.php` FIM 代码补全示例
- [ ] 14.6 验证所有示例可运行

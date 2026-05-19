## Context

从零构建一个 PHP Agent 框架，封装 DeepSeek API 的全部能力。底层 HTTP 通信依赖 `webman-php/openai`（已处理 Chat 补全和流式 SSE 解析），框架在其之上构建 Agent、工具、FIM、结构化输出等高级抽象。参考 TypeScript 生态中的 `deepseek-kit` 架构，适配 PHP 语言特性。

**约束条件：**
- PHP 8.1+（enum、readonly、match、fibers 可用）
- Workerman 5.1+ 运行时（协程模式需要 Fiber）
- webman/openai ^3.0 作为 HTTP 传输层
- 命名空间 `DeepSeek\Wan`

## Goals / Non-Goals

**Goals:**
- 提供三层架构（Client → Generation → Agent），让用户根据需要选择抽象层级
- 完整覆盖 DeepSeek API: Chat、FIM、Models、Balance
- 工具调用支持：定义、校验、超时、指数退避重试
- 流式输出支持五种事件类型（text-delta、reasoning-delta、tool-call、step、finish）
- 结构化 JSON 输出，基于 Schema Builder 编译为 JSON Schema
- 不可变配置对象，支持继承式覆盖以切换模型
- beforeStep / afterStep / onError Hook 生命周期

**Non-Goals:**
- 不提供 Prompt 模板系统（与 Agent 无关，留给用户自行管理）
- 不内置 MCP (Model Context Protocol) 支持（未来可扩展）
- 不提供 Web UI 或 CLI 工具
- 不处理 OpenAPI / Swagger 以外的 Schema 格式
- 不实现向量存储、RAG 等高级功能
- 不兼容非 workerman 的传统 PHP-FPM 运行模式（webman/openai 本身依赖 workerman）

## Decisions

### 1. 三层架构而非单体

**决定**: Client → Generation → Agent 三层分离。

**理由**: 不同用户场景需要不同抽象级别：简单脚本直接用 `generateText()`，复杂应用用 `DeepSeekAgent` 管理对话状态。三层分离允许渐进增强，每层可独立测试。

**替代方案**: 全部塞进一个 Agent 类 → 拒绝，因为失去了低层级 API 的直接访问能力。

### 2. Schema Builder 自建而非第三方库

**决定**: 自建轻量 `Schema` 类，提供 `Schema::object([...])` → `->toArray()` 链式 API，编译为标准 JSON Schema 数组。

**理由**:
- PHP 生态无 Zod 等价物
- Symfony/Validator 是验证库，不生成 JSON Schema
- `opis/json-schema` 是验证器而非构建器
- 自建 200 行以内即可覆盖 `object`、`string`、`number`、`integer`、`boolean`、`array`、`enum` 等类型

**替代方案**: 直接用原始 JSON Schema 关联数组 → 保留作为底层能力，但 Schema Builder 作为推荐的便捷 API。

### 3. FIM 客户端独立于 Chat

**决定**: 独立 `Fim` 类，与 `Chat` 平级，而非扩展 `Chat`。

**理由**: FIM 端点 (`POST /beta/completions`) 与 Chat (`POST /chat/completions`) 在参数结构上完全不同——FIM 用 `prompt`+`suffix` 而非 `messages` 数组。继承 Chat 会导致接口污染。两者共享 HTTP 配置（API key、baseURL）通过 `Config` 对象注入。

### 4. Hook 采用闭包而非事件派发器

**决定**: Hook 定义为 `callable` 数组，在执行流中直接调用。

**理由**:
- deepseek-kit 用函数回调，PHP 的 callable 是自然等价物
- PSR-14 EventDispatcher 引入额外依赖和复杂性
- Agent 框架的 Hook 场景简单（3 个生命周期），不需要完整的发布/订阅系统
- 闭包可以直接修改上下文（引用传递），实现 beforeStep 的消息/配置修改

**替代方案**: PSR-14 → 拒绝，过度设计。接口 + 实现类 → 备选，但闭包对用户更友好。

### 5. 强依赖 workerman 协程模式

**决定**: 框架要求 workerman 环境，不提供同步 fallback。

**理由**: webman/openai 已要求 workerman。流式 API 在 PHP 中天然适合 Generator + Fiber 模式。为 PHP-FPM 提供同步兼容层需要大量垫片代码，收益有限（webman 生态已有相当用户群）。

### 6. 流式事件使用 enum + DTO

**决定**: PHP 8.1 enum 定义 `StreamEventType`，配合 `StreamEvent` DTO（readonly class）。

**理由**: 类型安全的模式匹配（`match` 表达式），IDE 自动补全。Generator 自然映射异步流。每个事件 DTO 携带类型特定的数据（如 `TextDelta->delta`、`ToolCall->callId`、`Finish->usage`）。

### 7. 工具重试策略：指数退避 + 抖动

**决定**: 工具调用失败时使用 `delay = min(baseDelay * 2^attempt + randomJitter, maxDelay)` 计算重试间隔。

**理由**: 与 deepseek-kit 一致。避免工具服务在瞬时故障时雪崩。每个工具可独立配置 `timeout` 和 `retries`。

### 8. 命名空间与包名

**决定**: 
- Composer 包名: `deepseek/wan`
- 命名空间: `DeepSeek\Wan`
- 根目录: `src/`

**理由**: 项目名为 `deepseek-wan`，`wan` 取 "万" 之意，简洁。命名空间与包名解耦（`-` vs `\`），遵循 Composer PSR-4 规范。

## Risks / Trade-offs

- **[风险] webman-php/openai API 变化**: webman/openai 仍在大版本迭代（已到 v3），API 可能有 breaking change → 通过适配层封装 `Chat` 的直接调用，框架代码不直接依赖 webman/openai 的具体类名。
- **[风险] DeepSeek Beta API 不稳定**: FIM 和 JSON Output 处于 Beta 阶段，可能变更 → 在 Fim 客户端中做好错误处理，文档中标注 Beta 状态。
- **[风险] deepseek-reasoner (R1) 功能受限**: R1 不支持 function calling、JSON output、FIM → 在 Agent 层检测模型能力，对不支持的功能抛明确异常而非静默失败。
- **[取舍] 强依赖 workerman 限制用户群**: 放弃了 PHP-FPM / Swoole / ReactPHP 用户 → 文档中明确声明运行时要求，减少支持负担。

## Open Questions

- FIM 端点的参数后缀 `suffix` 是否与新版本 DeepSeek API 完全兼容？需在实现时验证官方文档最新版本。
- 是否需要内置对话历史管理（messages 数组管理）？还是留给 Agent 上层处理？

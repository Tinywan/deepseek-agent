# Structured Output

结构化 JSON 输出，通过 Schema Builder 构建 JSON Schema 并约束模型输出格式。

## ADDED Requirements

### Requirement: Schema builder for basic types

系统 SHALL 提供 Schema Builder 构建 JSON Schema，支持基本类型（string、number、integer、boolean）。

#### Scenario: Build a string schema

- **WHEN** 用户调用 `Schema::string()` 并加上描述
- **THEN** 系统编译输出 `{ "type": "string", "description": "..." }`

#### Scenario: Build a number schema

- **WHEN** 用户调用 `Schema::number()` 或 `Schema::integer()`
- **THEN** 系统编译输出对应类型的 JSON Schema，`number` 或 `integer`

### Requirement: Object schema with properties

系统 SHALL 支持构建 object 类型 Schema，包含嵌套属性和必填字段标记。

#### Scenario: Object with required fields

- **WHEN** 用户构建 `Schema::object(['name' => Schema::string()->required(), 'age' => Schema::integer()])`
- **THEN** 系统编译输出包含 `type: 'object'`、`properties` 和 `required: ['name']` 的 JSON Schema

#### Scenario: Nested object

- **WHEN** 用户构建包含嵌套 object 属性的 Schema
- **THEN** 系统递归编译嵌套对象，每层独立生成 `properties` 和 `required`

### Requirement: Array and enum schemas

系统 SHALL 支持 `array` 和 `enum` 类型。

#### Scenario: Array of strings

- **WHEN** 用户调用 `Schema::array(Schema::string())`
- **THEN** 系统编译输出 `{ "type": "array", "items": { "type": "string" } }`

#### Scenario: Enum with allowed values

- **WHEN** 用户调用 `Schema::enum(['red', 'green', 'blue'])`
- **THEN** 系统编译输出 `{ "type": "string", "enum": ["red", "green", "blue"] }`

### Requirement: Schema compilation

系统 SHALL 提供 `toArray()` 方法将 Schema 对象编译为标准 JSON Schema 关联数组。

#### Scenario: Compile to array for API request

- **WHEN** 用户调用 Schema 对象的 `toArray()`
- **THEN** 系统返回可直接嵌入 API 请求 `response_format` 或 `tools[].parameters` 的标准 JSON Schema 数组

### Requirement: Integration with generation

系统 SHALL 支持将 Schema 传递给生成函数，启用 DeepSeek 的 JSON Output 模式。

#### Scenario: Structured output generation

- **WHEN** 用户调用 `generateText()` 时传入 `output: ['schema' => $schema->toArray()]`
- **THEN** 系统向 API 发送 `response_format` 参数，模型返回符合 Schema 的 JSON 字符串

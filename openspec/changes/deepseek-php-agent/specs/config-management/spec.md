# Config Management

配置管理，不可变配置对象，支持继承式覆盖。

## ADDED Requirements

### Requirement: Configuration object

系统 SHALL 提供不可变的 Config 对象，存储 API key、base URL、模型名称、温度、max_tokens 等所有框架配置。

#### Scenario: Create config with required values

- **WHEN** 用户创建 Config 对象时至少传入 `apiKey` 和 `model`
- **THEN** 系统返回一个不可变的 Config 实例，其余字段使用默认值

#### Scenario: Readonly properties

- **WHEN** 用户尝试直接修改 Config 对象的属性
- **THEN** 系统通过 PHP readonly 类属性阻止修改，确保配置不可变

### Requirement: Config inheritance

系统 SHALL 提供 `withConfig()` 方法，返回新 Config 实例并合并覆盖参数，原有实例不受影响。

#### Scenario: Switch model with inheritance

- **WHEN** 用户在 Config A 上调用 `withConfig(['model' => 'deepseek-chat'])`
- **THEN** 系统返回新 Config B，B 的 `model` 为新值，其余字段继承自 A，A 保持原样

#### Scenario: Override multiple fields

- **WHEN** 用户传入多个字段的覆盖值
- **THEN** 新 Config 实例中指定的字段被覆盖，未指定的字段保持原值

### Requirement: Default values

系统 SHALL 为可选配置项提供合理的默认值。

#### Scenario: Default temperature and max tokens

- **WHEN** 用户未指定 `temperature` 和 `maxTokens`
- **THEN** `temperature` 默认为 `1.0`，`maxTokens` 默认为 `2048`

#### Scenario: Default base URL

- **WHEN** 用户未指定 `baseUrl`
- **THEN** 默认为 `https://api.deepseek.com`

### Requirement: Config validation

系统 SHALL 在创建 Config 时校验必填字段是否存在。

#### Scenario: Missing API key

- **WHEN** 用户创建 Config 时未提供 `apiKey`
- **THEN** 系统抛出 `InvalidConfigException`，说明缺少必填的 `apiKey` 字段

#### Scenario: Invalid temperature range

- **WHEN** 用户指定 `temperature` 超出 DeepSeek API 允许范围（0-2）
- **THEN** 系统抛出 `InvalidConfigException`

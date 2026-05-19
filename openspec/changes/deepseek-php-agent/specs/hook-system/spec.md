# Hook System

生命周期钩子系统，支持在生成流程的各个阶段注入自定义逻辑。

## ADDED Requirements

### Requirement: Before step hook

系统 SHALL 在每次生成步骤之前调用 `beforeStep` 钩子，允许用户修改消息列表和生成配置。

#### Scenario: Modify messages before step

- **WHEN** 用户注册了 `beforeStep` 回调且即将开始新步骤
- **THEN** 系统调用该回调，传入 `HookContext`（包含 `step` 编号和当前 `messages`），回调可返回修改后的消息数组和配置

#### Scenario: Adjust temperature per step

- **WHEN** `beforeStep` 回调返回包含 `config` 键的数组（如 `['temperature' => 0.5]`）
- **THEN** 系统在本次步骤中使用修改后的配置参数

### Requirement: After step hook

系统 SHALL 在每次生成步骤完成后调用 `afterStep` 钩子，传递步骤结果。

#### Scenario: Log step result

- **WHEN** 一个生成步骤完成且用户注册了 `afterStep` 回调
- **THEN** 系统调用该回调，传入 `StepResult`（包含 `step` 编号、`type`（'text' 或 'tool_calls'）、`usage`）

### Requirement: Error hook

系统 SHALL 在生成过程中发生错误时调用 `onError` 钩子，允许用户决定是否重试、跳过或终止。

#### Scenario: Handle and suppress error

- **WHEN** 某步骤发生错误且用户注册了 `onError` 回调
- **THEN** 系统调用该回调，传入 `HookError`（包含 `step` 编号和 `message`），回调返回的错误信息将作为最终错误抛出或用于重试判断

#### Scenario: Error hook rethrows

- **WHEN** `onError` 回调返回的错误对象被系统处理
- **THEN** 若错误不可恢复，系统中止生成流程并抛出异常

### Requirement: Hook context object

系统 SHALL 为每个钩子提供 `HookContext` 对象，包含当前步骤的上下文信息。

#### Scenario: Access hook context

- **WHEN** 钩子被调用
- **THEN** `HookContext` 对象至少包含 `step`（当前步骤编号）、`messages`（当前消息数组）、`config`（当前生成配置）

# deepseek-wan

PHP Agent 框架，封装 DeepSeek API 全部能力：文本生成、流式输出、工具调用、结构化输出、FIM 代码补全。

## 安装

```bash
composer require deepseek/wan
```

## 快速开始

```php
use DeepSeek\Wan\Config;
use function DeepSeek\Wan\generateText;

$config = new Config([
    'apiKey' => getenv('DEEPSEEK_API_KEY'),
    'model'  => 'deepseek-chat',
]);

$result = generateText($config, [
    'messages' => [
        ['role' => 'user', 'content' => '你好，请介绍一下你自己。'],
    ],
]);

echo $result->text;       // 模型回复内容
echo $result->usage['total_tokens']; // Token 用量
```

## 功能

### 流式输出

```php
use function DeepSeek\Wan\generateStream;

$stream = generateStream($config, [
    'messages' => [['role' => 'user', 'content' => '讲个故事']],
]);

foreach ($stream as $event) {
    if ($event instanceof \DeepSeek\Wan\TextDeltaEvent) {
        echo $event->delta;  // 逐字输出
    }
}
```

支持五种事件类型：`TextDeltaEvent`、`ReasoningDeltaEvent`、`ToolCallEvent`、`StepEvent`、`FinishEvent`。

### 工具调用

```php
use function DeepSeek\Wan\createAgent;
use function DeepSeek\Wan\createTool;
use DeepSeek\Wan\Schema;

$weatherTool = createTool(
    name: 'get_weather',
    description: '查询指定城市的天气',
    schema: Schema::object([
        'city' => Schema::string()->describe('城市名称')->required(),
    ]),
    execute: function (array $args): string {
        return json_encode(['city' => $args['city'], 'temp' => 22, 'condition' => '晴']);
    },
    retries: 2,    // 失败重试次数
    timeout: 30000, // 超时（毫秒）
);

$agent = createAgent($config, tools: [$weatherTool]);

$result = $agent->generate([
    ['role' => 'user', 'content' => '北京今天天气怎么样？'],
]);
```

- 自动工具调用循环：检测 `tool_calls` → 执行工具 → 追加结果 → 继续请求
- 指数退避重试：`delay = min(100 * 2^attempt + random(0,100), 10000)` ms

### 结构化输出

```php
use DeepSeek\Wan\Schema;
use function DeepSeek\Wan\createAgent;

$reviewSchema = Schema::object([
    'title'  => Schema::string()->describe('书名')->required(),
    'author' => Schema::string()->describe('作者')->required(),
    'rating' => Schema::number()->describe('评分 1.0-5.0')->required(),
    'genres' => Schema::array(Schema::string())->describe('类型'),
]);

$agent = createAgent($config, output: $reviewSchema);

$result = $agent->generate([
    ['role' => 'user', 'content' => '推荐《三体》并写一段书评'],
]);

$review = json_decode($result->text, true);
// $review['title'], $review['author'], $review['rating'], ...
```

Schema Builder 支持：`string()`、`number()`、`integer()`、`boolean()`、`object([])`、`array()`、`enum([])`，以及 `describe()`、`required()` 链式调用。

### FIM 代码补全

```php
use function DeepSeek\Wan\generateFim;

$result = generateFim($config, [
    'prompt' => "def fibonacci(n):\n    \"\"\"Return the nth Fibonacci number.\"\"\"\n",
    'suffix' => "\n    return result\n",
    'max_tokens' => 256,
]);

echo $result['choices'][0]['text']; // 模型补全的代码
```

### 模型列表 & 余额

```php
$model = new DeepSeek\Wan\Model($config);
$models = $model->list();      // GET /models
$balance = $model->balance();  // GET /user/balance
```

### Hook 生命周期

```php
$hooks = new DeepSeek\Wan\Hooks();

$hooks->beforeStep(function (HookContext $ctx) {
    return ['config' => ['temperature' => 0.5]]; // 每步前调整参数
});

$hooks->afterStep(function (StepResult $result) {
    echo "Step {$result->step}: {$result->finishReason}\n";
});

$hooks->onError(function (HookError $error) {
    return 'handled'; // 返回字符串则作为错误信息抛出
});

$agent = createAgent($config, hooks: $hooks);
```

## 配置项

```php
$config = new DeepSeek\Wan\Config([
    'apiKey'      => 'sk-xxx',                     // 必填
    'model'       => 'deepseek-chat',              // 默认
    'baseUrl'     => 'https://api.deepseek.com',   // 默认
    'temperature' => 1.0,                          // 默认，范围 0-2
    'maxTokens'   => 2048,                         // 默认
]);

// 不可变继承覆盖
$newConfig = $config->withConfig(['model' => 'deepseek-reasoner']);
```

## 架构

三层设计，按需选择抽象层级：

```
Client 层      Chat / Fim / Model       → 直接 HTTP 调用
Generation 层  TextGenerator / StreamGenerator → 工具循环 + Hook + 事件
Agent 层       Agent                    → 绑定 config + tools + output
```

## 要求

- PHP >= 8.1
- Composer
- webman/openai ^3.0（自动依赖 workerman 运行时）

## License

Apache 2.0 — 见 [LICENSE](LICENSE)。

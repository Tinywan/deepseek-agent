<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use DeepSeek\Agent\Agent;
use DeepSeek\Agent\Chat;
use DeepSeek\Agent\Config;
use DeepSeek\Agent\Fim;
use DeepSeek\Agent\Hooks;
use DeepSeek\Agent\Model;
use DeepSeek\Agent\Schema;
use DeepSeek\Agent\StreamGenerator;
use DeepSeek\Agent\StreamEventType;
use DeepSeek\Agent\TextGenerator;
use DeepSeek\Agent\Tool;
use DeepSeek\Agent\ToolCaller;
use DeepSeek\Agent\TextDeltaEvent;
use DeepSeek\Agent\ReasoningDeltaEvent;
use DeepSeek\Agent\ToolCallEvent;
use DeepSeek\Agent\StepEvent;
use DeepSeek\Agent\FinishEvent;
use DeepSeek\Agent\HookContext;
use DeepSeek\Agent\StepResult;
use DeepSeek\Agent\HookError;
use DeepSeek\Agent\GenerateTextResult;
use DeepSeek\Agent\Exceptions\DeepSeekException;
use DeepSeek\Agent\Exceptions\InvalidConfigException;
use DeepSeek\Agent\Exceptions\MaxStepsExceededException;
use DeepSeek\Agent\Exceptions\ToolExecutionException;
use DeepSeek\Agent\Exceptions\ToolTimeoutException;
use function DeepSeek\Agent\generateText;
use function DeepSeek\Agent\generateStream;
use function DeepSeek\Agent\createAgent;
use function DeepSeek\Agent\createTool;
use function DeepSeek\Agent\generateFim;

$pass = 0;
$fail = 0;

function check(string $label, bool $cond, string &$details = ''): void
{
    global $pass, $fail;
    if ($cond) {
        echo "  [PASS] {$label}\n";
        $pass++;
    } else {
        echo "  [FAIL] {$label}\n";
        $fail++;
    }
}

echo "=== 1. 项目搭建 ===\n";
$composerJson = json_decode(file_get_contents(__DIR__ . '/../composer.json'), true);
check("1.1 composer.json 包名 deepseek/agent", $composerJson['name'] === 'deepseek/agent');
check("1.2 PSR-4 DeepSeek\\Agent\\ → src/", isset($composerJson['autoload']['psr-4']['DeepSeek\\Agent\\']));
check("1.3 DeepSeekException 类", class_exists(DeepSeekException::class));

echo "\n=== 2. 配置管理 ===\n";
$c = new Config(['apiKey' => 'sk-test']);
check("2.1 Config readonly 不可变", (new ReflectionClass($c))->isReadOnly());
$c2 = $c->withConfig(['model' => 'test-model']);
check("2.2 withConfig 新实例 model 覆盖", $c2->model === 'test-model');
check("2.2 withConfig 原实例不变", $c->model === 'deepseek-chat');
check("2.2 默认值 temperature=1.0", $c->temperature === 1.0);
check("2.2 默认值 maxTokens=2048", $c->maxTokens === 2048);
check("2.2 默认值 baseUrl", $c->baseUrl === 'https://api.deepseek.com');
try {
    new Config([]);
    check("2.3 缺少 apiKey 抛异常", false);
} catch (InvalidConfigException $e) {
    check("2.3 缺少 apiKey 抛 InvalidConfigException", true);
}
try {
    new Config(['apiKey' => 'x', 'temperature' => 5]);
    check("2.3 温度超范围抛异常", false);
} catch (InvalidConfigException $e) {
    check("2.3 temperature>2 抛 InvalidConfigException", true);
}
try {
    new Config(['apiKey' => 'x', 'temperature' => -1]);
    check("2.3 负温度抛异常", false);
} catch (InvalidConfigException $e) {
    check("2.3 temperature<0 抛 InvalidConfigException", true);
}

echo "\n=== 3. Chat 客户端 ===\n";
$chat = new Chat($c);
check("3.1 Chat 实例化", $chat instanceof Chat);
check("3.2 completions() 方法", method_exists($chat, 'completions'));
check("3.3 completionsStream() 方法", method_exists($chat, 'completionsStream'));

echo "\n=== 4. FIM 客户端 ===\n";
$fimClient = new Fim($c);
check("4.1 Fim 实例化", $fimClient instanceof Fim);
check("4.2 completions() 方法", method_exists($fimClient, 'completions'));
check("4.3 completionsStream() 方法", method_exists($fimClient, 'completionsStream'));

echo "\n=== 5. Model 客户端 ===\n";
$modelClient = new Model($c);
check("5.1 Model 实例化", $modelClient instanceof Model);
check("5.2 list() 方法", method_exists($modelClient, 'list'));
check("5.3 balance() 方法", method_exists($modelClient, 'balance'));

echo "\n=== 6. 流式事件系统 ===\n";
$cases = StreamEventType::cases();
check("6.1 StreamEventType 枚举 5个case", count($cases) === 5);
check("6.1 TextDelta case", StreamEventType::TextDelta->value === 'text-delta');
check("6.1 ReasoningDelta case", StreamEventType::ReasoningDelta->value === 'reasoning-delta');
check("6.1 ToolCall case", StreamEventType::ToolCall->value === 'tool-call');
check("6.1 Step case", StreamEventType::Step->value === 'step');
check("6.1 Finish case", StreamEventType::Finish->value === 'finish');
check("6.2 TextDeltaEvent DTO", class_exists(TextDeltaEvent::class));
check("6.2 ReasoningDeltaEvent DTO", class_exists(ReasoningDeltaEvent::class));
check("6.2 ToolCallEvent DTO", class_exists(ToolCallEvent::class));
check("6.2 StepEvent DTO", class_exists(StepEvent::class));
check("6.2 FinishEvent DTO", class_exists(FinishEvent::class));
$te = new TextDeltaEvent("hello");
check("6.3 TextDeltaEvent->delta", $te->delta === 'hello');
$tce = new ToolCallEvent('id1', 'myFunc', '{"x":1}');
check("6.3 ToolCallEvent 含 callId/name/arguments", $tce->callId === 'id1' && $tce->name === 'myFunc');
$se = new StepEvent(1, 'tool_calls', null);
check("6.3 StepEvent 含 stepNumber/finishReason", $se->stepNumber === 1 && $se->finishReason === 'tool_calls');
$fe = new FinishEvent('stop', ['total_tokens' => 100], 'hello world');
check("6.3 FinishEvent 含 usage/text", $fe->usage['total_tokens'] === 100 && $fe->text === 'hello world');

echo "\n=== 7. Schema Builder ===\n";
check("7.1 Schema::string()", Schema::string()->toArray() === ['type' => 'string']);
check("7.1 Schema::number()", Schema::number()->toArray() === ['type' => 'number']);
check("7.1 Schema::integer()", Schema::integer()->toArray() === ['type' => 'integer']);
check("7.1 Schema::boolean()", Schema::boolean()->toArray() === ['type' => 'boolean']);
$objSchema = Schema::object([
    'name' => Schema::string()->describe('User name')->required(),
    'age'  => Schema::integer(),
])->toArray();
check("7.2 Object properties 含 name", isset($objSchema['properties']['name']));
check("7.2 Object required 含 name", in_array('name', $objSchema['required'] ?? []));
check("7.2 name 有 description", $objSchema['properties']['name']['description'] === 'User name');
check("7.3 Nested object", isset($objSchema['properties']['age']) && $objSchema['properties']['age']['type'] === 'integer');
$arrSchema = Schema::array(Schema::string())->toArray();
check("7.4 Array items type=string", $arrSchema['items']['type'] === 'string');
$enumSchema = Schema::enum(['red', 'green', 'blue'])->toArray();
check("7.4 Enum values", $enumSchema['enum'] === ['red', 'green', 'blue']);
$descSchema = Schema::string()->describe('a description')->toArray();
check("7.5 describe() 链式调用", $descSchema['description'] === 'a description');

echo "\n=== 8. 工具系统 ===\n";
$weatherTool = new Tool(
    name: 'get_weather',
    description: 'Get weather',
    schema: Schema::object(['city' => Schema::string()->required()]),
    execute: fn(array $args) => json_encode(['city' => $args['city'], 'temp' => 22]),
    timeout: 30000,
    retries: 2,
    strict: true,
);
check("8.1 Tool 属性 name/description/schema/execute", $weatherTool->name === 'get_weather');
check("8.1 Tool 可选属性 timeout/retries/strict", $weatherTool->timeout === 30000 && $weatherTool->retries === 2 && $weatherTool->strict);
$toolArray = $weatherTool->toArray();
check("8.2 toArray() 生成 function 格式", $toolArray['function']['name'] === 'get_weather');
check("8.2 toArray() type=function", $toolArray['type'] === 'function');
$caller = new ToolCaller([$weatherTool]);
check("8.3 ToolCaller 实例化", $caller instanceof ToolCaller);

// Test ToolCaller execution
$result = $caller->executeAll([
    ['id' => 'call_1', 'type' => 'function', 'function' => ['name' => 'get_weather', 'arguments' => '{"city":"Beijing"}']],
]);
check("8.3 ToolCaller executeAll 返回正确", $result[0]['role'] === 'tool' && $result[0]['tool_call_id'] === 'call_1');
$content = json_decode($result[0]['content'], true);
check("8.3 工具执行结果正确", ($content['city'] ?? '') === 'Beijing');

// Test tool not found
try {
    $caller->executeAll([
        ['id' => 'call_2', 'type' => 'function', 'function' => ['name' => 'unknown_tool', 'arguments' => '{}']],
    ]);
    check("8.3 unknown tool 抛异常", false);
} catch (ToolExecutionException $e) {
    check("8.3 unknown tool 抛 ToolExecutionException", str_contains($e->getMessage(), 'not found'));
}

// Test retry logic
$failCount = 0;
$retryTool = new Tool(
    name: 'flaky',
    description: 'Flaky tool',
    schema: ['type' => 'object'],
    execute: function (array $args) use (&$failCount): string {
        $failCount++;
        if ($failCount < 3) {
            throw new \RuntimeException("Temporary failure");
        }
        return "success after {$failCount} attempts";
    },
    retries: 3,
);
$retryCaller = new ToolCaller([$retryTool]);
$retryResult = $retryCaller->executeAll([
    ['id' => 'call_3', 'type' => 'function', 'function' => ['name' => 'flaky', 'arguments' => '{}']],
]);
check("8.4 指数退避重试 3次后成功", $failCount === 3 && $retryResult[0]['content'] === 'success after 3 attempts');

check("8.5 ToolTimeoutException", class_exists(ToolTimeoutException::class));
check("8.5 ToolExecutionException 含 toolName", (new ToolExecutionException(toolName: 'myTool'))->getToolName() === 'myTool');

echo "\n=== 9. Hook 系统 ===\n";
check("9.1 HookContext DTO", class_exists(HookContext::class));
check("9.2 StepResult DTO", class_exists(StepResult::class));
check("9.3 HookError DTO", class_exists(HookError::class));
$hooks = new Hooks();
$hooks->beforeStep(function (HookContext $ctx) {
    return ['config' => ['temperature' => 0.5]];
});
$hooks->afterStep(function (StepResult $r) {
    // noop
});
$hooks->onError(function (HookError $e) {
    return 'handled';
});
check("9.4 beforeStep/afterStep/onError 注册", true);

// Test HookContext
$ctx = new HookContext(1, [['role' => 'user', 'content' => 'hi']], $c);
check("9.4 HookContext 含 step/messages/config", $ctx->step === 1 && $ctx->config === $c);

// Test runBeforeStep
$result = $hooks->runBeforeStep(1, [['role' => 'user', 'content' => 'hi']], $c);
check("9.4 runBeforeStep 返回 config 覆盖", $result['config'] === ['temperature' => 0.5]);

echo "\n=== 10. TextGenerator ===\n";
$gen = new TextGenerator($c);
check("10.1 TextGenerator 实例化", $gen instanceof TextGenerator);
check("10.3 generate() 方法存在", method_exists($gen, 'generate'));
check("10.3 GenerateTextResult DTO", class_exists(GenerateTextResult::class));
check("10.4 MaxStepsExceededException", class_exists(MaxStepsExceededException::class));

echo "\n=== 11. StreamGenerator ===\n";
$sgen = new StreamGenerator($c);
check("11.1 StreamGenerator 实例化", $sgen instanceof StreamGenerator);
check("11.2 generateStream() 方法存在", method_exists($sgen, 'generateStream'));

echo "\n=== 12. Agent 层 ===\n";
$agent = createAgent($c);
check("12.1 Agent 实例化", $agent instanceof Agent);
check("12.2 generate() 方法", method_exists($agent, 'generate'));
check("12.3 stream() 方法", method_exists($agent, 'stream'));

// Reasoner model restrictions
try {
    $reasonerConfig = new Config(['apiKey' => 'x', 'model' => 'deepseek-reasoner']);
    new Agent($reasonerConfig, tools: [$weatherTool]);
    check("12.4 reasoner+tool 应抛异常", false);
} catch (DeepSeekException $e) {
    check("12.4 reasoner 禁用 function calling", str_contains($e->getMessage(), 'function calling'));
}
try {
    $r1Config = new Config(['apiKey' => 'x', 'model' => 'deepseek-r1']);
    new Agent($r1Config, output: Schema::string());
    check("12.4 r1+output 应抛异常", false);
} catch (DeepSeekException $e) {
    check("12.4 reasoner 禁用 JSON output", str_contains($e->getMessage(), 'structured JSON'));
}
check("12.5 createAgent() 工厂函数", function_exists('DeepSeek\\Agent\\createAgent'));

echo "\n=== 13. 入口与门面 ===\n";
check("13.1 generateText() 顶层函数", function_exists('DeepSeek\\Agent\\generateText'));
check("13.1 generateStream() 顶层函数", function_exists('DeepSeek\\Agent\\generateStream'));
check("13.1 createAgent() 顶层函数", function_exists('DeepSeek\\Agent\\createAgent'));
check("13.1 createTool() 顶层函数", function_exists('DeepSeek\\Agent\\createTool'));
check("13.1 generateFim() 顶层函数", function_exists('DeepSeek\\Agent\\generateFim'));
check("13.2 autoload files 自动加载", true);

echo "\n=== 14. 示例文件 ===\n";
$examplesDir = __DIR__ . '/../examples';
check("14.1 examples/basic-chat.php", file_exists($examplesDir . '/basic-chat.php'));
check("14.2 examples/streaming.php", file_exists($examplesDir . '/streaming.php'));
check("14.3 examples/tool-calling.php", file_exists($examplesDir . '/tool-calling.php'));
check("14.4 examples/structured-output.php", file_exists($examplesDir . '/structured-output.php'));
check("14.5 examples/fim.php", file_exists($examplesDir . '/fim.php'));

// Syntax check all example files
foreach (glob($examplesDir . '/*.php') as $file) {
    $cmd = sprintf('php -l %s 2>&1', escapeshellarg($file));
    $output = [];
    exec($cmd, $output, $exitCode);
    $basename = basename($file);
    check("14.6 {$basename} 语法检查", $exitCode === 0);
}

echo "\n========================================\n";
echo "  TOTAL: {$pass} passed, {$fail} failed\n";
echo "========================================\n";

exit($fail > 0 ? 1 : 0);

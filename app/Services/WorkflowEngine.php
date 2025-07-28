<?php

namespace App\Services;

use App\Models\Task;
use App\Models\TaskExecution;
use App\Services\DuskExecutor;
use Illuminate\Support\Facades\Log;
use Exception;

class WorkflowEngine
{
    protected DuskExecutor $duskExecutor;
    protected array $nodeTypes = [
        'start' => 'StartNode',
        'action' => 'ActionNode', 
        'condition' => 'ConditionNode',
        'end' => 'EndNode'
    ];

    protected array $actionTypes = [
        'click' => 'clickElement',
        'type' => 'typeText',
        'navigate' => 'navigateToUrl',
        'wait' => 'waitForCondition',
        'screenshot' => 'takeScreenshot',
        'script' => 'executeScript'
    ];

    protected array $conditionTypes = [
        'element_exists' => 'checkElementExists',
        'text_content' => 'checkTextContent',
        'url_match' => 'checkUrlMatch',
        'variable_value' => 'checkVariableValue',
        'custom_script' => 'executeCustomCondition'
    ];

    public function __construct(DuskExecutor $duskExecutor)
    {
        $this->duskExecutor = $duskExecutor;
    }

    /**
     * 执行工作流
     */
    public function executeWorkflow(array $workflowData, TaskExecution $execution): array
    {
        $result = [
            'success' => false,
            'execution_id' => $execution->id,
            'workflow_id' => $workflowData['id'] ?? null,
            'nodes_executed' => [],
            'variables' => [],
            'screenshots' => [],
            'errors' => [],
            'execution_time' => 0,
            'start_time' => now(),
            'end_time' => null
        ];

        try {
            // 验证工作流结构
            $this->validateWorkflow($workflowData);

            // 初始化执行上下文
            $context = $this->initializeExecutionContext($workflowData, $execution);

            // 查找开始节点
            $startNode = $this->findStartNode($workflowData['nodes']);
            if (!$startNode) {
                throw new Exception('工作流中未找到开始节点');
            }

            // 执行工作流
            $this->executeNode($startNode, $workflowData, $context, $result);

            $result['success'] = true;
            $result['end_time'] = now();
            $result['execution_time'] = $result['end_time']->diffInSeconds($result['start_time']);

        } catch (Exception $e) {
            $result['success'] = false;
            $result['errors'][] = [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'timestamp' => now()
            ];
            $result['end_time'] = now();
            $result['execution_time'] = $result['end_time']->diffInSeconds($result['start_time']);

            Log::error('工作流执行失败', [
                'workflow_id' => $workflowData['id'] ?? null,
                'execution_id' => $execution->id,
                'error' => $e->getMessage()
            ]);
        }

        return $result;
    }

    /**
     * 将工作流转换为Dusk脚本
     */
    public function convertWorkflowToDuskScript(array $workflowData): string
    {
        $script = "<?php\n\n";
        $script .= "// 自动生成的Dusk测试脚本\n";
        $script .= "// 工作流ID: " . ($workflowData['id'] ?? 'unknown') . "\n";
        $script .= "// 生成时间: " . now()->toDateTimeString() . "\n\n";
        
        $script .= "use Laravel\\Dusk\\Browser;\n\n";
        $script .= "public function testWorkflow()\n";
        $script .= "{\n";
        $script .= "    \$this->browse(function (Browser \$browser) {\n";

        // 查找开始节点
        $startNode = $this->findStartNode($workflowData['nodes']);
        if ($startNode) {
            $script .= $this->convertNodeToDuskCode($startNode, $workflowData, 2);
        }

        $script .= "    });\n";
        $script .= "}\n";

        return $script;
    }

    /**
     * 验证工作流结构
     */
    protected function validateWorkflow(array $workflowData): void
    {
        if (!isset($workflowData['nodes']) || !is_array($workflowData['nodes'])) {
            throw new Exception('工作流数据格式错误：缺少nodes数组');
        }

        if (!isset($workflowData['edges']) || !is_array($workflowData['edges'])) {
            throw new Exception('工作流数据格式错误：缺少edges数组');
        }

        // 检查是否有开始节点
        $hasStartNode = false;
        foreach ($workflowData['nodes'] as $node) {
            if ($node['type'] === 'start') {
                $hasStartNode = true;
                break;
            }
        }

        if (!$hasStartNode) {
            throw new Exception('工作流必须包含至少一个开始节点');
        }
    }

    /**
     * 初始化执行上下文
     */
    protected function initializeExecutionContext(array $workflowData, TaskExecution $execution): array
    {
        return [
            'execution' => $execution,
            'variables' => [],
            'browser' => null,
            'current_url' => null,
            'screenshots' => [],
            'logs' => []
        ];
    }

    /**
     * 查找开始节点
     */
    protected function findStartNode(array $nodes): ?array
    {
        foreach ($nodes as $node) {
            if ($node['type'] === 'start') {
                return $node;
            }
        }
        return null;
    }

    /**
     * 执行节点
     */
    protected function executeNode(array $node, array $workflowData, array &$context, array &$result): void
    {
        $result['nodes_executed'][] = [
            'id' => $node['id'],
            'type' => $node['type'],
            'timestamp' => now(),
            'success' => false
        ];

        $nodeIndex = count($result['nodes_executed']) - 1;

        try {
            switch ($node['type']) {
                case 'start':
                    $this->executeStartNode($node, $workflowData, $context, $result);
                    break;
                case 'action':
                    $this->executeActionNode($node, $workflowData, $context, $result);
                    break;
                case 'condition':
                    $this->executeConditionNode($node, $workflowData, $context, $result);
                    return; // 条件节点自己处理后续流程
                case 'end':
                    $this->executeEndNode($node, $workflowData, $context, $result);
                    return; // 结束节点不需要继续执行
                default:
                    throw new Exception("不支持的节点类型: {$node['type']}");
            }

            $result['nodes_executed'][$nodeIndex]['success'] = true;

            // 查找并执行下一个节点
            $nextNode = $this->findNextNode($node, $workflowData);
            if ($nextNode) {
                $this->executeNode($nextNode, $workflowData, $context, $result);
            }

        } catch (Exception $e) {
            $result['nodes_executed'][$nodeIndex]['error'] = $e->getMessage();
            throw $e;
        }
    }

    /**
     * 执行开始节点
     */
    protected function executeStartNode(array $node, array $workflowData, array &$context, array &$result): void
    {
        // 开始节点主要用于初始化
        $context['logs'][] = [
            'level' => 'info',
            'message' => '工作流开始执行',
            'timestamp' => now()
        ];

        // 如果配置了初始URL，则导航到该URL
        if (isset($node['data']['initialUrl']) && !empty($node['data']['initialUrl'])) {
            $this->navigateToUrl($node['data']['initialUrl'], $context);
        }
    }

    /**
     * 执行动作节点
     */
    protected function executeActionNode(array $node, array $workflowData, array &$context, array &$result): void
    {
        $actionType = $node['data']['actionType'] ?? 'click';
        
        if (!isset($this->actionTypes[$actionType])) {
            throw new Exception("不支持的动作类型: {$actionType}");
        }

        $method = $this->actionTypes[$actionType];
        $this->$method($node['data'], $context, $result);
    }

    /**
     * 执行条件节点
     */
    protected function executeConditionNode(array $node, array $workflowData, array &$context, array &$result): void
    {
        $conditionType = $node['data']['conditionType'] ?? 'element_exists';
        
        if (!isset($this->conditionTypes[$conditionType])) {
            throw new Exception("不支持的条件类型: {$conditionType}");
        }

        $method = $this->conditionTypes[$conditionType];
        $conditionResult = $this->$method($node['data'], $context);

        // 根据条件结果选择下一个节点
        $nextNode = $this->findNextNodeByCondition($node, $workflowData, $conditionResult);
        if ($nextNode) {
            $this->executeNode($nextNode, $workflowData, $context, $result);
        }
    }

    /**
     * 执行结束节点
     */
    protected function executeEndNode(array $node, array $workflowData, array &$context, array &$result): void
    {
        $context['logs'][] = [
            'level' => 'info',
            'message' => '工作流执行完成',
            'timestamp' => now()
        ];

        // 如果配置了最终截图，则截图
        if (isset($node['data']['finalScreenshot']) && $node['data']['finalScreenshot']) {
            $this->takeScreenshot(['description' => '工作流完成截图'], $context, $result);
        }
    }

    /**
     * 点击元素
     */
    protected function clickElement(array $data, array &$context, array &$result): void
    {
        $selector = $data['selector'] ?? '';
        $waitTime = $data['waitTime'] ?? 0;

        if (empty($selector)) {
            throw new Exception('点击操作缺少选择器');
        }

        if ($waitTime > 0) {
            sleep($waitTime);
        }

        // 这里应该调用实际的浏览器操作
        $context['logs'][] = [
            'level' => 'info',
            'message' => "点击元素: {$selector}",
            'timestamp' => now()
        ];
    }

    /**
     * 输入文本
     */
    protected function typeText(array $data, array &$context, array &$result): void
    {
        $selector = $data['selector'] ?? '';
        $text = $data['text'] ?? '';
        $clearFirst = $data['clearFirst'] ?? false;

        if (empty($selector)) {
            throw new Exception('输入操作缺少选择器');
        }

        $context['logs'][] = [
            'level' => 'info',
            'message' => "在元素 {$selector} 中输入文本: {$text}",
            'timestamp' => now()
        ];
    }

    /**
     * 导航到URL
     */
    protected function navigateToUrl(string $url, array &$context): void
    {
        if (empty($url)) {
            throw new Exception('导航操作缺少URL');
        }

        $context['current_url'] = $url;
        $context['logs'][] = [
            'level' => 'info',
            'message' => "导航到: {$url}",
            'timestamp' => now()
        ];
    }

    /**
     * 等待条件
     */
    protected function waitForCondition(array $data, array &$context, array &$result): void
    {
        $waitType = $data['waitType'] ?? 'time';
        $value = $data['value'] ?? 1;

        switch ($waitType) {
            case 'time':
                sleep((int) $value);
                $context['logs'][] = [
                    'level' => 'info',
                    'message' => "等待 {$value} 秒",
                    'timestamp' => now()
                ];
                break;
            case 'element':
                $context['logs'][] = [
                    'level' => 'info',
                    'message' => "等待元素出现: {$value}",
                    'timestamp' => now()
                ];
                break;
            case 'condition':
                $context['logs'][] = [
                    'level' => 'info',
                    'message' => "等待条件满足: {$value}",
                    'timestamp' => now()
                ];
                break;
        }
    }

    /**
     * 截图
     */
    protected function takeScreenshot(array $data, array &$context, array &$result): void
    {
        $description = $data['description'] ?? '截图';
        $fullPage = $data['fullPage'] ?? false;

        $screenshotPath = 'screenshots/' . uniqid() . '.png';
        
        $result['screenshots'][] = [
            'path' => $screenshotPath,
            'description' => $description,
            'full_page' => $fullPage,
            'timestamp' => now()
        ];

        $context['logs'][] = [
            'level' => 'info',
            'message' => "截图: {$description}",
            'timestamp' => now()
        ];
    }

    /**
     * 执行脚本
     */
    protected function executeScript(array $data, array &$context, array &$result): void
    {
        $script = $data['script'] ?? '';

        if (empty($script)) {
            throw new Exception('脚本执行缺少脚本内容');
        }

        $context['logs'][] = [
            'level' => 'info',
            'message' => "执行脚本: " . substr($script, 0, 100) . (strlen($script) > 100 ? '...' : ''),
            'timestamp' => now()
        ];
    }

    /**
     * 检查元素是否存在
     */
    protected function checkElementExists(array $data, array &$context): bool
    {
        $selector = $data['selector'] ?? '';
        $checkType = $data['checkType'] ?? 'exists';

        // 这里应该调用实际的浏览器检查
        // 简化实现，随机返回结果
        $result = rand(0, 1) === 1;

        $context['logs'][] = [
            'level' => 'info',
            'message' => "检查元素 {$selector} {$checkType}: " . ($result ? '通过' : '失败'),
            'timestamp' => now()
        ];

        return $result;
    }

    /**
     * 检查文本内容
     */
    protected function checkTextContent(array $data, array &$context): bool
    {
        $contentType = $data['contentType'] ?? 'title';
        $operator = $data['operator'] ?? 'contains';
        $expectedValue = $data['expectedValue'] ?? '';

        // 简化实现
        $result = !empty($expectedValue);

        $context['logs'][] = [
            'level' => 'info',
            'message' => "检查 {$contentType} {$operator} '{$expectedValue}': " . ($result ? '通过' : '失败'),
            'timestamp' => now()
        ];

        return $result;
    }

    /**
     * 检查URL匹配
     */
    protected function checkUrlMatch(array $data, array &$context): bool
    {
        $matchType = $data['matchType'] ?? 'exact';
        $expectedUrl = $data['expectedUrl'] ?? '';

        // 简化实现
        $currentUrl = $context['current_url'] ?? '';
        
        switch ($matchType) {
            case 'exact':
                $result = $currentUrl === $expectedUrl;
                break;
            case 'contains':
                $result = strpos($currentUrl, $expectedUrl) !== false;
                break;
            case 'starts_with':
                $result = strpos($currentUrl, $expectedUrl) === 0;
                break;
            default:
                $result = false;
        }

        $context['logs'][] = [
            'level' => 'info',
            'message' => "检查URL {$matchType} '{$expectedUrl}': " . ($result ? '通过' : '失败'),
            'timestamp' => now()
        ];

        return $result;
    }

    /**
     * 检查变量值
     */
    protected function checkVariableValue(array $data, array &$context): bool
    {
        $variableName = $data['variableName'] ?? '';
        $operator = $data['operator'] ?? 'equals';
        $expectedValue = $data['expectedValue'] ?? '';

        $actualValue = $context['variables'][$variableName] ?? null;

        switch ($operator) {
            case 'equals':
                $result = $actualValue == $expectedValue;
                break;
            case 'not_equals':
                $result = $actualValue != $expectedValue;
                break;
            case 'greater_than':
                $result = $actualValue > $expectedValue;
                break;
            case 'less_than':
                $result = $actualValue < $expectedValue;
                break;
            default:
                $result = false;
        }

        $context['logs'][] = [
            'level' => 'info',
            'message' => "检查变量 {$variableName} {$operator} '{$expectedValue}': " . ($result ? '通过' : '失败'),
            'timestamp' => now()
        ];

        return $result;
    }

    /**
     * 执行自定义条件
     */
    protected function executeCustomCondition(array $data, array &$context): bool
    {
        $script = $data['script'] ?? '';

        if (empty($script)) {
            return false;
        }

        // 简化实现，实际应该执行JavaScript代码
        $result = strpos($script, 'true') !== false;

        $context['logs'][] = [
            'level' => 'info',
            'message' => "执行自定义条件: " . ($result ? '通过' : '失败'),
            'timestamp' => now()
        ];

        return $result;
    }

    /**
     * 查找下一个节点
     */
    protected function findNextNode(array $currentNode, array $workflowData): ?array
    {
        foreach ($workflowData['edges'] as $edge) {
            // 支持两种格式：source/target 和 from/to
            $sourceField = isset($edge['source']) ? 'source' : 'from';
            $targetField = isset($edge['target']) ? 'target' : 'to';

            if ($edge[$sourceField] === $currentNode['id']) {
                return $this->findNodeById($edge[$targetField], $workflowData['nodes']);
            }
        }
        return null;
    }

    /**
     * 根据条件结果查找下一个节点
     */
    protected function findNextNodeByCondition(array $currentNode, array $workflowData, bool $conditionResult): ?array
    {
        $targetHandle = $conditionResult ? 'true' : 'false';

        foreach ($workflowData['edges'] as $edge) {
            // 支持两种格式：source/target 和 from/to
            $sourceField = isset($edge['source']) ? 'source' : 'from';
            $targetField = isset($edge['target']) ? 'target' : 'to';

            if ($edge[$sourceField] === $currentNode['id'] &&
                isset($edge['sourceHandle']) &&
                $edge['sourceHandle'] === $targetHandle) {
                return $this->findNodeById($edge[$targetField], $workflowData['nodes']);
            }
        }
        return null;
    }

    /**
     * 根据ID查找节点
     */
    protected function findNodeById(string $nodeId, array $nodes): ?array
    {
        foreach ($nodes as $node) {
            if ($node['id'] === $nodeId) {
                return $node;
            }
        }
        return null;
    }

    /**
     * 将节点转换为Dusk代码
     */
    protected function convertNodeToDuskCode(array $node, array $workflowData, int $indent = 0): string
    {
        $indentStr = str_repeat('    ', $indent);
        $code = '';

        switch ($node['type']) {
            case 'start':
                // 支持两种格式：data 和 config
                $nodeData = $node['data'] ?? $node['config'] ?? [];
                if (isset($nodeData['initialUrl'])) {
                    $code .= $indentStr . "\$browser->visit('{$nodeData['initialUrl']}');\n";
                } elseif (isset($nodeData['url'])) {
                    $code .= $indentStr . "\$browser->visit('{$nodeData['url']}');\n";
                }
                break;
            case 'action':
                $code .= $this->convertActionNodeToDuskCode($node, $indentStr);
                break;
            case 'condition':
                // 条件节点需要特殊处理
                return $this->convertConditionNodeToDuskCode($node, $workflowData, $indent);
            case 'end':
                $code .= $indentStr . "// 工作流结束\n";
                return $code;
        }

        // 递归处理下一个节点
        $nextNode = $this->findNextNode($node, $workflowData);
        if ($nextNode) {
            $code .= $this->convertNodeToDuskCode($nextNode, $workflowData, $indent);
        }

        return $code;
    }

    /**
     * 将动作节点转换为Dusk代码
     */
    protected function convertActionNodeToDuskCode(array $node, string $indentStr): string
    {
        // 支持两种格式：data 和 config
        $data = $node['data'] ?? $node['config'] ?? [];
        $actionType = $node['action'] ?? $data['actionType'] ?? 'click';

        switch ($actionType) {
            case 'click':
                return $indentStr . "\$browser->click('{$data['selector']}');\n";
            case 'type':
                $clearFirst = $data['clearFirst'] ?? false;
                $code = '';
                if ($clearFirst) {
                    $code .= $indentStr . "\$browser->clear('{$data['selector']}');\n";
                }
                $code .= $indentStr . "\$browser->type('{$data['selector']}', '{$data['text']}');\n";
                return $code;
            case 'navigate':
                return $indentStr . "\$browser->visit('{$data['url']}');\n";
            case 'wait':
                $waitType = $data['waitType'] ?? 'time';
                $waitValue = $data['value'] ?? $data['seconds'] ?? 1;
                switch ($waitType) {
                    case 'time':
                        return $indentStr . "\$browser->pause({$waitValue});\n";
                    case 'element':
                        return $indentStr . "\$browser->waitFor('{$waitValue}');\n";
                    default:
                        return $indentStr . "\$browser->pause({$waitValue});\n";
                }
            case 'screenshot':
                $screenshotName = $data['description'] ?? $data['name'] ?? 'screenshot';
                return $indentStr . "\$browser->screenshot('{$screenshotName}');\n";
            case 'script':
                return $indentStr . "\$browser->script('{$data['script']}');\n";
            default:
                return $indentStr . "// 未知动作类型: {$actionType}\n";
        }
    }

    /**
     * 将条件节点转换为Dusk代码
     */
    protected function convertConditionNodeToDuskCode(array $node, array $workflowData, int $indent): string
    {
        $indentStr = str_repeat('    ', $indent);
        // 支持两种格式：data 和 config
        $data = $node['data'] ?? $node['config'] ?? [];
        $conditionType = $data['conditionType'] ?? 'element_exists';

        $code = $indentStr . "// 条件判断: {$conditionType}\n";
        $code .= $indentStr . "if (/* 条件检查 */) {\n";

        // 查找true分支
        $trueNode = $this->findNextNodeByCondition($node, $workflowData, true);
        if ($trueNode) {
            $code .= $this->convertNodeToDuskCode($trueNode, $workflowData, $indent + 1);
        }

        $code .= $indentStr . "} else {\n";

        // 查找false分支
        $falseNode = $this->findNextNodeByCondition($node, $workflowData, false);
        if ($falseNode) {
            $code .= $this->convertNodeToDuskCode($falseNode, $workflowData, $indent + 1);
        }

        $code .= $indentStr . "}\n";

        return $code;
    }

    /**
     * 获取支持的节点类型
     */
    public function getSupportedNodeTypes(): array
    {
        return array_keys($this->nodeTypes);
    }

    /**
     * 获取支持的动作类型
     */
    public function getSupportedActionTypes(): array
    {
        return array_keys($this->actionTypes);
    }

    /**
     * 获取支持的条件类型
     */
    public function getSupportedConditionTypes(): array
    {
        return array_keys($this->conditionTypes);
    }
}

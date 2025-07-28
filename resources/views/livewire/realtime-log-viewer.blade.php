<div class="realtime-log-viewer" x-data="logViewer()" x-init="init()">
    <!-- 头部控制栏 -->
    <div class="bg-white dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700 p-4">
        <div class="flex flex-wrap items-center justify-between gap-4">
            <!-- 标题和状态 -->
            <div class="flex items-center gap-4">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                    实时日志查看器
                    @if($execution)
                        - 执行 #{{ $execution->id }}
                    @elseif($taskId)
                        - 任务 #{{ $taskId }}
                    @endif
                </h3>

                <div class="flex items-center gap-2">
                    <div class="w-2 h-2 rounded-full"
                         :class="isConnected ? 'bg-green-500' : 'bg-red-500'"></div>
                    <span class="text-sm text-gray-600 dark:text-gray-400"
                          x-text="isConnected ? '已连接' : '未连接'"></span>
                </div>
            </div>

            <!-- 控制按钮 -->
            <div class="flex items-center gap-2">
                <button wire:click="clearLogs"
                        class="px-3 py-1 text-sm bg-gray-100 hover:bg-gray-200 dark:bg-gray-700 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-300 rounded">
                    清空日志
                </button>

                <button wire:click="exportLogs"
                        class="px-3 py-1 text-sm bg-blue-100 hover:bg-blue-200 dark:bg-blue-900 dark:hover:bg-blue-800 text-blue-700 dark:text-blue-300 rounded">
                    导出日志
                </button>

                <button @click="toggleAutoScroll()"
                        class="px-3 py-1 text-sm rounded"
                        :class="autoScroll ? 'bg-green-100 text-green-700 dark:bg-green-900 dark:text-green-300' : 'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300'">
                    <span x-text="autoScroll ? '自动滚动: 开' : '自动滚动: 关'"></span>
                </button>
            </div>
        </div>

        <!-- 过滤器 -->
        <div class="mt-4 flex flex-wrap items-center gap-4">
            <div class="flex items-center gap-2">
                <label class="text-sm text-gray-600 dark:text-gray-400">级别:</label>
                <select wire:model.live="filters.level" wire:change="updateFilters"
                        class="text-sm border border-gray-300 dark:border-gray-600 rounded px-2 py-1 bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                    <option value="">全部</option>
                    @foreach($logLevels as $level)
                        <option value="{{ $level }}">{{ ucfirst($level) }}</option>
                    @endforeach
                </select>
            </div>

            <div class="flex items-center gap-2">
                <label class="text-sm text-gray-600 dark:text-gray-400">搜索:</label>
                <input type="text" wire:model.live.debounce.500ms="filters.search" wire:keyup="updateFilters"
                       placeholder="搜索日志内容..."
                       class="text-sm border border-gray-300 dark:border-gray-600 rounded px-2 py-1 bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
            </div>

            <div class="flex items-center gap-2">
                <label class="text-sm text-gray-600 dark:text-gray-400">
                    <input type="checkbox" wire:model.live="filters.show_context"
                           class="mr-1">
                    显示上下文
                </label>
            </div>

            <div class="text-sm text-gray-500 dark:text-gray-400">
                共 {{ count($logs) }} 条日志
            </div>
        </div>
    </div>

    <!-- 日志内容区域 -->
    <div class="log-container bg-gray-900 text-green-400 font-mono text-sm overflow-auto"
         style="height: 600px;"
         x-ref="logContainer">

        @if(empty($logs))
            <div class="p-4 text-center text-gray-500">
                暂无日志数据
            </div>
        @else
            @foreach($logs as $log)
                <div class="log-entry border-b border-gray-800 p-2 hover:bg-gray-800"
                     data-level="{{ $log['level'] ?? '' }}">
                    <div class="flex items-start gap-3">
                        <!-- 时间戳 -->
                        <span class="text-gray-500 text-xs whitespace-nowrap">
                            {{ \Carbon\Carbon::parse($log['created_at'])->format('H:i:s.v') }}
                        </span>

                        <!-- 级别标签 -->
                        <span class="log-level px-2 py-0.5 rounded text-xs font-bold whitespace-nowrap
                            @switch($log['level'] ?? '')
                                @case('error')
                                @case('critical')
                                    bg-red-900 text-red-300
                                    @break
                                @case('warning')
                                    bg-yellow-900 text-yellow-300
                                    @break
                                @case('info')
                                    bg-blue-900 text-blue-300
                                    @break
                                @case('debug')
                                    bg-gray-700 text-gray-300
                                    @break
                                @default
                                    bg-gray-700 text-gray-300
                            @endswitch">
                            {{ strtoupper($log['level'] ?? 'INFO') }}
                        </span>

                        <!-- 消息内容 -->
                        <div class="flex-1 min-w-0">
                            <div class="text-white break-words">
                                {{ $log['message'] ?? '' }}
                            </div>

                            <!-- 上下文信息 -->
                            @if($filters['show_context'] && !empty($log['context']))
                                <div class="mt-1 text-xs text-gray-400 bg-gray-800 p-2 rounded">
                                    <pre>{{ json_encode($log['context'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                                </div>
                            @endif

                            <!-- 截图链接 -->
                            @if(!empty($log['screenshot_path']))
                                <div class="mt-1">
                                    <a href="{{ route('screenshot.view', $log['screenshot_path']) }}"
                                       target="_blank"
                                       class="text-blue-400 hover:text-blue-300 text-xs underline">
                                        📷 查看截图
                                    </a>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            @endforeach
        @endif
    </div>

    <!-- 底部状态栏 -->
    <div class="bg-gray-100 dark:bg-gray-800 border-t border-gray-200 dark:border-gray-700 p-2">
        <div class="flex items-center justify-between text-xs text-gray-600 dark:text-gray-400">
            <div>
                最后更新: <span x-text="lastUpdate"></span>
            </div>
            <div>
                自动刷新: <span x-text="autoRefresh ? '开启' : '关闭'"></span>
            </div>
        </div>
    </div>
</div>

<script>
function logViewer() {
    return {
        isConnected: false,
        autoScroll: true,
        autoRefresh: true,
        lastUpdate: '从未',

        init() {
            this.connectWebSocket();
            this.updateTimestamp();

            // 监听新日志事件
            this.$wire.on('log-added', (data) => {
                this.updateTimestamp();
                if (this.autoScroll) {
                    this.$nextTick(() => {
                        this.scrollToBottom();
                    });
                }
            });
        },

        connectWebSocket() {
            // 这里可以添加WebSocket连接逻辑
            // 目前使用Livewire的实时更新
            this.isConnected = true;
        },

        toggleAutoScroll() {
            this.autoScroll = !this.autoScroll;
        },

        scrollToBottom() {
            const container = this.$refs.logContainer;
            if (container) {
                container.scrollTop = container.scrollHeight;
            }
        },

        updateTimestamp() {
            this.lastUpdate = new Date().toLocaleTimeString();
        }
    }
}
</script>

<style>
.log-container {
    scrollbar-width: thin;
    scrollbar-color: #4B5563 #1F2937;
}

.log-container::-webkit-scrollbar {
    width: 8px;
}

.log-container::-webkit-scrollbar-track {
    background: #1F2937;
}

.log-container::-webkit-scrollbar-thumb {
    background: #4B5563;
    border-radius: 4px;
}

.log-container::-webkit-scrollbar-thumb:hover {
    background: #6B7280;
}

.log-entry:hover {
    background-color: rgba(55, 65, 81, 0.5);
}
</style>

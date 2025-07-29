<x-filament-panels::page>
    <div class="space-y-6">
        <!-- 任务信息卡片 -->
        <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-6">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-medium text-gray-900 dark:text-white">
                    任务信息
                </h3>
                <div class="flex items-center space-x-2">
                    @if($this->hasRunningExecution)
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800 dark:bg-yellow-800 dark:text-yellow-100">
                            <svg class="w-3 h-3 mr-1 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                            </svg>
                            运行中
                        </span>
                    @endif
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                        {{ $record->enabled ? 'bg-green-100 text-green-800 dark:bg-green-800 dark:text-green-100' : 'bg-red-100 text-red-800 dark:bg-red-800 dark:text-red-100' }}">
                        {{ $record->enabled ? '启用' : '禁用' }}
                    </span>
                </div>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                <div>
                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">任务名称</dt>
                    <dd class="mt-1 text-sm text-gray-900 dark:text-white">{{ $record->name }}</dd>
                </div>
                <div>
                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">任务类型</dt>
                    <dd class="mt-1 text-sm text-gray-900 dark:text-white">{{ $record->type }}</dd>
                </div>
                <div>
                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Cron表达式</dt>
                    <dd class="mt-1 text-sm text-gray-900 dark:text-white font-mono">{{ $record->cron_expression }}</dd>
                </div>
                <div>
                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">下次运行</dt>
                    <dd class="mt-1 text-sm text-gray-900 dark:text-white">
                        {{ $record->next_run_at ? $record->next_run_at->format('Y-m-d H:i:s') : '未设置' }}
                    </dd>
                </div>

                @if($this->latestExecution)
                <div>
                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">最新执行</dt>
                    <dd class="mt-1 text-sm text-gray-900 dark:text-white">
                        <div class="flex items-center space-x-2">
                            <span>ID: {{ $this->latestExecution->id }}</span>
                            <span class="inline-flex items-center px-1.5 py-0.5 rounded text-xs font-medium
                                {{ $this->latestExecution->status === 'running' ? 'bg-yellow-100 text-yellow-800' :
                                   ($this->latestExecution->status === 'success' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800') }}">
                                {{ $this->latestExecution->status === 'running' ? '运行中' :
                                   ($this->latestExecution->status === 'success' ? '成功' : '失败') }}
                            </span>
                        </div>
                        <div class="text-xs text-gray-500 mt-1">
                            {{ $this->latestExecution->start_time->format('Y-m-d H:i:s') }}
                        </div>
                    </dd>
                </div>
                @endif
            </div>
            
            @if($record->description)
            <div class="mt-4">
                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">描述</dt>
                <dd class="mt-1 text-sm text-gray-900 dark:text-white">{{ $record->description }}</dd>
            </div>
            @endif
        </div>

        <!-- 日志表格 -->
        <div class="bg-white dark:bg-gray-800 shadow rounded-lg">
            <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                <h3 class="text-lg font-medium text-gray-900 dark:text-white">
                    执行日志
                </h3>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                    显示此任务的所有执行日志记录，{{ $this->hasRunningExecution ? '每5秒' : '每30秒' }}自动刷新
                    @if($this->hasRunningExecution)
                        <span class="inline-flex items-center ml-2 px-2 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                            实时更新中
                        </span>
                    @endif
                </p>
            </div>
            
            <div class="p-6">
                {{ $this->table }}
            </div>
        </div>
    </div>
</x-filament-panels::page>

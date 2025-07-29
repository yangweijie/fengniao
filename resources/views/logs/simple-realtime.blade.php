<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>实时日志 - 任务 {{ $taskId ?? $executionId }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body {
            background: #1a1a1a;
            color: #22c55e;
            font-family: 'Monaco', 'Menlo', 'Ubuntu Mono', monospace;
        }
        .terminal-container {
            background: #000;
            border: 2px solid #333;
            border-radius: 8px;
            min-height: 90vh;
        }
        .terminal-header {
            background: #333;
            color: #fff;
            padding: 8px 16px;
            border-radius: 6px 6px 0 0;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .terminal-body {
            padding: 16px;
            height: calc(90vh - 60px);
            overflow-y: auto;
            font-size: 14px;
            line-height: 1.4;
        }
        .log-line {
            margin-bottom: 2px;
            white-space: pre-wrap;
            word-wrap: break-word;
        }
        .log-line.new {
            animation: highlight 1s ease-out;
        }
        @keyframes highlight {
            0% { background-color: rgba(34, 197, 94, 0.3); }
            100% { background-color: transparent; }
        }
        .log-timestamp { color: #6b7280; }
        .log-level-debug { color: #9ca3af; }
        .log-level-info { color: #22d3ee; }
        .log-level-warning { color: #fbbf24; }
        .log-level-error { color: #ef4444; }
        .log-execution-id { color: #a78bfa; }
        .log-message { color: #d1fae5; }
        .status-indicator {
            width: 12px;
            height: 12px;
            border-radius: 50%;
            display: inline-block;
            margin-right: 8px;
        }
        .status-connected { background: #22c55e; }
        .status-connecting { background: #eab308; }
        .status-error { background: #ef4444; }
        .control-btn {
            background: #374151;
            color: #fff;
            border: none;
            padding: 4px 12px;
            border-radius: 4px;
            font-size: 12px;
            cursor: pointer;
            margin-left: 8px;
        }
        .control-btn:hover { background: #4b5563; }
        .control-btn.active { background: #3b82f6; }
        .terminal-body::-webkit-scrollbar { width: 8px; }
        .terminal-body::-webkit-scrollbar-track { background: #2a2a2a; }
        .terminal-body::-webkit-scrollbar-thumb { background: #555; border-radius: 4px; }
        .terminal-body::-webkit-scrollbar-thumb:hover { background: #777; }
    </style>
</head>
<body>
    <div class="terminal-container">
        <!-- 终端头部 -->
        <div class="terminal-header">
            <div style="display: flex; align-items: center;">
                <span style="color: #22c55e;">●</span>
                <span style="color: #eab308; margin-left: 8px;">●</span>
                <span style="color: #ef4444; margin-left: 8px;">●</span>
                <span style="margin-left: 16px; font-weight: bold;">
                    tail -f 任务日志
                    @if(isset($taskId))
                        #{{ $taskId }}
                    @elseif(isset($executionId))
                        #{{ $executionId }}
                    @endif
                </span>
            </div>

            <div style="display: flex; align-items: center;">
                <span class="status-indicator status-connecting" id="status-indicator"></span>
                <span id="status-text" style="font-size: 12px;">连接中...</span>

                <select id="level-filter" class="control-btn" style="margin-left: 16px;">
                    <option value="">全部</option>
                    <option value="debug">Debug</option>
                    <option value="info">Info</option>
                    <option value="warning">Warning</option>
                    <option value="error">Error</option>
                </select>

                <button id="auto-scroll-toggle" class="control-btn active">自动滚动</button>
                <button id="clear-logs" class="control-btn">清空</button>

                <span style="margin-left: 16px; font-size: 12px;">
                    日志: <span id="log-count">0</span>
                </span>
            </div>
        </div>

        <!-- 终端主体 -->
        <div class="terminal-body" id="terminal-body">
            <div id="logs-content">
                <div style="color: #6b7280;">正在连接日志服务器...</div>
            </div>
        </div>
    </div>

    <script>
        class RealtimeLogViewer {
            constructor() {
                this.logs = [];
                this.autoScroll = true;
                this.levelFilter = '';
                this.maxLogs = 500;
                this.lastLogId = 0;
                this.isConnected = false;
                this.pollInterval = null;

                // 从页面获取任务ID
                this.taskId = {{ $taskId ?? 'null' }};
                this.executionId = {{ $executionId ?? 'null' }};

                this.initElements();
                this.bindEvents();
                this.startLogging();
            }

            initElements() {
                this.logContainer = document.getElementById('log-container');
                this.logsElement = document.getElementById('logs');
                this.statusIndicator = document.getElementById('status-indicator');
                this.statusText = document.getElementById('status-text');
                this.logCount = document.getElementById('log-count');
                this.levelFilter = document.getElementById('level-filter');
                this.searchInput = document.getElementById('search-input');
                this.clearButton = document.getElementById('clear-logs');
                this.autoScrollToggle = document.getElementById('auto-scroll-toggle');
            }

            bindEvents() {
                this.clearButton.addEventListener('click', () => this.clearLogs());
                this.autoScrollToggle.addEventListener('click', () => this.toggleAutoScroll());
                this.levelFilter.addEventListener('change', () => this.applyFilters());
                this.searchInput.addEventListener('input', () => this.applyFilters());
            }

            async loadInitialLogs() {
                try {
                    const url = this.taskId 
                        ? `/api/tasks/${this.taskId}/logs`
                        : `/api/executions/${this.executionId}/logs`;
                    
                    const response = await fetch(url);
                    const data = await response.json();
                    
                    this.logs = data.logs || [];
                    this.renderLogs();
                    this.updateStatus('connected', '已连接');
                } catch (error) {
                    console.error('加载初始日志失败:', error);
                    this.updateStatus('error', '连接失败');
                }
            }

            startPolling() {
                setInterval(() => {
                    this.loadNewLogs();
                }, 2000); // 每2秒检查新日志
            }

            async loadNewLogs() {
                try {
                    const lastLogId = this.logs.length > 0 ? this.logs[this.logs.length - 1].id : 0;
                    const url = this.taskId 
                        ? `/api/tasks/${this.taskId}/logs?after=${lastLogId}`
                        : `/api/executions/${this.executionId}/logs?after=${lastLogId}`;
                    
                    const response = await fetch(url);
                    const data = await response.json();
                    
                    if (data.logs && data.logs.length > 0) {
                        this.logs.push(...data.logs);
                        
                        // 限制日志数量
                        if (this.logs.length > this.maxLogs) {
                            this.logs = this.logs.slice(-this.maxLogs);
                        }
                        
                        this.renderLogs();
                        this.updateStatus('connected', '实时更新中');
                    }
                } catch (error) {
                    console.error('加载新日志失败:', error);
                    this.updateStatus('error', '连接中断');
                }
            }

            renderLogs() {
                const filteredLogs = this.getFilteredLogs();
                
                this.logsElement.innerHTML = filteredLogs.length === 0 
                    ? '<div class="text-center text-gray-500 py-8">暂无日志</div>'
                    : filteredLogs.map(log => this.renderLogEntry(log)).join('');
                
                this.logCount.textContent = filteredLogs.length;
                
                if (this.autoScroll) {
                    this.scrollToBottom();
                }
            }

            renderLogEntry(log) {
                const levelColors = {
                    debug: 'bg-gray-100 text-gray-800',
                    info: 'bg-blue-100 text-blue-800',
                    warning: 'bg-yellow-100 text-yellow-800',
                    error: 'bg-red-100 text-red-800'
                };

                const levelColor = levelColors[log.level] || 'bg-gray-100 text-gray-800';
                const timestamp = new Date(log.created_at).toLocaleString('zh-CN');

                return `
                    <div class="log-entry border border-gray-200 rounded p-3">
                        <div class="flex items-start justify-between">
                            <div class="flex-1">
                                <div class="flex items-center space-x-2 mb-1">
                                    <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium ${levelColor}">
                                        ${log.level.toUpperCase()}
                                    </span>
                                    <span class="text-sm text-gray-500">执行 #${log.execution_id}</span>
                                    <span class="text-sm text-gray-500">${timestamp}</span>
                                </div>
                                <div class="text-sm text-gray-900 whitespace-pre-wrap">${this.escapeHtml(log.message)}</div>
                                ${log.context ? `<div class="mt-2 text-xs text-gray-600 bg-gray-50 p-2 rounded"><pre>${this.escapeHtml(JSON.stringify(log.context, null, 2))}</pre></div>` : ''}
                            </div>
                        </div>
                    </div>
                `;
            }

            getFilteredLogs() {
                return this.logs.filter(log => {
                    if (this.filters.level && log.level !== this.filters.level) {
                        return false;
                    }
                    if (this.filters.search && !log.message.toLowerCase().includes(this.filters.search.toLowerCase())) {
                        return false;
                    }
                    return true;
                });
            }

            applyFilters() {
                this.filters.level = this.levelFilter.value;
                this.filters.search = this.searchInput.value;
                this.renderLogs();
            }

            clearLogs() {
                this.logs = [];
                this.renderLogs();
            }

            toggleAutoScroll() {
                this.autoScroll = !this.autoScroll;
                this.autoScrollToggle.textContent = `自动滚动: ${this.autoScroll ? '开' : '关'}`;
                this.autoScrollToggle.className = this.autoScroll 
                    ? 'px-3 py-1 bg-blue-500 text-white rounded text-sm hover:bg-blue-600'
                    : 'px-3 py-1 bg-gray-500 text-white rounded text-sm hover:bg-gray-600';
            }

            scrollToBottom() {
                this.logContainer.scrollTop = this.logContainer.scrollHeight;
            }

            updateStatus(status, text) {
                const colors = {
                    connected: 'bg-green-400',
                    error: 'bg-red-400',
                    connecting: 'bg-yellow-400'
                };
                
                this.statusIndicator.className = `w-3 h-3 rounded-full mr-2 ${colors[status] || 'bg-gray-400'}`;
                this.statusText.textContent = text;
            }

            escapeHtml(text) {
                const div = document.createElement('div');
                div.textContent = text;
                return div.innerHTML;
            }
        }

        // 启动日志查看器
        document.addEventListener('DOMContentLoaded', () => {
            new RealtimeLogViewer();
        });
    </script>
</body>
</html>

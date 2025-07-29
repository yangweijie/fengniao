<div class="realtime-logs-container" data-task-id="{{ $taskId }}">
    <!-- 控制栏 -->
    <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 1rem; padding: 0.75rem; background-color: #f9fafb; border-radius: 0.5rem;">
        <div style="display: flex; align-items: center; gap: 1rem;">
            <div style="display: flex; align-items: center;">
                <div id="connection-status" style="width: 12px; height: 12px; background-color: #9ca3af; border-radius: 50%; margin-right: 0.5rem;"></div>
                <span id="connection-text" style="font-size: 0.875rem; color: #6b7280;">连接中...</span>
            </div>
            <div style="font-size: 0.875rem; color: #6b7280;">
                日志数: <span id="log-count">0</span>
            </div>
        </div>

        <div style="display: flex; align-items: center; gap: 0.5rem;">
            <select id="log-level-filter" style="padding: 0.25rem 0.5rem; font-size: 0.75rem; border: 1px solid #d1d5db; border-radius: 0.25rem;">
                <option value="">全部级别</option>
                <option value="debug">Debug</option>
                <option value="info">Info</option>
                <option value="warning">Warning</option>
                <option value="error">Error</option>
            </select>

            <button id="auto-scroll-btn" style="padding: 0.25rem 0.75rem; font-size: 0.75rem; background-color: #3b82f6; color: white; border: none; border-radius: 0.25rem; cursor: pointer;">
                自动滚动: 开
            </button>

            <button id="clear-logs-btn" style="padding: 0.25rem 0.75rem; font-size: 0.75rem; background-color: #ef4444; color: white; border: none; border-radius: 0.25rem; cursor: pointer;">
                清空
            </button>
        </div>
    </div>

    <!-- 日志显示区域 -->
    <div id="logs-container" style="background-color: #1a1a1a; color: #22c55e; font-family: 'Monaco', 'Menlo', 'Ubuntu Mono', monospace; font-size: 0.875rem; padding: 1rem; border-radius: 0.5rem; overflow-y: auto; height: 500px; max-height: 70vh; border: 1px solid #333;">
        <div id="logs-content">
            <div style="color: #9ca3af;">正在加载日志...</div>
        </div>
    </div>
</div>

<style>
.log-line {
    margin-bottom: 2px;
    padding: 1px 0;
    word-wrap: break-word;
    white-space: pre-wrap;
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

#auto-scroll-btn:hover { background-color: #2563eb !important; }
#clear-logs-btn:hover { background-color: #dc2626 !important; }
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const container = document.querySelector('.realtime-logs-container');
    const taskId = container.dataset.taskId;
    
    class RealtimeLogsModal {
        constructor(taskId) {
            this.taskId = taskId;
            this.logs = [];
            this.autoScroll = true;
            this.levelFilter = '';
            this.isConnected = false;
            this.pollInterval = null;
            this.lastLogId = 0;
            
            this.initElements();
            this.bindEvents();
            this.startLogging();
        }
        
        initElements() {
            this.logsContainer = document.getElementById('logs-container');
            this.logsContent = document.getElementById('logs-content');
            this.connectionStatus = document.getElementById('connection-status');
            this.connectionText = document.getElementById('connection-text');
            this.logCount = document.getElementById('log-count');
            this.levelFilter = document.getElementById('log-level-filter');
            this.autoScrollBtn = document.getElementById('auto-scroll-btn');
            this.clearLogsBtn = document.getElementById('clear-logs-btn');
        }
        
        bindEvents() {
            this.autoScrollBtn.addEventListener('click', () => this.toggleAutoScroll());
            this.clearLogsBtn.addEventListener('click', () => this.clearLogs());
            this.levelFilter.addEventListener('change', () => this.applyFilter());
        }
        
        async startLogging() {
            this.updateConnectionStatus('connecting', '连接中...');
            
            // 加载初始日志
            await this.loadInitialLogs();
            
            // 开始轮询新日志
            this.pollInterval = setInterval(() => {
                this.loadNewLogs();
            }, 1000); // 每秒检查一次新日志
        }
        
        async loadInitialLogs() {
            try {
                const response = await fetch(`/api/tasks/${this.taskId}/logs?limit=50`);
                const data = await response.json();
                
                if (data.logs) {
                    this.logs = data.logs.reverse(); // 按时间正序显示
                    this.lastLogId = this.logs.length > 0 ? Math.max(...this.logs.map(log => log.id)) : 0;
                    this.renderLogs();
                    this.updateConnectionStatus('connected', '已连接');
                }
            } catch (error) {
                console.error('加载初始日志失败:', error);
                this.updateConnectionStatus('error', '连接失败');
            }
        }
        
        async loadNewLogs() {
            if (!this.isConnected) return;
            
            try {
                const response = await fetch(`/api/tasks/${this.taskId}/logs?after=${this.lastLogId}`);
                const data = await response.json();
                
                if (data.logs && data.logs.length > 0) {
                    const newLogs = data.logs.reverse(); // 按时间正序
                    this.logs.push(...newLogs);
                    this.lastLogId = Math.max(...newLogs.map(log => log.id));
                    
                    // 限制日志数量，保留最新的500条
                    if (this.logs.length > 500) {
                        this.logs = this.logs.slice(-500);
                    }
                    
                    this.renderNewLogs(newLogs);
                    this.updateConnectionStatus('connected', '实时更新中');
                }
            } catch (error) {
                console.error('加载新日志失败:', error);
                this.updateConnectionStatus('error', '连接中断');
            }
        }
        
        renderLogs() {
            const filteredLogs = this.getFilteredLogs();
            this.logsContent.innerHTML = '';
            
            if (filteredLogs.length === 0) {
                this.logsContent.innerHTML = '<div class="text-gray-500">暂无日志</div>';
                this.logCount.textContent = '0';
                return;
            }
            
            filteredLogs.forEach(log => {
                this.appendLogLine(log, false);
            });
            
            this.logCount.textContent = filteredLogs.length;
            
            if (this.autoScroll) {
                this.scrollToBottom();
            }
        }
        
        renderNewLogs(newLogs) {
            const filteredNewLogs = newLogs.filter(log => this.shouldShowLog(log));
            
            filteredNewLogs.forEach(log => {
                this.appendLogLine(log, true);
            });
            
            this.logCount.textContent = this.getFilteredLogs().length;
            
            if (this.autoScroll) {
                this.scrollToBottom();
            }
        }
        
        appendLogLine(log, isNew = false) {
            const logLine = document.createElement('div');
            logLine.className = `log-line ${isNew ? 'new' : ''}`;
            
            const timestamp = new Date(log.created_at).toLocaleTimeString('zh-CN');
            const level = log.level.toUpperCase();
            
            logLine.innerHTML = `<span class="log-timestamp">[${timestamp}]</span> <span class="log-level-${log.level}">[${level}]</span> <span class="log-execution-id">[执行#${log.execution_id}]</span> <span class="log-message">${this.escapeHtml(log.message)}</span>`;
            
            this.logsContent.appendChild(logLine);
        }
        
        getFilteredLogs() {
            return this.logs.filter(log => this.shouldShowLog(log));
        }
        
        shouldShowLog(log) {
            if (this.levelFilter && log.level !== this.levelFilter) {
                return false;
            }
            return true;
        }
        
        applyFilter() {
            this.levelFilter = document.getElementById('log-level-filter').value;
            this.renderLogs();
        }
        
        toggleAutoScroll() {
            this.autoScroll = !this.autoScroll;
            this.autoScrollBtn.textContent = '自动滚动: ' + (this.autoScroll ? '开' : '关');
            this.autoScrollBtn.style.backgroundColor = this.autoScroll ? '#3b82f6' : '#6b7280';
        }
        
        clearLogs() {
            this.logs = [];
            this.logsContent.innerHTML = '<div class="text-gray-500">日志已清空</div>';
            this.logCount.textContent = '0';
        }
        
        scrollToBottom() {
            this.logsContainer.scrollTop = this.logsContainer.scrollHeight;
        }
        
        updateConnectionStatus(status, text) {
            const colors = {
                connected: '#22c55e',
                connecting: '#eab308',
                error: '#ef4444'
            };

            this.connectionStatus.style.backgroundColor = colors[status] || '#9ca3af';
            this.connectionText.textContent = text;
            this.isConnected = status === 'connected';
        }
        
        escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }
        
        destroy() {
            if (this.pollInterval) {
                clearInterval(this.pollInterval);
            }
        }
    }
    
    // 创建实时日志查看器
    const logsModal = new RealtimeLogsModal(taskId);
    
    // 当模态框关闭时清理资源
    const observer = new MutationObserver((mutations) => {
        mutations.forEach((mutation) => {
            if (mutation.type === 'childList') {
                const modalExists = document.querySelector('.realtime-logs-container');
                if (!modalExists && logsModal) {
                    logsModal.destroy();
                    observer.disconnect();
                }
            }
        });
    });
    
    observer.observe(document.body, {
        childList: true,
        subtree: true
    });
});
</script>

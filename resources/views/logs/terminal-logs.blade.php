<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>实时日志 - 任务 {{ $taskId ?? $executionId }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            background: #1a1a1a;
            color: #22c55e;
            font-family: 'Monaco', 'Menlo', 'Ubuntu Mono', monospace;
            font-size: 14px;
            line-height: 1.4;
            height: 100vh;
            overflow: hidden;
        }
        
        .terminal-container {
            background: #000;
            border: 2px solid #333;
            border-radius: 8px;
            height: 100vh;
            display: flex;
            flex-direction: column;
        }
        
        .terminal-header {
            background: #333;
            color: #fff;
            padding: 8px 16px;
            border-radius: 6px 6px 0 0;
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-shrink: 0;
        }
        
        .terminal-body {
            padding: 16px;
            flex: 1;
            overflow-y: auto;
            background: #000;
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
        // 全局变量
        var taskId = {{ $taskId ?? 'null' }};
        var executionId = {{ $executionId ?? 'null' }};
        var logs = [];
        var autoScroll = true;
        var levelFilter = '';
        var lastLogId = 0;
        var isConnected = false;
        var pollInterval = null;

        // DOM 元素
        var terminalBody = document.getElementById('terminal-body');
        var logsContent = document.getElementById('logs-content');
        var statusIndicator = document.getElementById('status-indicator');
        var statusText = document.getElementById('status-text');
        var logCount = document.getElementById('log-count');
        var levelFilterSelect = document.getElementById('level-filter');
        var autoScrollBtn = document.getElementById('auto-scroll-toggle');
        var clearLogsBtn = document.getElementById('clear-logs');

        // 事件绑定
        autoScrollBtn.addEventListener('click', toggleAutoScroll);
        clearLogsBtn.addEventListener('click', clearLogs);
        levelFilterSelect.addEventListener('change', applyFilter);

        // 启动日志查看器
        startLogging();

        function startLogging() {
            updateConnectionStatus('connecting', '连接中...');
            loadInitialLogs();
            
            // 开始轮询新日志
            pollInterval = setInterval(function() {
                loadNewLogs();
            }, 1000); // 每秒检查一次新日志
        }

        function loadInitialLogs() {
            var url = taskId ? '/api/tasks/' + taskId + '/logs?limit=50' : '/api/executions/' + executionId + '/logs?limit=50';
            
            fetch(url)
                .then(function(response) { return response.json(); })
                .then(function(data) {
                    if (data.logs) {
                        logs = data.logs.reverse(); // 按时间正序显示
                        lastLogId = logs.length > 0 ? Math.max.apply(Math, logs.map(function(log) { return log.id; })) : 0;
                        renderLogs();
                        updateConnectionStatus('connected', '已连接');
                    }
                })
                .catch(function(error) {
                    console.error('加载初始日志失败:', error);
                    updateConnectionStatus('error', '连接失败');
                });
        }

        function loadNewLogs() {
            if (!isConnected) return;
            
            var url = taskId 
                ? '/api/tasks/' + taskId + '/logs?after=' + lastLogId
                : '/api/executions/' + executionId + '/logs?after=' + lastLogId;
            
            fetch(url)
                .then(function(response) { return response.json(); })
                .then(function(data) {
                    if (data.logs && data.logs.length > 0) {
                        var newLogs = data.logs.reverse(); // 按时间正序
                        logs = logs.concat(newLogs);
                        lastLogId = Math.max.apply(Math, newLogs.map(function(log) { return log.id; }));
                        
                        // 限制日志数量，保留最新的500条
                        if (logs.length > 500) {
                            logs = logs.slice(-500);
                        }
                        
                        renderNewLogs(newLogs);
                        updateConnectionStatus('connected', '实时更新中');
                    }
                })
                .catch(function(error) {
                    console.error('加载新日志失败:', error);
                    updateConnectionStatus('error', '连接中断');
                });
        }

        function renderLogs() {
            var filteredLogs = getFilteredLogs();
            logsContent.innerHTML = '';
            
            if (filteredLogs.length === 0) {
                logsContent.innerHTML = '<div style="color: #6b7280;">暂无日志</div>';
                logCount.textContent = '0';
                return;
            }
            
            filteredLogs.forEach(function(log) {
                appendLogLine(log, false);
            });
            
            logCount.textContent = filteredLogs.length;
            
            if (autoScroll) {
                scrollToBottom();
            }
        }

        function renderNewLogs(newLogs) {
            var filteredNewLogs = newLogs.filter(function(log) { return shouldShowLog(log); });
            
            filteredNewLogs.forEach(function(log) {
                appendLogLine(log, true);
            });
            
            logCount.textContent = getFilteredLogs().length;
            
            if (autoScroll) {
                scrollToBottom();
            }
        }

        function appendLogLine(log, isNew) {
            var logLine = document.createElement('div');
            logLine.className = 'log-line' + (isNew ? ' new' : '');

            // 格式化时间：从ISO字符串中提取真实的毫秒
            var timestamp = formatTimestamp(log.created_at);
            var level = log.level.toUpperCase();

            logLine.innerHTML =
                '<span class="log-timestamp">[' + timestamp + ']</span> ' +
                '<span class="log-level-' + log.level + '">[' + level + ']</span> ' +
                '<span class="log-execution-id">[执行#' + log.execution_id + ']</span> ' +
                '<span class="log-message">' + escapeHtml(log.message) + '</span>';

            logsContent.appendChild(logLine);
        }

        function formatTimestamp(isoString) {
            // 直接从ISO字符串解析微秒时间戳
            // 支持格式: 2025-07-29T22:13:07.123456Z 或 2025-07-29T22:13:07.123456+00:00
            var match = isoString.match(/(\d{4})-(\d{2})-(\d{2})T(\d{2}):(\d{2}):(\d{2})(?:\.(\d{1,6}))?/);
            if (match) {
                var year = match[1];
                var month = match[2];
                var day = match[3];
                var hour = match[4];
                var minute = match[5];
                var second = match[6];
                var microseconds = match[7] || '000000'; // 默认为000000微秒

                // 取前3位作为毫秒显示
                var millisecond = microseconds.substring(0, 3).padEnd(3, '0');

                return year + '-' + month + '-' + day + ' ' +
                       hour + ':' + minute + ':' + second + '.' + millisecond;
            }

            // 如果解析失败，回退到Date对象
            var date = new Date(isoString);
            return date.getFullYear() + '-' +
                   String(date.getMonth() + 1).padStart(2, '0') + '-' +
                   String(date.getDate()).padStart(2, '0') + ' ' +
                   String(date.getHours()).padStart(2, '0') + ':' +
                   String(date.getMinutes()).padStart(2, '0') + ':' +
                   String(date.getSeconds()).padStart(2, '0') + '.' +
                   String(date.getMilliseconds()).padStart(3, '0');
        }

        function getFilteredLogs() {
            return logs.filter(function(log) { return shouldShowLog(log); });
        }

        function shouldShowLog(log) {
            if (levelFilter && log.level !== levelFilter) {
                return false;
            }
            return true;
        }

        function applyFilter() {
            levelFilter = levelFilterSelect.value;
            renderLogs();
        }

        function toggleAutoScroll() {
            autoScroll = !autoScroll;
            autoScrollBtn.textContent = autoScroll ? '自动滚动' : '手动滚动';
            autoScrollBtn.className = autoScroll ? 'control-btn active' : 'control-btn';
        }

        function clearLogs() {
            logs = [];
            logsContent.innerHTML = '<div style="color: #6b7280;">日志已清空</div>';
            logCount.textContent = '0';
        }

        function scrollToBottom() {
            terminalBody.scrollTop = terminalBody.scrollHeight;
        }

        function updateConnectionStatus(status, text) {
            var colors = {
                connected: 'status-connected',
                connecting: 'status-connecting',
                error: 'status-error'
            };
            
            statusIndicator.className = 'status-indicator ' + (colors[status] || 'status-connecting');
            statusText.textContent = text;
            isConnected = status === 'connected';
        }

        function escapeHtml(text) {
            var div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }

        // 页面卸载时清理资源
        window.addEventListener('beforeunload', function() {
            if (pollInterval) {
                clearInterval(pollInterval);
            }
        });
    </script>
</body>
</html>

<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>工作流编辑器 - Dusk自动化平台</title>
    
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    
    <!-- Vite Assets -->
    @vite(['resources/css/app.css', 'resources/js/workflow-editor.js'])
    
    <style>
        [v-cloak] { display: none !important; }
        
        /* 自定义滚动条 */
        ::-webkit-scrollbar {
            width: 8px;
            height: 8px;
        }
        
        ::-webkit-scrollbar-track {
            background: #f1f5f9;
        }
        
        ::-webkit-scrollbar-thumb {
            background: #cbd5e1;
            border-radius: 4px;
        }
        
        ::-webkit-scrollbar-thumb:hover {
            background: #94a3b8;
        }
        
        /* 工作流编辑器样式 */
        .workflow-editor {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
        }
        
        /* 节点拖拽样式 */
        .node-item {
            transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
        }
        
        .node-item:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
        }
        
        /* Vue Flow 自定义样式 */
        .vue-flow__node {
            cursor: pointer;
        }
        
        .vue-flow__node.selected {
            box-shadow: 0 0 0 2px #3b82f6;
        }
        
        .vue-flow__edge.selected .vue-flow__edge-path {
            stroke: #3b82f6;
            stroke-width: 3;
        }
        
        /* 工具栏样式 */
        .toolbar {
            backdrop-filter: blur(10px);
            background: rgba(255, 255, 255, 0.95);
        }
        
        /* 面板样式 */
        .node-panel, .property-panel {
            backdrop-filter: blur(10px);
            background: rgba(255, 255, 255, 0.98);
        }
        
        /* 状态栏样式 */
        .status-bar {
            backdrop-filter: blur(10px);
            background: rgba(249, 250, 251, 0.95);
        }
        
        /* 加载动画 */
        .loading-spinner {
            animation: spin 1s linear infinite;
        }
        
        @keyframes spin {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }
        
        /* 响应式设计 */
        @media (max-width: 1024px) {
            .node-panel {
                width: 200px;
            }
            
            .property-panel {
                width: 250px;
            }
        }
        
        @media (max-width: 768px) {
            .node-panel, .property-panel {
                position: absolute;
                top: 0;
                bottom: 0;
                z-index: 10;
                transform: translateX(-100%);
                transition: transform 0.3s ease;
            }
            
            .node-panel.open, .property-panel.open {
                transform: translateX(0);
            }
        }
    </style>
</head>
<body class="bg-gray-50">
    <!-- 顶部导航 -->
    <nav class="bg-white shadow-sm border-b border-gray-200 sticky top-0 z-50">
        <div class="max-w-full mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <h1 class="text-xl font-bold text-gray-900">Dusk自动化平台</h1>
                    </div>
                    
                    <div class="hidden md:ml-6 md:flex md:space-x-8">
                        <a href="/admin" class="text-gray-600 hover:text-gray-900 px-3 py-2 text-sm font-medium">
                            管理后台
                        </a>
                        <a href="/logs/realtime" class="text-gray-600 hover:text-gray-900 px-3 py-2 text-sm font-medium">
                            实时日志
                        </a>
                        <span class="text-blue-600 px-3 py-2 text-sm font-medium">
                            工作流编辑器
                        </span>
                    </div>
                </div>
                
                <div class="flex items-center space-x-4">
                    <!-- 移动端菜单按钮 -->
                    <button class="md:hidden p-2 rounded-md text-gray-600 hover:text-gray-900 hover:bg-gray-100">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
                        </svg>
                    </button>
                    
                    <!-- 帮助按钮 -->
                    <button onclick="showHelp()" class="p-2 rounded-md text-gray-600 hover:text-gray-900 hover:bg-gray-100">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                                  d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </button>
                    
                    <!-- 全屏按钮 -->
                    <button onclick="toggleFullscreen()" class="p-2 rounded-md text-gray-600 hover:text-gray-900 hover:bg-gray-100">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                                  d="M4 8V4m0 0h4M4 4l5 5m11-1V4m0 0h-4m4 0l-5 5M4 16v4m0 0h4m-4 0l5-5m11 5l-5-5m5 5v-4m0 4h-4"></path>
                        </svg>
                    </button>
                </div>
            </div>
        </div>
    </nav>

    <!-- 工作流编辑器容器 -->
    <div id="workflow-editor" v-cloak></div>

    <!-- 帮助模态框 -->
    <div id="helpModal" class="fixed inset-0 bg-black bg-opacity-50 hidden z-50 flex items-center justify-center p-4">
        <div class="bg-white rounded-lg max-w-2xl w-full max-h-[80vh] overflow-y-auto">
            <div class="p-6">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-lg font-semibold text-gray-900">工作流编辑器帮助</h3>
                    <button onclick="hideHelp()" class="text-gray-400 hover:text-gray-600">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>
                
                <div class="space-y-6">
                    <div>
                        <h4 class="font-medium text-gray-900 mb-2">基本操作</h4>
                        <ul class="text-sm text-gray-600 space-y-1">
                            <li>• 从左侧节点库拖拽节点到画布</li>
                            <li>• 点击节点查看和编辑属性</li>
                            <li>• 拖拽节点的连接点创建连接</li>
                            <li>• 使用鼠标滚轮缩放画布</li>
                            <li>• 拖拽空白区域移动画布</li>
                        </ul>
                    </div>
                    
                    <div>
                        <h4 class="font-medium text-gray-900 mb-2">节点类型</h4>
                        <ul class="text-sm text-gray-600 space-y-1">
                            <li>• <span class="font-medium text-green-600">开始节点</span>：工作流的起始点</li>
                            <li>• <span class="font-medium text-blue-600">动作节点</span>：执行具体操作</li>
                            <li>• <span class="font-medium text-yellow-600">条件节点</span>：根据条件分支</li>
                            <li>• <span class="font-medium text-red-600">结束节点</span>：工作流的结束点</li>
                        </ul>
                    </div>
                    
                    <div>
                        <h4 class="font-medium text-gray-900 mb-2">快捷键</h4>
                        <ul class="text-sm text-gray-600 space-y-1">
                            <li>• <kbd class="bg-gray-100 px-1 rounded">Ctrl+S</kbd> 保存工作流</li>
                            <li>• <kbd class="bg-gray-100 px-1 rounded">Ctrl+Z</kbd> 撤销操作</li>
                            <li>• <kbd class="bg-gray-100 px-1 rounded">Delete</kbd> 删除选中节点</li>
                            <li>• <kbd class="bg-gray-100 px-1 rounded">Ctrl+A</kbd> 全选节点</li>
                            <li>• <kbd class="bg-gray-100 px-1 rounded">F11</kbd> 全屏模式</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- 加载提示 -->
    <div id="loadingOverlay" class="fixed inset-0 bg-white bg-opacity-90 flex items-center justify-center z-50">
        <div class="text-center">
            <div class="loading-spinner w-8 h-8 border-4 border-blue-600 border-t-transparent rounded-full mx-auto mb-4"></div>
            <p class="text-gray-600">正在加载工作流编辑器...</p>
        </div>
    </div>

    <script>
        // 隐藏加载提示
        window.addEventListener('load', function() {
            setTimeout(() => {
                document.getElementById('loadingOverlay').style.display = 'none';
            }, 1000);
        });

        // 帮助模态框
        function showHelp() {
            document.getElementById('helpModal').classList.remove('hidden');
        }

        function hideHelp() {
            document.getElementById('helpModal').classList.add('hidden');
        }

        // 全屏切换
        function toggleFullscreen() {
            if (!document.fullscreenElement) {
                document.documentElement.requestFullscreen();
            } else {
                document.exitFullscreen();
            }
        }

        // 键盘快捷键
        document.addEventListener('keydown', function(e) {
            if (e.ctrlKey || e.metaKey) {
                switch(e.key) {
                    case 's':
                        e.preventDefault();
                        // 触发保存
                        console.log('保存工作流');
                        break;
                    case 'z':
                        e.preventDefault();
                        // 触发撤销
                        console.log('撤销操作');
                        break;
                    case 'a':
                        e.preventDefault();
                        // 触发全选
                        console.log('全选节点');
                        break;
                }
            } else if (e.key === 'Delete') {
                // 触发删除
                console.log('删除选中节点');
            } else if (e.key === 'F11') {
                e.preventDefault();
                toggleFullscreen();
            }
        });

        // 阻止默认的拖拽行为
        document.addEventListener('dragover', function(e) {
            e.preventDefault();
        });

        document.addEventListener('drop', function(e) {
            e.preventDefault();
        });
    </script>
</body>
</html>

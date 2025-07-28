<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>实时日志查看器 - Dusk自动化平台</title>
    
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    
    <!-- Alpine.js -->
    <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>
    
    <!-- Livewire -->
    @livewireStyles
    
    <style>
        [x-cloak] { display: none !important; }
    </style>
</head>
<body class="bg-gray-100 dark:bg-gray-900">
    <div class="min-h-screen">
        <!-- 顶部导航 -->
        <nav class="bg-white dark:bg-gray-800 shadow-sm border-b border-gray-200 dark:border-gray-700">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between h-16">
                    <div class="flex items-center">
                        <h1 class="text-xl font-semibold text-gray-900 dark:text-white">
                            Dusk自动化平台
                        </h1>
                    </div>
                    
                    <div class="flex items-center space-x-4">
                        <a href="/admin" 
                           class="text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white">
                            返回管理后台
                        </a>
                        
                        <button onclick="toggleDarkMode()" 
                                class="p-2 rounded-md text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                                      d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"></path>
                            </svg>
                        </button>
                    </div>
                </div>
            </div>
        </nav>

        <!-- 主要内容 -->
        <main class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 shadow rounded-lg overflow-hidden">
                @livewire('realtime-log-viewer', [
                    'executionId' => $executionId ?? null,
                    'taskId' => $taskId ?? null
                ])
            </div>
        </main>

        <!-- 快捷键提示 -->
        <div class="fixed bottom-4 right-4 bg-black bg-opacity-75 text-white text-xs p-3 rounded-lg" 
             x-data="{ show: false }" 
             x-show="show" 
             x-cloak
             @keydown.window.ctrl.h="show = !show"
             @keydown.window.escape="show = false">
            <div class="space-y-1">
                <div><kbd class="bg-gray-700 px-1 rounded">Ctrl+H</kbd> 显示/隐藏帮助</div>
                <div><kbd class="bg-gray-700 px-1 rounded">Ctrl+L</kbd> 清空日志</div>
                <div><kbd class="bg-gray-700 px-1 rounded">Ctrl+S</kbd> 切换自动滚动</div>
                <div><kbd class="bg-gray-700 px-1 rounded">Ctrl+E</kbd> 导出日志</div>
                <div><kbd class="bg-gray-700 px-1 rounded">F5</kbd> 刷新页面</div>
            </div>
        </div>

        <!-- 帮助按钮 -->
        <button onclick="toggleHelp()" 
                class="fixed bottom-4 left-4 bg-blue-600 hover:bg-blue-700 text-white p-3 rounded-full shadow-lg">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                      d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
            </svg>
        </button>
    </div>

    @livewireScripts
    
    <script>
        // 暗色模式切换
        function toggleDarkMode() {
            document.documentElement.classList.toggle('dark');
            localStorage.setItem('darkMode', document.documentElement.classList.contains('dark'));
        }

        // 初始化暗色模式
        if (localStorage.getItem('darkMode') === 'true' || 
            (!localStorage.getItem('darkMode') && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
            document.documentElement.classList.add('dark');
        }

        // 帮助提示
        function toggleHelp() {
            const event = new KeyboardEvent('keydown', {
                key: 'h',
                ctrlKey: true,
                bubbles: true
            });
            window.dispatchEvent(event);
        }

        // 全局快捷键
        document.addEventListener('keydown', function(e) {
            if (e.ctrlKey) {
                switch(e.key) {
                    case 'l':
                        e.preventDefault();
                        Livewire.dispatch('clearLogs');
                        break;
                    case 's':
                        e.preventDefault();
                        // 触发自动滚动切换
                        break;
                    case 'e':
                        e.preventDefault();
                        Livewire.dispatch('exportLogs');
                        break;
                }
            }
        });

        // 页面可见性变化时的处理
        document.addEventListener('visibilitychange', function() {
            if (document.visibilityState === 'visible') {
                // 页面变为可见时，可以触发日志刷新
                console.log('页面变为可见，刷新日志');
            }
        });

        // WebSocket连接状态监控
        window.addEventListener('online', function() {
            console.log('网络连接恢复');
        });

        window.addEventListener('offline', function() {
            console.log('网络连接断开');
        });
    </script>
</body>
</html>

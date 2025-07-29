<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>测试日志弹窗</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 p-8">
    <div class="max-w-4xl mx-auto">
        <h1 class="text-2xl font-bold mb-4">测试日志弹窗</h1>
        
        <button id="open-modal" class="px-4 py-2 bg-blue-500 text-white rounded hover:bg-blue-600">
            打开日志弹窗
        </button>
        
        <!-- 模态框 -->
        <div id="modal" class="fixed inset-0 bg-black bg-opacity-50 hidden z-50">
            <div class="flex items-center justify-center min-h-screen p-4">
                <div class="bg-white rounded-lg shadow-xl max-w-6xl w-full max-h-[90vh] overflow-hidden">
                    <!-- 模态框头部 -->
                    <div class="flex items-center justify-between p-4 border-b">
                        <h2 class="text-lg font-semibold">任务日志 - 测试任务</h2>
                        <button id="close-modal" class="text-gray-400 hover:text-gray-600">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                        </button>
                    </div>
                    
                    <!-- 模态框内容 -->
                    <div class="p-4">
                        @include('components.realtime-logs-modal', ['taskId' => 1])
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.getElementById('open-modal').addEventListener('click', function() {
            document.getElementById('modal').classList.remove('hidden');
        });
        
        document.getElementById('close-modal').addEventListener('click', function() {
            document.getElementById('modal').classList.add('hidden');
        });
        
        // 点击背景关闭模态框
        document.getElementById('modal').addEventListener('click', function(e) {
            if (e.target === this) {
                this.classList.add('hidden');
            }
        });
    </script>
</body>
</html>

<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>截图预览测试</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 p-8">
    <div class="max-w-4xl mx-auto">
        <h1 class="text-3xl font-bold text-gray-900 mb-8">截图预览功能测试</h1>
        
        <!-- 测试截图预览组件 -->
        <div class="bg-white rounded-lg shadow p-6 mb-8">
            <h2 class="text-xl font-semibold mb-4">截图预览列组件测试</h2>
            
            <!-- 模拟有截图的记录 -->
            <div class="border border-gray-200 rounded p-4 mb-4">
                <h3 class="font-medium mb-2">模拟有截图的日志记录</h3>
                <div class="flex items-center space-x-4">
                    <div class="text-sm text-gray-600">执行ID: 123</div>
                    <div class="text-sm text-gray-600">级别: info</div>
                    <div class="text-sm text-gray-600">消息: 截图测试</div>
                    
                    <!-- 这里会显示截图预览 -->
                    <div class="flex items-center space-x-2">
                        <div class="w-12 h-12 bg-gray-200 rounded border flex items-center justify-center">
                            <svg class="w-6 h-6 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                            </svg>
                        </div>
                        <div class="flex flex-col space-y-1">
                            <button class="text-xs text-blue-600 hover:text-blue-800">预览</button>
                            <button class="text-xs text-gray-600 hover:text-gray-800">打开</button>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- 模拟无截图的记录 -->
            <div class="border border-gray-200 rounded p-4">
                <h3 class="font-medium mb-2">模拟无截图的日志记录</h3>
                <div class="flex items-center space-x-4">
                    <div class="text-sm text-gray-600">执行ID: 124</div>
                    <div class="text-sm text-gray-600">级别: warning</div>
                    <div class="text-sm text-gray-600">消息: 普通日志</div>
                    
                    <!-- 无截图显示 -->
                    <div class="flex items-center text-gray-400">
                        <svg class="w-5 h-5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728L5.636 5.636m12.728 12.728L18.364 5.636M5.636 18.364l12.728-12.728"></path>
                        </svg>
                        <span class="text-xs">无截图</span>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- 功能说明 -->
        <div class="bg-blue-50 border border-blue-200 rounded-lg p-6">
            <h2 class="text-lg font-semibold text-blue-900 mb-3">功能说明</h2>
            <ul class="space-y-2 text-blue-800">
                <li class="flex items-start">
                    <span class="w-2 h-2 bg-blue-600 rounded-full mt-2 mr-3 flex-shrink-0"></span>
                    <span>在日志列表中，有截图的记录会显示缩略图预览</span>
                </li>
                <li class="flex items-start">
                    <span class="w-2 h-2 bg-blue-600 rounded-full mt-2 mr-3 flex-shrink-0"></span>
                    <span>点击缩略图或"预览"按钮可以在模态框中查看大图</span>
                </li>
                <li class="flex items-start">
                    <span class="w-2 h-2 bg-blue-600 rounded-full mt-2 mr-3 flex-shrink-0"></span>
                    <span>支持在新窗口打开截图和下载功能</span>
                </li>
                <li class="flex items-start">
                    <span class="w-2 h-2 bg-blue-600 rounded-full mt-2 mr-3 flex-shrink-0"></span>
                    <span>截图存储在 storage/app/screenshots/ 目录下</span>
                </li>
                <li class="flex items-start">
                    <span class="w-2 h-2 bg-blue-600 rounded-full mt-2 mr-3 flex-shrink-0"></span>
                    <span>无截图的记录显示"无截图"标识</span>
                </li>
            </ul>
        </div>
        
        <!-- 返回链接 -->
        <div class="mt-8 text-center">
            <a href="/admin" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700">
                返回管理后台
            </a>
        </div>
    </div>
</body>
</html>

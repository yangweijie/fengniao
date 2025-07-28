<div class="space-y-4">
    <!-- 基本信息 -->
    <div class="grid grid-cols-2 gap-4">
        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">执行ID</label>
            <p class="mt-1 text-sm text-gray-900 dark:text-white">{{ $log->execution->id ?? 'N/A' }}</p>
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">日志级别</label>
            <p class="mt-1">
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                    {{ match($log->level) {
                        'error' => 'bg-red-100 text-red-800 dark:bg-red-800 dark:text-red-100',
                        'warning' => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-800 dark:text-yellow-100',
                        'info' => 'bg-blue-100 text-blue-800 dark:bg-blue-800 dark:text-blue-100',
                        'debug' => 'bg-gray-100 text-gray-800 dark:bg-gray-800 dark:text-gray-100',
                        default => 'bg-green-100 text-green-800 dark:bg-green-800 dark:text-green-100',
                    } }}">
                    {{ strtoupper($log->level) }}
                </span>
            </p>
        </div>
    </div>

    <!-- 时间信息 -->
    <div>
        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">创建时间</label>
        <p class="mt-1 text-sm text-gray-900 dark:text-white">{{ $log->created_at->format('Y-m-d H:i:s') }}</p>
    </div>

    <!-- 消息内容 -->
    <div>
        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">消息内容</label>
        <div class="mt-1 p-3 bg-gray-50 dark:bg-gray-900 rounded-md">
            <pre class="text-sm text-gray-900 dark:text-white whitespace-pre-wrap">{{ $log->message }}</pre>
        </div>
    </div>

    <!-- 上下文信息 -->
    @if($log->context && !empty($log->context))
    <div>
        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">上下文信息</label>
        <div class="mt-1 p-3 bg-gray-50 dark:bg-gray-900 rounded-md">
            <pre class="text-sm text-gray-900 dark:text-white whitespace-pre-wrap">{{ json_encode($log->context, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
        </div>
    </div>
    @endif

    <!-- 截图信息 -->
    @if($log->screenshot_path)
    <div>
        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">相关截图</label>
        <div class="mt-1 space-y-3">
            <!-- 截图预览 -->
            <div class="border border-gray-200 dark:border-gray-700 rounded-lg overflow-hidden bg-white dark:bg-gray-800">
                <img src="{{ route('screenshot.view', $log->screenshot_path) }}"
                     alt="截图预览"
                     class="w-full max-w-md mx-auto block cursor-pointer hover:opacity-90 transition-opacity"
                     onclick="openScreenshotModal('{{ route('screenshot.view', $log->screenshot_path) }}', '{{ $log->screenshot_path }}')"
                     style="max-height: 300px; object-fit: contain;">
            </div>

            <!-- 操作按钮 -->
            <div class="flex space-x-2">
                <a href="{{ route('screenshot.view', $log->screenshot_path) }}"
                   target="_blank"
                   class="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-2M7 7l10 10M17 7v4"></path>
                    </svg>
                    新窗口打开
                </a>

                <button type="button"
                        onclick="openScreenshotModal('{{ route('screenshot.view', $log->screenshot_path) }}', '{{ $log->screenshot_path }}')"
                        class="inline-flex items-center px-3 py-2 border border-gray-300 dark:border-gray-600 text-sm leading-4 font-medium rounded-md text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0zM10 7v3m0 0v3m0-3h3m-3 0H7"></path>
                    </svg>
                    放大查看
                </button>
            </div>

            <!-- 截图文件信息 -->
            <div class="text-xs text-gray-500 dark:text-gray-400">
                <p>文件名: {{ $log->screenshot_path }}</p>
                <p>路径: storage/app/public/screenshots/{{ $log->screenshot_path }}</p>
            </div>
        </div>
    </div>
    @endif

    <!-- 执行信息 -->
    @if($log->execution)
    <div class="border-t border-gray-200 dark:border-gray-700 pt-4">
        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">执行信息</label>
        <div class="grid grid-cols-2 gap-4 text-sm">
            <div>
                <span class="text-gray-500 dark:text-gray-400">状态:</span>
                <span class="ml-2 inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                    {{ match($log->execution->status) {
                        'completed' => 'bg-green-100 text-green-800 dark:bg-green-800 dark:text-green-100',
                        'failed' => 'bg-red-100 text-red-800 dark:bg-red-800 dark:text-red-100',
                        'running' => 'bg-blue-100 text-blue-800 dark:bg-blue-800 dark:text-blue-100',
                        default => 'bg-gray-100 text-gray-800 dark:bg-gray-800 dark:text-gray-100',
                    } }}">
                    {{ $log->execution->status }}
                </span>
            </div>
            <div>
                <span class="text-gray-500 dark:text-gray-400">开始时间:</span>
                <span class="ml-2 text-gray-900 dark:text-white">{{ $log->execution->started_at?->format('Y-m-d H:i:s') ?? 'N/A' }}</span>
            </div>
            @if($log->execution->completed_at)
            <div>
                <span class="text-gray-500 dark:text-gray-400">完成时间:</span>
                <span class="ml-2 text-gray-900 dark:text-white">{{ $log->execution->completed_at->format('Y-m-d H:i:s') }}</span>
            </div>
            @endif
            @if($log->execution->started_at && $log->execution->completed_at)
            <div>
                <span class="text-gray-500 dark:text-gray-400">执行时长:</span>
                <span class="ml-2 text-gray-900 dark:text-white">{{ $log->execution->started_at->diffForHumans($log->execution->completed_at, true) }}</span>
            </div>
            @endif
        </div>
    </div>
    @endif
</div>

<!-- 截图放大模态框 -->
<div id="screenshotModal" class="fixed inset-0 z-50 hidden bg-black bg-opacity-75 flex items-center justify-center p-4">
    <div class="relative max-w-7xl max-h-full">
        <!-- 关闭按钮 -->
        <button onclick="closeScreenshotModal()"
                class="absolute top-4 right-4 z-10 text-white hover:text-gray-300 bg-black bg-opacity-50 rounded-full p-2">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
            </svg>
        </button>

        <!-- 截图图片 -->
        <img id="modalScreenshot"
             src=""
             alt="截图预览"
             class="max-w-full max-h-full object-contain rounded-lg shadow-2xl">

        <!-- 图片信息 -->
        <div class="absolute bottom-4 left-4 bg-black bg-opacity-50 text-white px-3 py-2 rounded">
            <p id="modalScreenshotName" class="text-sm"></p>
        </div>
    </div>
</div>

<script>
function openScreenshotModal(imageUrl, filename) {
    const modal = document.getElementById('screenshotModal');
    const modalImg = document.getElementById('modalScreenshot');
    const modalName = document.getElementById('modalScreenshotName');

    modalImg.src = imageUrl;
    modalName.textContent = filename;
    modal.classList.remove('hidden');

    // 阻止背景滚动
    document.body.style.overflow = 'hidden';
}

function closeScreenshotModal() {
    const modal = document.getElementById('screenshotModal');
    modal.classList.add('hidden');

    // 恢复背景滚动
    document.body.style.overflow = '';
}

// 点击模态框背景关闭
document.getElementById('screenshotModal').addEventListener('click', function(e) {
    if (e.target === this) {
        closeScreenshotModal();
    }
});

// ESC键关闭模态框
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closeScreenshotModal();
    }
});
</script>

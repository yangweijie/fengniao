<div class="space-y-4">
    <!-- 截图预览 -->
    <div class="flex justify-center">
        <img src="{{ $screenshotUrl }}" 
             alt="截图预览" 
             class="max-w-full max-h-96 object-contain rounded-lg border border-gray-200 dark:border-gray-700">
    </div>
    
    <!-- 截图信息 -->
    <div class="bg-gray-50 dark:bg-gray-900 p-3 rounded-lg">
        <div class="text-sm space-y-1">
            <div>
                <span class="font-medium text-gray-700 dark:text-gray-300">文件名:</span>
                <span class="text-gray-900 dark:text-white ml-2">{{ $filename }}</span>
            </div>
            <div>
                <span class="font-medium text-gray-700 dark:text-gray-300">存储路径:</span>
                <span class="text-gray-900 dark:text-white ml-2">storage/app/public/screenshots/{{ $filename }}</span>
            </div>
        </div>
    </div>
    
    <!-- 操作按钮 -->
    <div class="flex justify-center space-x-3">
        <a href="{{ $screenshotUrl }}"
           target="_blank"
           class="inline-flex items-center px-4 py-2 border border-gray-300 dark:border-gray-600 text-sm font-medium rounded-md text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-2M7 7l10 10M17 7v4"></path>
            </svg>
            新窗口打开
        </a>
        
        <button type="button" 
                onclick="downloadScreenshot('{{ $screenshotUrl }}', '{{ $filename }}')"
                class="inline-flex items-center px-4 py-2 border border-gray-300 dark:border-gray-600 text-sm font-medium rounded-md text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3M3 17V7a2 2 0 012-2h6l2 2h6a2 2 0 012 2v10a2 2 0 01-2 2H5a2 2 0 01-2-2z"></path>
            </svg>
            下载图片
        </button>
    </div>
</div>

<script>
function downloadScreenshot(url, filename) {
    const link = document.createElement('a');
    link.href = url;
    link.download = filename;
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
}
</script>

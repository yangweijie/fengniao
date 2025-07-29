<div class="space-y-6 max-h-[80vh] overflow-y-auto" @click.stop>
    <!-- 概述 -->
    <div class="bg-blue-50 dark:bg-blue-900/20 p-4 rounded-lg border border-blue-200 dark:border-blue-800">
        <h3 class="text-lg font-semibold text-blue-900 dark:text-blue-100 mb-2">
            🚀 脚本编辑器功能概述
        </h3>
        <p class="text-blue-800 dark:text-blue-200">
            本编辑器支持PHP语法高亮、智能代码提示和参数跳转。输入方法名或模板名后按Tab键可快速插入代码。
        </p>
    </div>

    <!-- 标签页导航 -->
    <div x-data="{ activeTab: 'dusk' }" class="w-full" @click.stop>
        <!-- 标签页头部 -->
        <div class="flex space-x-1 bg-gray-100 dark:bg-gray-800 p-1 rounded-lg mb-4">
            <button @click.stop="activeTab = 'dusk'"
                    :class="activeTab === 'dusk' ? 'bg-white dark:bg-gray-700 text-blue-600 dark:text-blue-400' : 'text-gray-600 dark:text-gray-400'"
                    class="flex-1 py-2 px-4 rounded-md font-medium transition-colors"
                    type="button">
                🌐 Dusk浏览器宏
            </button>
            <button @click.stop="activeTab = 'http'"
                    :class="activeTab === 'http' ? 'bg-white dark:bg-gray-700 text-blue-600 dark:text-blue-400' : 'text-gray-600 dark:text-gray-400'"
                    class="flex-1 py-2 px-4 rounded-md font-medium transition-colors"
                    type="button">
                📡 HTTP请求宏
            </button>
            <button @click.stop="activeTab = 'variables'"
                    :class="activeTab === 'variables' ? 'bg-white dark:bg-gray-700 text-blue-600 dark:text-blue-400' : 'text-gray-600 dark:text-gray-400'"
                    class="flex-1 py-2 px-4 rounded-md font-medium transition-colors"
                    type="button">
                📦 可用变量&Facade
            </button>
            <button @click.stop="activeTab = 'templates'"
                    :class="activeTab === 'templates' ? 'bg-white dark:bg-gray-700 text-blue-600 dark:text-blue-400' : 'text-gray-600 dark:text-gray-400'"
                    class="flex-1 py-2 px-4 rounded-md font-medium transition-colors"
                    type="button">
                📋 代码模板
            </button>
        </div>

        <!-- Dusk浏览器宏 -->
        <div x-show="activeTab === 'dusk'" class="space-y-4">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">🌐 Dusk浏览器自动化宏</h3>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <!-- 基础操作 -->
                <div class="bg-gray-50 dark:bg-gray-800 p-4 rounded-lg">
                    <h4 class="font-semibold text-gray-900 dark:text-gray-100 mb-3">基础操作</h4>
                    <div class="space-y-2 text-sm">
                        <div><code class="bg-blue-100 dark:bg-blue-900 px-2 py-1 rounded">$browser->visit()</code> - 访问页面</div>
                        <div><code class="bg-blue-100 dark:bg-blue-900 px-2 py-1 rounded">$browser->click()</code> - 点击元素</div>
                        <div><code class="bg-blue-100 dark:bg-blue-900 px-2 py-1 rounded">$browser->type()</code> - 输入文本</div>
                        <div><code class="bg-blue-100 dark:bg-blue-900 px-2 py-1 rounded">$browser->screenshot()</code> - 截图</div>
                        <div><code class="bg-blue-100 dark:bg-blue-900 px-2 py-1 rounded">$browser->waitFor()</code> - 等待元素</div>
                    </div>
                </div>

                <!-- 智能宏方法 -->
                <div class="bg-gray-50 dark:bg-gray-800 p-4 rounded-lg">
                    <h4 class="font-semibold text-gray-900 dark:text-gray-100 mb-3">智能宏方法</h4>
                    <div class="space-y-2 text-sm">
                        <div><code class="bg-green-100 dark:bg-green-900 px-2 py-1 rounded">$browser->waitForPageLoad()</code> - 等待页面加载</div>
                        <div><code class="bg-green-100 dark:bg-green-900 px-2 py-1 rounded">$browser->smartClick()</code> - 智能点击</div>
                        <div><code class="bg-green-100 dark:bg-green-900 px-2 py-1 rounded">$browser->smartLogin()</code> - 智能登录</div>
                        <div><code class="bg-green-100 dark:bg-green-900 px-2 py-1 rounded">$browser->fillForm()</code> - 批量填表</div>
                        <div><code class="bg-green-100 dark:bg-green-900 px-2 py-1 rounded">$browser->acceptCookies()</code> - 接受Cookie</div>
                    </div>
                </div>

                <!-- 数据采集 -->
                <div class="bg-gray-50 dark:bg-gray-800 p-4 rounded-lg">
                    <h4 class="font-semibold text-gray-900 dark:text-gray-100 mb-3">数据采集</h4>
                    <div class="space-y-2 text-sm">
                        <div><code class="bg-purple-100 dark:bg-purple-900 px-2 py-1 rounded">$browser->getAllText()</code> - 获取所有文本</div>
                        <div><code class="bg-purple-100 dark:bg-purple-900 px-2 py-1 rounded">$browser->hasElement()</code> - 检查元素存在</div>
                        <div><code class="bg-purple-100 dark:bg-purple-900 px-2 py-1 rounded">$browser->screenshotWithTimestamp()</code> - 带时间戳截图</div>
                    </div>
                </div>

                <!-- 高级功能 -->
                <div class="bg-gray-50 dark:bg-gray-800 p-4 rounded-lg">
                    <h4 class="font-semibold text-gray-900 dark:text-gray-100 mb-3">高级功能</h4>
                    <div class="space-y-2 text-sm">
                        <div><code class="bg-orange-100 dark:bg-orange-900 px-2 py-1 rounded">$browser->waitForAnyElement()</code> - 等待任意元素</div>
                        <div><code class="bg-orange-100 dark:bg-orange-900 px-2 py-1 rounded">$browser->humanType()</code> - 模拟人类输入</div>
                        <div><code class="bg-orange-100 dark:bg-orange-900 px-2 py-1 rounded">$browser->closeAds()</code> - 关闭广告</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- HTTP请求宏 -->
        <div x-show="activeTab === 'http'" class="space-y-4">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">📡 HTTP请求宏</h3>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <!-- 基础请求 -->
                <div class="bg-gray-50 dark:bg-gray-800 p-4 rounded-lg">
                    <h4 class="font-semibold text-gray-900 dark:text-gray-100 mb-3">基础请求</h4>
                    <div class="space-y-2 text-sm">
                        <div><code class="bg-blue-100 dark:bg-blue-900 px-2 py-1 rounded">Http::get()</code> - GET请求</div>
                        <div><code class="bg-blue-100 dark:bg-blue-900 px-2 py-1 rounded">Http::post()</code> - POST请求</div>
                        <div><code class="bg-blue-100 dark:bg-blue-900 px-2 py-1 rounded">Http::withHeaders()</code> - 设置请求头</div>
                        <div><code class="bg-blue-100 dark:bg-blue-900 px-2 py-1 rounded">Http::withToken()</code> - 设置Token</div>
                    </div>
                </div>

                <!-- 智能请求 -->
                <div class="bg-gray-50 dark:bg-gray-800 p-4 rounded-lg">
                    <h4 class="font-semibold text-gray-900 dark:text-gray-100 mb-3">智能请求</h4>
                    <div class="space-y-2 text-sm">
                        <div><code class="bg-green-100 dark:bg-green-900 px-2 py-1 rounded">Http::smartRetry()</code> - 智能重试</div>
                        <div><code class="bg-green-100 dark:bg-green-900 px-2 py-1 rounded">Http::withLogging()</code> - 带日志记录</div>
                        <div><code class="bg-green-100 dark:bg-green-900 px-2 py-1 rounded">Http::jsonApi()</code> - JSON API</div>
                        <div><code class="bg-green-100 dark:bg-green-900 px-2 py-1 rounded">Http::apiWithAuth()</code> - 认证API</div>
                    </div>
                </div>

                <!-- 文件处理 -->
                <div class="bg-gray-50 dark:bg-gray-800 p-4 rounded-lg">
                    <h4 class="font-semibold text-gray-900 dark:text-gray-100 mb-3">文件处理</h4>
                    <div class="space-y-2 text-sm">
                        <div><code class="bg-purple-100 dark:bg-purple-900 px-2 py-1 rounded">Http::uploadFile()</code> - 文件上传</div>
                        <div><code class="bg-purple-100 dark:bg-purple-900 px-2 py-1 rounded">Http::downloadFile()</code> - 文件下载</div>
                        <div><code class="bg-purple-100 dark:bg-purple-900 px-2 py-1 rounded">Http::formData()</code> - 表单数据</div>
                    </div>
                </div>

                <!-- 高级功能 -->
                <div class="bg-gray-50 dark:bg-gray-800 p-4 rounded-lg">
                    <h4 class="font-semibold text-gray-900 dark:text-gray-100 mb-3">高级功能</h4>
                    <div class="space-y-2 text-sm">
                        <div><code class="bg-orange-100 dark:bg-orange-900 px-2 py-1 rounded">Http::batchRequests()</code> - 批量请求</div>
                        <div><code class="bg-orange-100 dark:bg-orange-900 px-2 py-1 rounded">Http::healthCheck()</code> - 健康检查</div>
                        <div><code class="bg-orange-100 dark:bg-orange-900 px-2 py-1 rounded">Http::withRateLimit()</code> - 速率限制</div>
                        <div><code class="bg-orange-100 dark:bg-orange-900 px-2 py-1 rounded">Http::asBrowser()</code> - 模拟浏览器</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- 可用变量和Facade -->
        <div x-show="activeTab === 'variables'" class="space-y-4">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">📦 可用变量和Facade</h3>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <!-- 浏览器任务变量 -->
                <div class="bg-gray-50 dark:bg-gray-800 p-4 rounded-lg">
                    <h4 class="font-semibold text-gray-900 dark:text-gray-100 mb-3">浏览器任务变量</h4>
                    <div class="space-y-2 text-sm">
                        <div><code class="bg-blue-100 dark:bg-blue-900 px-2 py-1 rounded">$browser</code> - Dusk浏览器实例</div>
                        <div><code class="bg-blue-100 dark:bg-blue-900 px-2 py-1 rounded">$task</code> - 当前任务对象</div>
                        <div><code class="bg-blue-100 dark:bg-blue-900 px-2 py-1 rounded">$config</code> - 任务配置数组</div>
                        <div><code class="bg-blue-100 dark:bg-blue-900 px-2 py-1 rounded">$loginConfig</code> - 登录配置</div>
                    </div>
                </div>

                <!-- API任务变量 -->
                <div class="bg-gray-50 dark:bg-gray-800 p-4 rounded-lg">
                    <h4 class="font-semibold text-gray-900 dark:text-gray-100 mb-3">API任务变量</h4>
                    <div class="space-y-2 text-sm">
                        <div><code class="bg-green-100 dark:bg-green-900 px-2 py-1 rounded">$http</code> - HTTP客户端 (等同于Http::)</div>
                        <div><code class="bg-green-100 dark:bg-green-900 px-2 py-1 rounded">$task</code> - 当前任务对象</div>
                        <div><code class="bg-green-100 dark:bg-green-900 px-2 py-1 rounded">$config</code> - 任务配置数组</div>
                        <div><code class="bg-green-100 dark:bg-green-900 px-2 py-1 rounded">$log</code> - 日志记录函数</div>
                    </div>
                </div>

                <!-- Laravel Facade -->
                <div class="bg-gray-50 dark:bg-gray-800 p-4 rounded-lg">
                    <h4 class="font-semibold text-gray-900 dark:text-gray-100 mb-3">Laravel Facade</h4>
                    <div class="space-y-2 text-sm">
                        <div><code class="bg-purple-100 dark:bg-purple-900 px-2 py-1 rounded">Http::</code> - HTTP客户端Facade</div>
                        <div><code class="bg-purple-100 dark:bg-purple-900 px-2 py-1 rounded">Log::</code> - 日志Facade</div>
                        <div><code class="bg-purple-100 dark:bg-purple-900 px-2 py-1 rounded">Cache::</code> - 缓存Facade</div>
                        <div><code class="bg-purple-100 dark:bg-purple-900 px-2 py-1 rounded">Storage::</code> - 存储Facade</div>
                    </div>
                </div>

                <!-- 辅助函数 -->
                <div class="bg-gray-50 dark:bg-gray-800 p-4 rounded-lg">
                    <h4 class="font-semibold text-gray-900 dark:text-gray-100 mb-3">辅助函数</h4>
                    <div class="space-y-2 text-sm">
                        <div><code class="bg-orange-100 dark:bg-orange-900 px-2 py-1 rounded">env()</code> - 获取环境变量</div>
                        <div><code class="bg-orange-100 dark:bg-orange-900 px-2 py-1 rounded">config()</code> - 获取配置</div>
                        <div><code class="bg-orange-100 dark:bg-orange-900 px-2 py-1 rounded">now()</code> - 当前时间</div>
                        <div><code class="bg-orange-100 dark:bg-orange-900 px-2 py-1 rounded">sleep()</code> - 暂停执行</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- 代码模板 -->
        <div x-show="activeTab === 'templates'" class="space-y-4">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">📋 代码模板</h3>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <!-- Dusk模板 -->
                <div class="bg-gray-50 dark:bg-gray-800 p-4 rounded-lg">
                    <h4 class="font-semibold text-gray-900 dark:text-gray-100 mb-3">Dusk模板</h4>
                    <div class="space-y-2 text-sm">
                        <div><code class="bg-blue-100 dark:bg-blue-900 px-2 py-1 rounded">dusk-basic</code> - 基础脚本模板</div>
                        <div><code class="bg-blue-100 dark:bg-blue-900 px-2 py-1 rounded">dusk-login</code> - 登录脚本模板</div>
                        <div><code class="bg-blue-100 dark:bg-blue-900 px-2 py-1 rounded">dusk-form</code> - 表单填写模板</div>
                        <div><code class="bg-blue-100 dark:bg-blue-900 px-2 py-1 rounded">dusk-search</code> - 搜索脚本模板</div>
                        <div><code class="bg-blue-100 dark:bg-blue-900 px-2 py-1 rounded">dusk-scraping</code> - 数据采集模板</div>
                        <div><code class="bg-blue-100 dark:bg-blue-900 px-2 py-1 rounded">dusk-error</code> - 错误处理模板</div>
                    </div>
                </div>

                <!-- HTTP模板 -->
                <div class="bg-gray-50 dark:bg-gray-800 p-4 rounded-lg">
                    <h4 class="font-semibold text-gray-900 dark:text-gray-100 mb-3">HTTP模板</h4>
                    <div class="space-y-2 text-sm">
                        <div><code class="bg-green-100 dark:bg-green-900 px-2 py-1 rounded">api-template</code> - API请求模板</div>
                        <div><code class="bg-green-100 dark:bg-green-900 px-2 py-1 rounded">http-smart-retry</code> - 智能重试模板</div>
                        <div><code class="bg-green-100 dark:bg-green-900 px-2 py-1 rounded">http-batch-requests</code> - 批量请求模板</div>
                        <div><code class="bg-green-100 dark:bg-green-900 px-2 py-1 rounded">http-download-file</code> - 文件下载模板</div>
                        <div><code class="bg-green-100 dark:bg-green-900 px-2 py-1 rounded">http-health-check</code> - 健康检查模板</div>
                        <div><code class="bg-green-100 dark:bg-green-900 px-2 py-1 rounded">http-rate-limited</code> - 速率限制模板</div>
                    </div>
                </div>
            </div>

            <!-- 使用说明 -->
            <div class="bg-yellow-50 dark:bg-yellow-900/20 p-4 rounded-lg border border-yellow-200 dark:border-yellow-800">
                <h4 class="font-semibold text-yellow-900 dark:text-yellow-100 mb-2">💡 使用说明</h4>
                <ul class="text-yellow-800 dark:text-yellow-200 text-sm space-y-1">
                    <li>• 在编辑器中输入模板名称（如 <code>dusk-basic</code>），然后按Tab键插入完整模板</li>
                    <li>• 输入方法名（如 <code>$browser-></code> 或 <code>Http::</code>）会显示智能提示</li>
                    <li>• 使用Tab键可以在模板参数之间跳转</li>
                    <li>• 所有模板都包含注释和示例，便于理解和修改</li>
                </ul>
            </div>
        </div>
    </div>
</div>

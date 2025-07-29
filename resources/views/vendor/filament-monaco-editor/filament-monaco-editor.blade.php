<x-dynamic-component :component="$getFieldWrapperView()" :field="$field" class="overflow-hidden">

    <div x-data="{
        monacoContent: $wire.$entangle('{{ $getStatePath() }}'),
        previewContent: '',
        fullScreenModeEnabled: false,
        showPreview: false,
        monacoLanguage: '{{ $getLanguage() }}',
        monacoPlaceholder: {{ (int) $getShowPlaceholder() }},
        monacoPlaceholderText: '{{ $getPlaceholderText() }}',
        monacoLoader: {{ (int) $getShowLoader() }},
        monacoFontSize: '{{ $getFontSize() }}',
        lineNumbersMinChars: {{ $getLineNumbersMinChars() }},
        automaticLayout: {{ (int) $getAutomaticLayout() }},
        monacoId: $id('monaco-editor'),

        toggleFullScreenMode() {
            this.fullScreenModeEnabled = !this.fullScreenModeEnabled;
            this.fullScreenModeEnabled ? document.body.classList.add('overflow-hidden')
                                       : document.body.classList.remove('overflow-hidden');
            $el.style.width = this.fullScreenModeEnabled ? '100vw'
                                                         : $el.parentElement.clientWidth + 'px';
        },

        monacoEditor(editor){
            editor.onDidChangeModelContent((e) => {
                this.monacoContent = editor.getValue();
                this.updatePlaceholder(editor.getValue());
            });

            editor.onDidBlurEditorWidget(() => {
                this.updatePlaceholder(editor.getValue());
            });

            editor.onDidFocusEditorWidget(() => {
                this.updatePlaceholder(editor.getValue());
            });
        },

        updatePlaceholder: function(value) {
            if (value == '') {
                this.monacoPlaceholder = true;
                return;
            }
            this.monacoPlaceholder = false;
        },

        monacoEditorFocus(){
            document.getElementById(this.monacoId).dispatchEvent(
                new CustomEvent('monaco-editor-focused', { monacoId: this.monacoId })
            );
        },

        monacoEditorAddLoaderScriptToHead() {
            script = document.createElement('script');
            script.src = 'https://cdnjs.cloudflare.com/ajax/libs/monaco-editor/0.39.0/min/vs/loader.min.js';
            document.head.appendChild(script);
        },

        wrapPreview(value){
            return `<head>{{ $getPreviewHeadEndContent() }}</head>` +
            `&lt;body {{ $getPreviewBodyAttributes() }}&gt;` +
            `{{ $getPreviewBodyStartContent() }}` +
            `${value}` +
            `{{ $getPreviewBodyEndContent() }}` +
            `&lt;/body&gt;`;
        },

        showCodePreview(){
            this.previewContent = this.wrapPreview(this.monacoContent);
            this.showPreview = true;
        },

    }" x-init="
        previewContent = wrapPreview(monacoContent);
        $el.style.height = '500px';
        $watch('fullScreenModeEnabled', value => {
            if (value) {
                $el.style.height = '100vh';
            } else {
                $el.style.height = '500px';
            }
        });

        if(typeof _amdLoaderGlobal == 'undefined'){
            monacoEditorAddLoaderScriptToHead();
        }

        monacoLoaderInterval = setInterval(() => {
            if(typeof _amdLoaderGlobal !== 'undefined'){

                // Based on https://jsfiddle.net/developit/bwgkr6uq/ which works without needing service worker. Provided by loader.min.js.
                require.config({ paths: { 'vs': 'https://cdnjs.cloudflare.com/ajax/libs/monaco-editor/0.39.0/min/vs' }});
                let proxy = URL.createObjectURL(new Blob([` self.MonacoEnvironment = { baseUrl: 'https://cdnjs.cloudflare.com/ajax/libs/monaco-editor/0.39.0/min' }; importScripts('https://cdnjs.cloudflare.com/ajax/libs/monaco-editor/0.39.0/min/vs/base/worker/workerMain.min.js');`], { type: 'text/javascript' }));
                window.MonacoEnvironment = { getWorkerUrl: () => proxy };

                require(['vs/editor/editor.main'], () => {

                    monaco.editor.defineTheme('custom', {{ $editorTheme() }});

                    // 设置PHP语言支持和Dusk语法提示
                    setupPhpLanguageAndDuskSnippets();

                    document.getElementById(monacoId).editor = monaco.editor.create($refs.monacoEditorElement, {
                        value: monacoContent,
                        theme: 'custom',
                        fontSize: monacoFontSize,
                        lineNumbersMinChars: lineNumbersMinChars,
                        automaticLayout: automaticLayout,
                        language: monacoLanguage
                    });
                    monacoEditor(document.getElementById(monacoId).editor);
                    document.getElementById(monacoId).addEventListener('monaco-editor-focused', (event) => {
                        document.getElementById(monacoId).editor.focus();
                    });
                    updatePlaceholder(document.getElementById(monacoId).editor.getValue());
                });

                clearInterval(monacoLoaderInterval);
                monacoLoader = false;
            }
        }, 5); " :id="monacoId"
        class="fme-wrapper"
        :class="{ 'fme-full-screen': fullScreenModeEnabled }" x-cloak>
        <div class="fme-control-section">
            @if($getEnablePreview())
            <div x-data="{
                    repositionTabMarker(el){
                        this.$refs.marker.classList.remove('p-1');
                        this.$refs.marker.style.width   =   el.offsetWidth + 'px';
                        this.$refs.marker.style.height  =   el.offsetHeight + 'px';
                        this.$refs.marker.style.left    =   el.offsetLeft + 'px';
                    }
                }" x-cloak class="fme-code-preview-tab" wire:ignore>
                    <button type="button" @click="repositionTabMarker($el); showPreview = false;" class="fme-code-preview-tab-item">
                        {{ __("Code") }}
                    </button>
                    <button type="button" @click="repositionTabMarker($el); showCodePreview();" class="fme-code-preview-tab-item">
                        {{ __("Preview") }}
                    </button>
                    <div x-ref="marker" class="fme-code-preview-tab-marker-container p-1">
                        <div class="fme-code-preview-tab-marker"></div>
                    </div>
            </div>
            @endif
            <div class="flex items-center ml-auto">
                @if($getShowFullScreenToggle())
                    <button type="button" aria-label="{{ __("full_screen_btn_label") }}" class="fme-full-screen-btn" @click="toggleFullScreenMode()">
                        <svg class="fme-full-screen-btn-icon" x-show="!fullScreenModeEnabled" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M16 4l4 0l0 4" /><path d="M14 10l6 -6" /><path d="M8 20l-4 0l0 -4" /><path d="M4 20l6 -6" /><path d="M16 20l4 0l0 -4" /><path d="M14 14l6 6" /><path d="M8 4l-4 0l0 4" /><path d="M4 4l6 6" /></svg>
                        <svg class="fme-full-screen-btn-icon" x-show="fullScreenModeEnabled" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M5 9l4 0l0 -4" /><path d="M3 3l6 6" /><path d="M5 15l4 0l0 4" /><path d="M3 21l6 -6" /><path d="M19 9l-4 0l0 -4" /><path d="M15 9l6 -6" /><path d="M19 15l-4 0l0 4" /><path d="M15 15l6 6" /></svg>
                    </button>
                @endif
            </div>
        </div>
        <div class="h-full w-full">
            <div class="fme-container" x-show="!showPreview">
                <!-- Loader -->
                <div x-show="monacoLoader" class="fme-loader">
                    <svg class="fme-loader-icon" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                </div>

                <!-- Editor -->
                <div x-show="!monacoLoader" class="fme-element-wrapper">
                    <div x-ref="monacoEditorElement" class="fme-element" wire:ignore style="height: 100%"></div>
                    <div x-ref="monacoPlaceholderElement" x-show="monacoPlaceholder" @click="monacoEditorFocus()" :style="'font-size: ' + monacoFontSize" class="fme-placeholder" x-text="monacoPlaceholderText"></div>
                </div>
            </div>

            <div class="fme-preview-wrapper">
                <!-- Preview -->
                <iframe class="fme-preview" :srcdoc="previewContent" x-show="showPreview" wire:ignore></iframe>
            </div>
        </div>
    </div>

</x-dynamic-component>

<script>
// PHP语言支持和Dusk语法提示
function setupPhpLanguageAndDuskSnippets() {
    try {
        console.log('Setting up PHP language and Dusk snippets...');

        // 确保PHP语言已注册
        const languages = monaco.languages.getLanguages();
        const phpLanguage = languages.find(lang => lang.id === 'php');

        if (!phpLanguage) {
            console.log('Registering PHP language...');
            // 注册PHP语言
            monaco.languages.register({ id: 'php' });
        }

        // 设置PHP语言配置
        monaco.languages.setLanguageConfiguration('php', {
            comments: {
                lineComment: '//',
                blockComment: ['/*', '*/']
            },
            brackets: [
                ['{', '}'],
                ['[', ']'],
                ['(', ')']
            ],
            autoClosingPairs: [
                { open: '{', close: '}' },
                { open: '[', close: ']' },
                { open: '(', close: ')' },
                { open: '"', close: '"' },
                { open: "'", close: "'" }
            ],
            surroundingPairs: [
                { open: '{', close: '}' },
                { open: '[', close: ']' },
                { open: '(', close: ')' },
                { open: '"', close: '"' },
                { open: "'", close: "'" }
            ]
        });

        // 设置PHP语法高亮
        monaco.languages.setMonarchTokensProvider('php', {
            tokenizer: {
                root: [
                    [/<\?php/, 'keyword'],
                    [/\?>/, 'keyword'],
                    [/\$[a-zA-Z_][a-zA-Z0-9_]*/, 'variable'],
                    [/\b(class|function|public|private|protected|static|const|var|if|else|elseif|endif|while|endwhile|for|endfor|foreach|endforeach|switch|endswitch|case|default|break|continue|return|try|catch|finally|throw|new|clone|instanceof|extends|implements|interface|trait|use|namespace|as|global|isset|unset|empty|array|echo|print|die|exit|include|include_once|require|require_once)\b/, 'keyword'],
                    [/\b(true|false|null|TRUE|FALSE|NULL)\b/, 'constant'],
                    [/\b\d+\b/, 'number'],
                    [/'([^'\\]|\\.)*'/, 'string'],
                    [/"([^"\\]|\\.)*"/, 'string'],
                    [/\/\/.*$/, 'comment'],
                    [/\/\*/, 'comment', '@comment'],
                    [/->/, 'operator'],
                    [/::/, 'operator'],
                    [/[{}()\[\]]/, 'bracket'],
                    [/[;,.]/, 'delimiter']
                ],
                comment: [
                    [/[^\/*]+/, 'comment'],
                    [/\*\//, 'comment', '@pop'],
                    [/[\/*]/, 'comment']
                ]
            }
        });

        // 注册Dusk代码片段
        monaco.languages.registerCompletionItemProvider('php', {
            provideCompletionItems: function(model, position) {
                console.log('Providing Dusk completion items...');
                var suggestions = [
                    // 基础浏览器操作
                    {
                        label: '$browser->visit',
                        kind: monaco.languages.CompletionItemKind.Method,
                        insertText: '$browser->visit(\'${1:url}\');',
                        insertTextRules: monaco.languages.CompletionItemInsertTextRule.InsertAsSnippet,
                        documentation: '访问指定URL'
                    },
                    {
                        label: '$browser->click',
                        kind: monaco.languages.CompletionItemKind.Method,
                        insertText: '$browser->click(\'${1:selector}\');',
                        insertTextRules: monaco.languages.CompletionItemInsertTextRule.InsertAsSnippet,
                        documentation: '点击指定元素'
                    },
                    {
                        label: '$browser->type',
                        kind: monaco.languages.CompletionItemKind.Method,
                        insertText: '$browser->type(\'${1:selector}\', \'${2:text}\');',
                        insertTextRules: monaco.languages.CompletionItemInsertTextRule.InsertAsSnippet,
                        documentation: '在输入框中输入文本'
                    },
                    {
                        label: '$browser->screenshot',
                        kind: monaco.languages.CompletionItemKind.Method,
                        insertText: '$browser->screenshot(\'${1:filename}\');',
                        insertTextRules: monaco.languages.CompletionItemInsertTextRule.InsertAsSnippet,
                        documentation: '截取屏幕截图'
                    },
                    {
                        label: '$browser->pause',
                        kind: monaco.languages.CompletionItemKind.Method,
                        insertText: '$browser->pause(${1:3000});',
                        insertTextRules: monaco.languages.CompletionItemInsertTextRule.InsertAsSnippet,
                        documentation: '暂停指定毫秒数'
                    },
                    {
                        label: '$browser->waitFor',
                        kind: monaco.languages.CompletionItemKind.Method,
                        insertText: '$browser->waitFor(\'${1:selector}\');',
                        insertTextRules: monaco.languages.CompletionItemInsertTextRule.InsertAsSnippet,
                        documentation: '等待元素出现'
                    },
                    {
                        label: '$browser->assertSee',
                        kind: monaco.languages.CompletionItemKind.Method,
                        insertText: '$browser->assertSee(\'${1:text}\');',
                        insertTextRules: monaco.languages.CompletionItemInsertTextRule.InsertAsSnippet,
                        documentation: '断言页面包含指定文本'
                    },
                    {
                        label: '$browser->assertDontSee',
                        kind: monaco.languages.CompletionItemKind.Method,
                        insertText: '$browser->assertDontSee(\'${1:text}\');',
                        insertTextRules: monaco.languages.CompletionItemInsertTextRule.InsertAsSnippet,
                        documentation: '断言页面不包含指定文本'
                    },
                    {
                        label: '$browser->assertPresent',
                        kind: monaco.languages.CompletionItemKind.Method,
                        insertText: '$browser->assertPresent(\'${1:selector}\');',
                        insertTextRules: monaco.languages.CompletionItemInsertTextRule.InsertAsSnippet,
                        documentation: '断言元素存在'
                    },
                    {
                        label: '$browser->assertMissing',
                        kind: monaco.languages.CompletionItemKind.Method,
                        insertText: '$browser->assertMissing(\'${1:selector}\');',
                        insertTextRules: monaco.languages.CompletionItemInsertTextRule.InsertAsSnippet,
                        documentation: '断言元素不存在'
                    },
                    {
                        label: '$browser->select',
                        kind: monaco.languages.CompletionItemKind.Method,
                        insertText: '$browser->select(\'${1:selector}\', \'${2:value}\');',
                        insertTextRules: monaco.languages.CompletionItemInsertTextRule.InsertAsSnippet,
                        documentation: '选择下拉框选项'
                    },
                    {
                        label: '$browser->check',
                        kind: monaco.languages.CompletionItemKind.Method,
                        insertText: '$browser->check(\'${1:selector}\');',
                        insertTextRules: monaco.languages.CompletionItemInsertTextRule.InsertAsSnippet,
                        documentation: '勾选复选框'
                    },
                    {
                        label: '$browser->uncheck',
                        kind: monaco.languages.CompletionItemKind.Method,
                        insertText: '$browser->uncheck(\'${1:selector}\');',
                        insertTextRules: monaco.languages.CompletionItemInsertTextRule.InsertAsSnippet,
                        documentation: '取消勾选复选框'
                    },
                    {
                        label: '$browser->refresh',
                        kind: monaco.languages.CompletionItemKind.Method,
                        insertText: '$browser->refresh();',
                        insertTextRules: monaco.languages.CompletionItemInsertTextRule.InsertAsSnippet,
                        documentation: '刷新页面'
                    },
                    {
                        label: '$browser->back',
                        kind: monaco.languages.CompletionItemKind.Method,
                        insertText: '$browser->back();',
                        insertTextRules: monaco.languages.CompletionItemInsertTextRule.InsertAsSnippet,
                        documentation: '返回上一页'
                    },
                    {
                        label: '$browser->forward',
                        kind: monaco.languages.CompletionItemKind.Method,
                        insertText: '$browser->forward();',
                        insertTextRules: monaco.languages.CompletionItemInsertTextRule.InsertAsSnippet,
                        documentation: '前进到下一页'
                    },
                    {
                        label: '$browser->resize',
                        kind: monaco.languages.CompletionItemKind.Method,
                        insertText: '$browser->resize(${1:1920}, ${2:1080});',
                        insertTextRules: monaco.languages.CompletionItemInsertTextRule.InsertAsSnippet,
                        documentation: '调整浏览器窗口大小'
                    },
                    {
                        label: '$browser->maximize',
                        kind: monaco.languages.CompletionItemKind.Method,
                        insertText: '$browser->maximize();',
                        insertTextRules: monaco.languages.CompletionItemInsertTextRule.InsertAsSnippet,
                        documentation: '最大化浏览器窗口'
                    },
                    {
                        label: '$browser->scrollTo',
                        kind: monaco.languages.CompletionItemKind.Method,
                        insertText: '$browser->scrollTo(\'${1:selector}\');',
                        insertTextRules: monaco.languages.CompletionItemInsertTextRule.InsertAsSnippet,
                        documentation: '滚动到指定元素'
                    },
                    {
                        label: '$browser->scrollIntoView',
                        kind: monaco.languages.CompletionItemKind.Method,
                        insertText: '$browser->scrollIntoView(\'${1:selector}\');',
                        insertTextRules: monaco.languages.CompletionItemInsertTextRule.InsertAsSnippet,
                        documentation: '滚动使元素进入视图'
                    },
                    {
                        label: '$browser->waitUntilMissing',
                        kind: monaco.languages.CompletionItemKind.Method,
                        insertText: '$browser->waitUntilMissing(\'${1:selector}\');',
                        insertTextRules: monaco.languages.CompletionItemInsertTextRule.InsertAsSnippet,
                        documentation: '等待元素消失'
                    },

                    // 自定义宏 - autoScreenshot
                    {
                        label: '$browser->autoScreenshot',
                        kind: monaco.languages.CompletionItemKind.Method,
                        insertText: '$browser->autoScreenshot(\'${1:description}\');',
                        insertTextRules: monaco.languages.CompletionItemInsertTextRule.InsertAsSnippet,
                        documentation: '自动截图并记录日志 (自定义宏)'
                    },

                    // API任务相关变量
                    {
                        label: '$http',
                        kind: monaco.languages.CompletionItemKind.Variable,
                        insertText: '\\$http',
                        documentation: 'HTTP客户端变量 (API任务中可用，等同于Http::class)'
                    },
                    {
                        label: '$http::get',
                        kind: monaco.languages.CompletionItemKind.Method,
                        insertText: '\\$http::get(\'${1:url}\');',
                        insertTextRules: monaco.languages.CompletionItemInsertTextRule.InsertAsSnippet,
                        documentation: '发送GET请求'
                    },
                    {
                        label: '$http::post',
                        kind: monaco.languages.CompletionItemKind.Method,
                        insertText: '\\$http::post(\'${1:url}\', [${2:data}]);',
                        insertTextRules: monaco.languages.CompletionItemInsertTextRule.InsertAsSnippet,
                        documentation: '发送POST请求'
                    },
                    {
                        label: '$http::put',
                        kind: monaco.languages.CompletionItemKind.Method,
                        insertText: '\\$http::put(\'${1:url}\', [${2:data}]);',
                        insertTextRules: monaco.languages.CompletionItemInsertTextRule.InsertAsSnippet,
                        documentation: '发送PUT请求'
                    },
                    {
                        label: '$http::delete',
                        kind: monaco.languages.CompletionItemKind.Method,
                        insertText: '\\$http::delete(\'${1:url}\');',
                        insertTextRules: monaco.languages.CompletionItemInsertTextRule.InsertAsSnippet,
                        documentation: '发送DELETE请求'
                    },
                    {
                        label: '$http::withHeaders',
                        kind: monaco.languages.CompletionItemKind.Method,
                        insertText: '\\$http::withHeaders([${1:headers}])->get(\'${2:url}\');',
                        insertTextRules: monaco.languages.CompletionItemInsertTextRule.InsertAsSnippet,
                        documentation: '设置请求头'
                    },
                    {
                        label: '$http::withToken',
                        kind: monaco.languages.CompletionItemKind.Method,
                        insertText: '\\$http::withToken(\'${1:token}\')->get(\'${2:url}\');',
                        insertTextRules: monaco.languages.CompletionItemInsertTextRule.InsertAsSnippet,
                        documentation: '设置Bearer Token'
                    },

                    // 日志记录函数 (API任务中可用)
                    {
                        label: '$log',
                        kind: monaco.languages.CompletionItemKind.Function,
                        insertText: '$log(\'${1:info}\', \'${2:message}\');',
                        insertTextRules: monaco.languages.CompletionItemInsertTextRule.InsertAsSnippet,
                        documentation: '记录日志 (API任务中可用)'
                    },

                    // 环境变量访问
                    {
                        label: 'env',
                        kind: monaco.languages.CompletionItemKind.Function,
                        insertText: 'env(\'${1:VARIABLE_NAME}\');',
                        insertTextRules: monaco.languages.CompletionItemInsertTextRule.InsertAsSnippet,
                        documentation: '获取环境变量值'
                    },
                    {
                        label: 'env-with-default',
                        kind: monaco.languages.CompletionItemKind.Function,
                        insertText: 'env(\'${1:VARIABLE_NAME}\', \'${2:default_value}\');',
                        insertTextRules: monaco.languages.CompletionItemInsertTextRule.InsertAsSnippet,
                        documentation: '获取环境变量值（带默认值）'
                    },
                    // 代码模板
                    {
                        label: 'dusk-template',
                        kind: monaco.languages.CompletionItemKind.Snippet,
                        insertText: [
                            '// 访问页面',
                            '\\$browser->visit(\'${1:https://example.com}\');',
                            '',
                            '// 等待页面加载',
                            '\\$browser->pause(${2:3000});',
                            '',
                            '// 执行操作',
                            '\\$browser->click(\'${3:button}\');',
                            '',
                            '// 自动截图',
                            '\\$browser->autoScreenshot(\'${4:操作完成}\');'
                        ].join('\n'),
                        insertTextRules: monaco.languages.CompletionItemInsertTextRule.InsertAsSnippet,
                        documentation: 'Dusk测试基础模板'
                    },
                    {
                        label: 'api-template',
                        kind: monaco.languages.CompletionItemKind.Snippet,
                        insertText: [
                            '// API请求模板',
                            '\\$response = \\$http::withHeaders([',
                            '    \'Authorization\' => \'Bearer \' . env(\'${1:API_TOKEN}\'),',
                            '    \'Content-Type\' => \'application/json\'',
                            '])->post(\'${2:https://api.example.com/endpoint}\', [',
                            '    \'${3:key}\' => \'${4:value}\'',
                            ']);',
                            '',
                            '// 记录响应',
                            '\\$log(\'info\', \'API响应: \' . \\$response->body());',
                            '',
                            '// 检查响应状态',
                            'if (\\$response->successful()) {',
                            '    \\$log(\'info\', \'请求成功\');',
                            '} else {',
                            '    \\$log(\'error\', \'请求失败: \' . \\$response->status());',
                            '}'
                        ].join('\n'),
                        insertTextRules: monaco.languages.CompletionItemInsertTextRule.InsertAsSnippet,
                        documentation: 'API请求基础模板'
                    },
                    {
                        label: 'login-template',
                        kind: monaco.languages.CompletionItemKind.Snippet,
                        insertText: [
                            '// 登录模板',
                            '\\$browser->visit(\'${1:https://example.com/login}\');',
                            '',
                            '// 输入登录信息',
                            '\\$browser->type(\'${2:input[name="username"]}\', env(\'${3:USERNAME}\'));',
                            '\\$browser->type(\'${4:input[name="password"]}\', env(\'${5:PASSWORD}\'));',
                            '',
                            '// 点击登录按钮',
                            '\\$browser->click(\'${6:button[type="submit"]}\');',
                            '',
                            '// 等待登录完成',
                            '\\$browser->waitFor(\'${7:.dashboard}\');',
                            '',
                            '// 登录成功截图',
                            '\\$browser->autoScreenshot(\'登录成功\');'
                        ].join('\n'),
                        insertTextRules: monaco.languages.CompletionItemInsertTextRule.InsertAsSnippet,
                        documentation: '登录操作模板'
                    },
                    {
                        label: 'form-template',
                        kind: monaco.languages.CompletionItemKind.Snippet,
                        insertText: [
                            '// 表单填写模板',
                            '\\$browser->visit(\'${1:https://example.com/form}\');',
                            '',
                            '// 填写表单字段',
                            '\\$browser->type(\'${2:input[name="field1"]}\', \'${3:value1}\');',
                            '\\$browser->select(\'${4:select[name="field2"]}\', \'${5:option1}\');',
                            '\\$browser->check(\'${6:input[name="field3"]}\');',
                            '',
                            '// 提交表单',
                            '\\$browser->click(\'${7:button[type="submit"]}\');',
                            '',
                            '// 等待提交完成',
                            '\\$browser->waitFor(\'${8:.success-message}\');',
                            '',
                            '// 提交成功截图',
                            '\\$browser->autoScreenshot(\'表单提交成功\');'
                        ].join('\n'),
                        insertTextRules: monaco.languages.CompletionItemInsertTextRule.InsertAsSnippet,
                        documentation: '表单操作模板'
                    }
                ];

                return { suggestions: suggestions };
            }
        });

        console.log('PHP language and Dusk snippets setup completed!');
    } catch (error) {
        console.error('Error setting up PHP language and Dusk snippets:', error);
    }
}
</script>

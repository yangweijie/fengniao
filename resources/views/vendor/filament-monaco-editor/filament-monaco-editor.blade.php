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

<!-- 加载Dusk代码片段 -->
<script src="{{ asset('js/dusk-monaco-snippets.js') }}"></script>

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
                        insertText: '\\$browser->visit(\'${1:url}\');',
                        insertTextRules: monaco.languages.CompletionItemInsertTextRule.InsertAsSnippet,
                        documentation: '访问指定URL'
                    },
                    {
                        label: '$browser->click',
                        kind: monaco.languages.CompletionItemKind.Method,
                        insertText: '\\$browser->click(\'${1:selector}\');',
                        insertTextRules: monaco.languages.CompletionItemInsertTextRule.InsertAsSnippet,
                        documentation: '点击指定元素'
                    },
                    {
                        label: '$browser->type',
                        kind: monaco.languages.CompletionItemKind.Method,
                        insertText: '\\$browser->type(\'${1:selector}\', \'${2:text}\');',
                        insertTextRules: monaco.languages.CompletionItemInsertTextRule.InsertAsSnippet,
                        documentation: '在输入框中输入文本'
                    },
                    {
                        label: '$browser->screenshot',
                        kind: monaco.languages.CompletionItemKind.Method,
                        insertText: '\\$browser->screenshot(\'${1:filename}\');',
                        insertTextRules: monaco.languages.CompletionItemInsertTextRule.InsertAsSnippet,
                        documentation: '截取屏幕截图'
                    },
                    {
                        label: '$browser->pause',
                        kind: monaco.languages.CompletionItemKind.Method,
                        insertText: '\\$browser->pause(${1:3000});',
                        insertTextRules: monaco.languages.CompletionItemInsertTextRule.InsertAsSnippet,
                        documentation: '暂停指定毫秒数'
                    },
                    {
                        label: '$browser->waitFor',
                        kind: monaco.languages.CompletionItemKind.Method,
                        insertText: '\\$browser->waitFor(\'${1:selector}\');',
                        insertTextRules: monaco.languages.CompletionItemInsertTextRule.InsertAsSnippet,
                        documentation: '等待元素出现'
                    },
                    {
                        label: '$browser->assertSee',
                        kind: monaco.languages.CompletionItemKind.Method,
                        insertText: '\\$browser->assertSee(\'${1:text}\');',
                        insertTextRules: monaco.languages.CompletionItemInsertTextRule.InsertAsSnippet,
                        documentation: '断言页面包含指定文本'
                    },
                    {
                        label: '$browser->assertDontSee',
                        kind: monaco.languages.CompletionItemKind.Method,
                        insertText: '\\$browser->assertDontSee(\'${1:text}\');',
                        insertTextRules: monaco.languages.CompletionItemInsertTextRule.InsertAsSnippet,
                        documentation: '断言页面不包含指定文本'
                    },
                    {
                        label: '$browser->assertPresent',
                        kind: monaco.languages.CompletionItemKind.Method,
                        insertText: '\\$browser->assertPresent(\'${1:selector}\');',
                        insertTextRules: monaco.languages.CompletionItemInsertTextRule.InsertAsSnippet,
                        documentation: '断言元素存在'
                    },
                    {
                        label: '$browser->assertMissing',
                        kind: monaco.languages.CompletionItemKind.Method,
                        insertText: '\\$browser->assertMissing(\'${1:selector}\');',
                        insertTextRules: monaco.languages.CompletionItemInsertTextRule.InsertAsSnippet,
                        documentation: '断言元素不存在'
                    },
                    {
                        label: '$browser->select',
                        kind: monaco.languages.CompletionItemKind.Method,
                        insertText: '\\$browser->select(\'${1:selector}\', \'${2:value}\');',
                        insertTextRules: monaco.languages.CompletionItemInsertTextRule.InsertAsSnippet,
                        documentation: '选择下拉框选项'
                    },
                    {
                        label: '$browser->check',
                        kind: monaco.languages.CompletionItemKind.Method,
                        insertText: '\\$browser->check(\'${1:selector}\');',
                        insertTextRules: monaco.languages.CompletionItemInsertTextRule.InsertAsSnippet,
                        documentation: '勾选复选框'
                    },
                    {
                        label: '$browser->uncheck',
                        kind: monaco.languages.CompletionItemKind.Method,
                        insertText: '\\$browser->uncheck(\'${1:selector}\');',
                        insertTextRules: monaco.languages.CompletionItemInsertTextRule.InsertAsSnippet,
                        documentation: '取消勾选复选框'
                    },
                    {
                        label: '$browser->refresh',
                        kind: monaco.languages.CompletionItemKind.Method,
                        insertText: '\\$browser->refresh();',
                        insertTextRules: monaco.languages.CompletionItemInsertTextRule.InsertAsSnippet,
                        documentation: '刷新页面'
                    },
                    {
                        label: '$browser->back',
                        kind: monaco.languages.CompletionItemKind.Method,
                        insertText: '\\$browser->back();',
                        insertTextRules: monaco.languages.CompletionItemInsertTextRule.InsertAsSnippet,
                        documentation: '返回上一页'
                    },
                    {
                        label: '$browser->forward',
                        kind: monaco.languages.CompletionItemKind.Method,
                        insertText: '\\$browser->forward();',
                        insertTextRules: monaco.languages.CompletionItemInsertTextRule.InsertAsSnippet,
                        documentation: '前进到下一页'
                    },
                    {
                        label: '$browser->resize',
                        kind: monaco.languages.CompletionItemKind.Method,
                        insertText: '\\$browser->resize(${1:1920}, ${2:1080});',
                        insertTextRules: monaco.languages.CompletionItemInsertTextRule.InsertAsSnippet,
                        documentation: '调整浏览器窗口大小'
                    },
                    // Dusk宏方法
                    {
                        label: '$browser->waitForAnyElement',
                        kind: monaco.languages.CompletionItemKind.Method,
                        insertText: '\\$browser->waitForAnyElement([\'${1:selector1}\', \'${2:selector2}\'], ${3:10});',
                        insertTextRules: monaco.languages.CompletionItemInsertTextRule.InsertAsSnippet,
                        documentation: '等待任意一个元素出现'
                    },
                    {
                        label: '$browser->waitForPageLoad',
                        kind: monaco.languages.CompletionItemKind.Method,
                        insertText: '\\$browser->waitForPageLoad(${1:30});',
                        insertTextRules: monaco.languages.CompletionItemInsertTextRule.InsertAsSnippet,
                        documentation: '等待页面完全加载'
                    },
                    {
                        label: '$browser->waitForAjax',
                        kind: monaco.languages.CompletionItemKind.Method,
                        insertText: '\\$browser->waitForAjax(${1:30});',
                        insertTextRules: monaco.languages.CompletionItemInsertTextRule.InsertAsSnippet,
                        documentation: '等待AJAX请求完成'
                    },
                    {
                        label: '$browser->smartClick',
                        kind: monaco.languages.CompletionItemKind.Method,
                        insertText: '\\$browser->smartClick(\'${1:selector}\', ${2:10});',
                        insertTextRules: monaco.languages.CompletionItemInsertTextRule.InsertAsSnippet,
                        documentation: '智能点击，等待元素可点击后点击'
                    },
                    {
                        label: '$browser->smartType',
                        kind: monaco.languages.CompletionItemKind.Method,
                        insertText: '\\$browser->smartType(\'${1:selector}\', \'${2:text}\', ${3:true});',
                        insertTextRules: monaco.languages.CompletionItemInsertTextRule.InsertAsSnippet,
                        documentation: '智能输入，先清空再输入'
                    },
                    {
                        label: '$browser->humanType',
                        kind: monaco.languages.CompletionItemKind.Method,
                        insertText: '\\$browser->humanType(\'${1:selector}\', \'${2:text}\');',
                        insertTextRules: monaco.languages.CompletionItemInsertTextRule.InsertAsSnippet,
                        documentation: '模拟人类输入，随机延迟'
                    },
                    {
                        label: '$browser->fillForm',
                        kind: monaco.languages.CompletionItemKind.Method,
                        insertText: '\\$browser->fillForm([\'${1:#field}\' => \'${2:value}\']);',
                        insertTextRules: monaco.languages.CompletionItemInsertTextRule.InsertAsSnippet,
                        documentation: '批量填写表单'
                    },
                    {
                        label: '$browser->smartLogin',
                        kind: monaco.languages.CompletionItemKind.Method,
                        insertText: '\\$browser->smartLogin(\'${1:#email}\', \'${2:#password}\', \'${3:user@example.com}\', \'${4:password123}\');',
                        insertTextRules: monaco.languages.CompletionItemInsertTextRule.InsertAsSnippet,
                        documentation: '智能登录'
                    },
                    {
                        label: '$browser->smartSearch',
                        kind: monaco.languages.CompletionItemKind.Method,
                        insertText: '\\$browser->smartSearch(\'${1:#search}\', \'${2:关键词}\', \'${3:#search-btn}\');',
                        insertTextRules: monaco.languages.CompletionItemInsertTextRule.InsertAsSnippet,
                        documentation: '智能搜索'
                    },
                    {
                        label: '$browser->getAllText',
                        kind: monaco.languages.CompletionItemKind.Method,
                        insertText: '\\$browser->getAllText(\'${1:selector}\');',
                        insertTextRules: monaco.languages.CompletionItemInsertTextRule.InsertAsSnippet,
                        documentation: '获取所有匹配元素的文本'
                    },
                    {
                        label: '$browser->hasElement',
                        kind: monaco.languages.CompletionItemKind.Method,
                        insertText: '\\$browser->hasElement(\'${1:selector}\');',
                        insertTextRules: monaco.languages.CompletionItemInsertTextRule.InsertAsSnippet,
                        documentation: '检查元素是否存在'
                    },
                    {
                        label: '$browser->screenshotWithTimestamp',
                        kind: monaco.languages.CompletionItemKind.Method,
                        insertText: '\\$browser->screenshotWithTimestamp(\'${1:screenshot_name}\');',
                        insertTextRules: monaco.languages.CompletionItemInsertTextRule.InsertAsSnippet,
                        documentation: '带时间戳的截图'
                    },
                    {
                        label: '$browser->acceptCookies',
                        kind: monaco.languages.CompletionItemKind.Method,
                        insertText: '\\$browser->acceptCookies([\'${1:.cookie-accept}\', \'${2:.accept-cookies}\']);',
                        insertTextRules: monaco.languages.CompletionItemInsertTextRule.InsertAsSnippet,
                        documentation: '接受Cookie提示'
                    },
                    {
                        label: '$browser->closeAds',
                        kind: monaco.languages.CompletionItemKind.Method,
                        insertText: '\\$browser->closeAds([\'${1:.ad-close}\', \'${2:.close-ad}\']);',
                        insertTextRules: monaco.languages.CompletionItemInsertTextRule.InsertAsSnippet,
                        documentation: '关闭广告弹窗'
                    },
                    {
                        label: '$browser->maximize',
                        kind: monaco.languages.CompletionItemKind.Method,
                        insertText: '\\$browser->maximize();',
                        insertTextRules: monaco.languages.CompletionItemInsertTextRule.InsertAsSnippet,
                        documentation: '最大化浏览器窗口'
                    },
                    {
                        label: '$browser->scrollTo',
                        kind: monaco.languages.CompletionItemKind.Method,
                        insertText: '\\$browser->scrollTo(\'${1:selector}\');',
                        insertTextRules: monaco.languages.CompletionItemInsertTextRule.InsertAsSnippet,
                        documentation: '滚动到指定元素'
                    },
                    {
                        label: '$browser->scrollIntoView',
                        kind: monaco.languages.CompletionItemKind.Method,
                        insertText: '\\$browser->scrollIntoView(\'${1:selector}\');',
                        insertTextRules: monaco.languages.CompletionItemInsertTextRule.InsertAsSnippet,
                        documentation: '滚动使元素进入视图'
                    },
                    {
                        label: '$browser->waitUntilMissing',
                        kind: monaco.languages.CompletionItemKind.Method,
                        insertText: '\\$browser->waitUntilMissing(\'${1:selector}\');',
                        insertTextRules: monaco.languages.CompletionItemInsertTextRule.InsertAsSnippet,
                        documentation: '等待元素消失'
                    },

                    // 自定义宏 - autoScreenshot
                    {
                        label: '$browser->autoScreenshot',
                        kind: monaco.languages.CompletionItemKind.Method,
                        insertText: '\\$browser->autoScreenshot(\'${1:description}\');',
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
                        label: 'Http::get',
                        kind: monaco.languages.CompletionItemKind.Method,
                        insertText: 'Http::get(\'${1:url}\');',
                        insertTextRules: monaco.languages.CompletionItemInsertTextRule.InsertAsSnippet,
                        documentation: '发送GET请求'
                    },
                    {
                        label: 'Http::post',
                        kind: monaco.languages.CompletionItemKind.Method,
                        insertText: 'Http::post(\'${1:url}\', [${2:data}]);',
                        insertTextRules: monaco.languages.CompletionItemInsertTextRule.InsertAsSnippet,
                        documentation: '发送POST请求'
                    },
                    {
                        label: 'Http::put',
                        kind: monaco.languages.CompletionItemKind.Method,
                        insertText: 'Http::put(\'${1:url}\', [${2:data}]);',
                        insertTextRules: monaco.languages.CompletionItemInsertTextRule.InsertAsSnippet,
                        documentation: '发送PUT请求'
                    },
                    {
                        label: 'Http::delete',
                        kind: monaco.languages.CompletionItemKind.Method,
                        insertText: 'Http::delete(\'${1:url}\');',
                        insertTextRules: monaco.languages.CompletionItemInsertTextRule.InsertAsSnippet,
                        documentation: '发送DELETE请求'
                    },
                    {
                        label: 'Http::withHeaders',
                        kind: monaco.languages.CompletionItemKind.Method,
                        insertText: 'Http::withHeaders([${1:headers}])->get(\'${2:url}\');',
                        insertTextRules: monaco.languages.CompletionItemInsertTextRule.InsertAsSnippet,
                        documentation: '设置请求头'
                    },
                    {
                        label: 'Http::withToken',
                        kind: monaco.languages.CompletionItemKind.Method,
                        insertText: 'Http::withToken(\'${1:token}\')->get(\'${2:url}\');',
                        insertTextRules: monaco.languages.CompletionItemInsertTextRule.InsertAsSnippet,
                        documentation: '设置Bearer Token'
                    },

                    // HTTP宏方法
                    {
                        label: 'Http::smartRetry',
                        kind: monaco.languages.CompletionItemKind.Method,
                        insertText: 'Http::smartRetry(\'${1:url}\', [${2:options}], ${3:3}, ${4:1000});',
                        insertTextRules: monaco.languages.CompletionItemInsertTextRule.InsertAsSnippet,
                        documentation: '智能重试请求，支持指数退避'
                    },
                    {
                        label: 'Http::withLogging',
                        kind: monaco.languages.CompletionItemKind.Method,
                        insertText: 'Http::withLogging(\'${1:HTTP}\')->get(\'${2:url}\');',
                        insertTextRules: monaco.languages.CompletionItemInsertTextRule.InsertAsSnippet,
                        documentation: '带日志记录的HTTP请求'
                    },
                    {
                        label: 'Http::jsonApi',
                        kind: monaco.languages.CompletionItemKind.Method,
                        insertText: 'Http::jsonApi(\'${1:baseUrl}\')->get(\'${2:endpoint}\');',
                        insertTextRules: monaco.languages.CompletionItemInsertTextRule.InsertAsSnippet,
                        documentation: '快速JSON API请求，自动设置JSON头'
                    },
                    {
                        label: 'Http::apiWithAuth',
                        kind: monaco.languages.CompletionItemKind.Method,
                        insertText: 'Http::apiWithAuth(\'${1:token}\', \'${2:Bearer}\', \'${3:baseUrl}\')->get(\'${4:endpoint}\');',
                        insertTextRules: monaco.languages.CompletionItemInsertTextRule.InsertAsSnippet,
                        documentation: '带认证的API请求'
                    },
                    {
                        label: 'Http::formData',
                        kind: monaco.languages.CompletionItemKind.Method,
                        insertText: 'Http::formData([${1:data}])->post(\'${2:url}\');',
                        insertTextRules: monaco.languages.CompletionItemInsertTextRule.InsertAsSnippet,
                        documentation: '表单数据请求'
                    },
                    {
                        label: 'Http::uploadFile',
                        kind: monaco.languages.CompletionItemKind.Method,
                        insertText: 'Http::uploadFile(\'${1:fieldName}\', \'${2:filePath}\', [${3:additionalData}])->post(\'${4:url}\');',
                        insertTextRules: monaco.languages.CompletionItemInsertTextRule.InsertAsSnippet,
                        documentation: '文件上传请求'
                    },
                    {
                        label: 'Http::downloadFile',
                        kind: monaco.languages.CompletionItemKind.Method,
                        insertText: 'Http::downloadFile(\'${1:url}\', \'${2:savePath}\', [${3:headers}]);',
                        insertTextRules: monaco.languages.CompletionItemInsertTextRule.InsertAsSnippet,
                        documentation: '下载文件到本地'
                    },
                    {
                        label: 'Http::batchRequests',
                        kind: monaco.languages.CompletionItemKind.Method,
                        insertText: 'Http::batchRequests([${1:requests}], ${2:5});',
                        insertTextRules: monaco.languages.CompletionItemInsertTextRule.InsertAsSnippet,
                        documentation: '批量并发请求'
                    },
                    {
                        label: 'Http::healthCheck',
                        kind: monaco.languages.CompletionItemKind.Method,
                        insertText: 'Http::healthCheck(\'${1:url}\', ${2:10});',
                        insertTextRules: monaco.languages.CompletionItemInsertTextRule.InsertAsSnippet,
                        documentation: '健康检查，返回状态和响应时间'
                    },
                    {
                        label: 'Http::getAllPages',
                        kind: monaco.languages.CompletionItemKind.Method,
                        insertText: 'Http::getAllPages(\'${1:baseUrl}\', [${2:params}], \'${3:page}\', \'${4:data}\');',
                        insertTextRules: monaco.languages.CompletionItemInsertTextRule.InsertAsSnippet,
                        documentation: '自动获取所有分页数据'
                    },
                    {
                        label: 'Http::withRateLimit',
                        kind: monaco.languages.CompletionItemKind.Method,
                        insertText: 'Http::withRateLimit(${1:60})->get(\'${2:url}\');',
                        insertTextRules: monaco.languages.CompletionItemInsertTextRule.InsertAsSnippet,
                        documentation: '速率限制请求，防止过于频繁'
                    },
                    {
                        label: 'Http::withCache',
                        kind: monaco.languages.CompletionItemKind.Method,
                        insertText: 'Http::withCache(${1:300})->get(\'${2:url}\');',
                        insertTextRules: monaco.languages.CompletionItemInsertTextRule.InsertAsSnippet,
                        documentation: '缓存HTTP响应'
                    },
                    {
                        label: 'Http::asBrowser',
                        kind: monaco.languages.CompletionItemKind.Method,
                        insertText: 'Http::asBrowser(\'${1:userAgent}\')->get(\'${2:url}\');',
                        insertTextRules: monaco.languages.CompletionItemInsertTextRule.InsertAsSnippet,
                        documentation: '模拟浏览器请求，设置浏览器头'
                    },
                    {
                        label: 'Http::withProxy',
                        kind: monaco.languages.CompletionItemKind.Method,
                        insertText: 'Http::withProxy(\'${1:proxy}\', \'${2:auth}\')->get(\'${3:url}\');',
                        insertTextRules: monaco.languages.CompletionItemInsertTextRule.InsertAsSnippet,
                        documentation: '使用代理发送请求'
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
                    // 新增的Dusk模板
                    {
                        label: 'dusk-basic',
                        kind: monaco.languages.CompletionItemKind.Snippet,
                        insertText: [
                            '// 基础Dusk脚本模板',
                            '\\$browser->visit(\'${1:https://example.com}\')',
                            '        ->waitForPageLoad()',
                            '        ->acceptCookies()',
                            '        ->closeAds()',
                            '        ->screenshotWithTimestamp(\'${2:step_name}\');'
                        ].join('\n'),
                        insertTextRules: monaco.languages.CompletionItemInsertTextRule.InsertAsSnippet,
                        documentation: '基础Dusk脚本模板'
                    },
                    {
                        label: 'dusk-login',
                        kind: monaco.languages.CompletionItemKind.Snippet,
                        insertText: [
                            '// 登录脚本模板',
                            '\\$browser->visit(\'${1:https://example.com/login}\')',
                            '        ->waitForPageLoad()',
                            '        ->smartLogin(\'${2:#email}\', \'${3:#password}\', \'${4:user@example.com}\', \'${5:password123}\')',
                            '        ->waitForUrlContains(\'${6:/dashboard}\')',
                            '        ->screenshotWithTimestamp(\'login_success\');'
                        ].join('\n'),
                        insertTextRules: monaco.languages.CompletionItemInsertTextRule.InsertAsSnippet,
                        documentation: '登录脚本模板'
                    },
                    {
                        label: 'dusk-form',
                        kind: monaco.languages.CompletionItemKind.Snippet,
                        insertText: [
                            '// 表单填写模板',
                            '\\$browser->visit(\'${1:https://example.com/form}\')',
                            '        ->waitForPageLoad()',
                            '        ->fillForm([',
                            '            \'${2:#name}\' => \'${3:张三}\',',
                            '            \'${4:#email}\' => \'${5:zhangsan@example.com}\',',
                            '            \'${6:#phone}\' => \'${7:13800138000}\',',
                            '            \'${8:#newsletter}\' => ${9:true}',
                            '        ])',
                            '        ->smartClick(\'${10:#submit}\')',
                            '        ->waitForAnyElement([\'.success\', \'.submitted\'])',
                            '        ->screenshotWithTimestamp(\'form_submitted\');'
                        ].join('\n'),
                        insertTextRules: monaco.languages.CompletionItemInsertTextRule.InsertAsSnippet,
                        documentation: '表单填写模板'
                    },
                    {
                        label: 'dusk-search',
                        kind: monaco.languages.CompletionItemKind.Snippet,
                        insertText: [
                            '// 搜索脚本模板',
                            '\\$browser->visit(\'${1:https://example.com}\')',
                            '        ->waitForPageLoad()',
                            '        ->smartSearch(\'${2:#search}\', \'${3:搜索关键词}\')',
                            '        ->waitForPageLoad()',
                            '        ->screenshotWithTimestamp(\'search_results\');'
                        ].join('\n'),
                        insertTextRules: monaco.languages.CompletionItemInsertTextRule.InsertAsSnippet,
                        documentation: '搜索脚本模板'
                    },
                    {
                        label: 'dusk-scraping',
                        kind: monaco.languages.CompletionItemKind.Snippet,
                        insertText: [
                            '// 数据采集模板',
                            '\\$data = [];',
                            '\\$browser->visit(\'${1:https://example.com/products}\')',
                            '        ->waitForPageLoad();',
                            '',
                            '\\$names = \\$browser->getAllText(\'${2:.product-name}\');',
                            '\\$prices = \\$browser->getAllText(\'${3:.product-price}\');',
                            '',
                            'for (\\$i = 0; \\$i < count(\\$names); \\$i++) {',
                            '    \\$data[] = [',
                            '        \'name\' => \\$names[\\$i] ?? \'\',',
                            '        \'price\' => \\$prices[\\$i] ?? \'\'',
                            '    ];',
                            '}',
                            '',
                            'file_put_contents(\'${4:scraped_data.json}\', json_encode(\\$data, JSON_PRETTY_PRINT));'
                        ].join('\n'),
                        insertTextRules: monaco.languages.CompletionItemInsertTextRule.InsertAsSnippet,
                        documentation: '数据采集模板'
                    },
                    {
                        label: 'dusk-error',
                        kind: monaco.languages.CompletionItemKind.Snippet,
                        insertText: [
                            '// 错误处理模板',
                            'try {',
                            '    \\$browser->visit(\'${1:https://example.com}\')',
                            '            ->waitForPageLoad();',
                            '    ',
                            '    // 主要操作',
                            '    if (\\$browser->hasElement(\'${2:.login-required}\')) {',
                            '        \\$browser->smartLogin(\'${3:#email}\', \'${4:#password}\', \'${5:user@example.com}\', \'${6:password123}\');',
                            '    }',
                            '    ',
                            '    \\$browser->smartClick(\'${7:.main-action}\');',
                            '    ',
                            '} catch (\\\\Exception \\$e) {',
                            '    \\$browser->screenshotWithTimestamp(\'error_occurred\');',
                            '    ',
                            '    // 记录错误',
                            '    file_put_contents(\'error_log.txt\', date(\'Y-m-d H:i:s\') . \' - \' . \\$e->getMessage() . "\\\\n", FILE_APPEND);',
                            '    ',
                            '    // 尝试恢复',
                            '    \\$browser->refresh()->waitForPageLoad();',
                            '}'
                        ].join('\n'),
                        insertTextRules: monaco.languages.CompletionItemInsertTextRule.InsertAsSnippet,
                        documentation: '错误处理模板'
                    },
                    {
                        label: 'api-template',
                        kind: monaco.languages.CompletionItemKind.Snippet,
                        insertText: [
                            '// API请求模板',
                            '\\$response = Http::withHeaders([',
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
                    // 新增HTTP模板
                    {
                        label: 'http-smart-retry',
                        kind: monaco.languages.CompletionItemKind.Snippet,
                        insertText: [
                            '// 智能重试请求模板',
                            'try {',
                            '    \\$response = Http::smartRetry(\'${1:https://api.example.com/endpoint}\', [',
                            '        \'${2:param}\' => \'${3:value}\'',
                            '    ], ${4:3}, ${5:1000});',
                            '    ',
                            '    if (\\$response->successful()) {',
                            '        \\$data = \\$response->json();',
                            '        \\$log(\'info\', \'请求成功\', \\$data);',
                            '    }',
                            '} catch (\\\\Exception \\$e) {',
                            '    \\$log(\'error\', \'重试失败: \' . \\$e->getMessage());',
                            '}'
                        ].join('\n'),
                        insertTextRules: monaco.languages.CompletionItemInsertTextRule.InsertAsSnippet,
                        documentation: '智能重试请求模板'
                    },
                    {
                        label: 'http-batch-requests',
                        kind: monaco.languages.CompletionItemKind.Snippet,
                        insertText: [
                            '// 批量请求模板',
                            '\\$requests = [',
                            '    [\'method\' => \'GET\', \'url\' => \'${1:https://api1.example.com}\'],',
                            '    [\'method\' => \'POST\', \'url\' => \'${2:https://api2.example.com}\', \'data\' => [\'key\' => \'value\']],',
                            '    [\'method\' => \'GET\', \'url\' => \'${3:https://api3.example.com}\', \'headers\' => [\'Authorization\' => \'Bearer token\']]',
                            '];',
                            '',
                            '\\$responses = Http::batchRequests(\\$requests, ${4:5});',
                            '',
                            'foreach (\\$responses as \\$index => \\$response) {',
                            '    if (\\$response->successful()) {',
                            '        \\$log(\'info\', \"请求 #{\\$index} 成功\", \\$response->json());',
                            '    } else {',
                            '        \\$log(\'error\', \"请求 #{\\$index} 失败: \" . \\$response->status());',
                            '    }',
                            '}'
                        ].join('\n'),
                        insertTextRules: monaco.languages.CompletionItemInsertTextRule.InsertAsSnippet,
                        documentation: '批量并发请求模板'
                    },
                    {
                        label: 'http-download-file',
                        kind: monaco.languages.CompletionItemKind.Snippet,
                        insertText: [
                            '// 文件下载模板',
                            '\\$downloadUrl = \'${1:https://example.com/file.pdf}\';',
                            '\\$savePath = \'${2:downloads/file.pdf}\';',
                            '',
                            '\\$result = Http::downloadFile(\\$downloadUrl, \\$savePath, [',
                            '    \'User-Agent\' => \'${3:MyApp/1.0}\'',
                            ']);',
                            '',
                            'if (\\$result[\'success\']) {',
                            '    \\$log(\'info\', \"文件下载成功: {\\$result[\'path\']}, 大小: {\\$result[\'size\']} bytes\");',
                            '} else {',
                            '    \\$log(\'error\', \'文件下载失败: \' . \\$result[\'error\']);',
                            '}'
                        ].join('\n'),
                        insertTextRules: monaco.languages.CompletionItemInsertTextRule.InsertAsSnippet,
                        documentation: '文件下载模板'
                    },
                    {
                        label: 'http-health-check',
                        kind: monaco.languages.CompletionItemKind.Snippet,
                        insertText: [
                            '// 健康检查模板',
                            '\\$services = [',
                            '    \'${1:API服务}\' => \'${2:https://api.example.com/health}\',',
                            '    \'${3:数据库}\' => \'${4:https://db.example.com/ping}\',',
                            '    \'${5:缓存}\' => \'${6:https://cache.example.com/status}\'',
                            '];',
                            '',
                            'foreach (\\$services as \\$name => \\$url) {',
                            '    \\$health = Http::healthCheck(\\$url, ${7:10});',
                            '    ',
                            '    if (\\$health[\'success\']) {',
                            '        \\$log(\'info\', \"{\\$name} 健康检查通过\", [',
                            '            \'响应时间\' => \\$health[\'response_time\'] . \'ms\',',
                            '            \'状态码\' => \\$health[\'status\']',
                            '        ]);',
                            '    } else {',
                            '        \\$log(\'error\', \"{\\$name} 健康检查失败\", [',
                            '            \'错误\' => \\$health[\'error\'] ?? \'未知错误\'',
                            '        ]);',
                            '    }',
                            '}'
                        ].join('\n'),
                        insertTextRules: monaco.languages.CompletionItemInsertTextRule.InsertAsSnippet,
                        documentation: '服务健康检查模板'
                    },
                    {
                        label: 'http-paginated-data',
                        kind: monaco.languages.CompletionItemKind.Snippet,
                        insertText: [
                            '// 分页数据获取模板',
                            '\\$baseUrl = \'${1:https://api.example.com/data}\';',
                            '\\$params = [',
                            '    \'${2:filter}\' => \'${3:value}\',',
                            '    \'${4:sort}\' => \'${5:created_at}\'',
                            '];',
                            '',
                            '\\$allData = Http::getAllPages(\\$baseUrl, \\$params, \'${6:page}\', \'${7:data}\');',
                            '',
                            '\\$log(\'info\', \"获取到 \" . count(\\$allData) . \" 条数据\");',
                            '',
                            '// 处理数据',
                            'foreach (\\$allData as \\$item) {',
                            '    // 处理每个数据项',
                            '    \\$log(\'debug\', \'处理数据项\', \\$item);',
                            '}',
                            '',
                            '// 保存到文件',
                            'file_put_contents(\'${8:data.json}\', json_encode(\\$allData, JSON_PRETTY_PRINT));'
                        ].join('\n'),
                        insertTextRules: monaco.languages.CompletionItemInsertTextRule.InsertAsSnippet,
                        documentation: '分页数据获取模板'
                    },
                    {
                        label: 'http-rate-limited',
                        kind: monaco.languages.CompletionItemKind.Snippet,
                        insertText: [
                            '// 速率限制请求模板',
                            '\\$urls = [',
                            '    \'${1:https://api.example.com/endpoint1}\',',
                            '    \'${2:https://api.example.com/endpoint2}\',',
                            '    \'${3:https://api.example.com/endpoint3}\'',
                            '];',
                            '',
                            '// 限制每分钟60个请求',
                            'foreach (\\$urls as \\$url) {',
                            '    \\$response = Http::withRateLimit(${4:60})->get(\\$url);',
                            '    ',
                            '    if (\\$response->successful()) {',
                            '        \\$data = \\$response->json();',
                            '        \\$log(\'info\', \"请求成功: {\\$url}\", \\$data);',
                            '    } else {',
                            '        \\$log(\'error\', \"请求失败: {\\$url}, 状态: \" . \\$response->status());',
                            '    }',
                            '}'
                        ].join('\n'),
                        insertTextRules: monaco.languages.CompletionItemInsertTextRule.InsertAsSnippet,
                        documentation: '速率限制请求模板'
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

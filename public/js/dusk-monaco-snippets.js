// Dusk Monaco Editor 语法提示和代码片段
(function() {
    'use strict';

    // 等待Monaco Editor加载完成
    function waitForMonaco() {
        if (typeof monaco !== 'undefined' && monaco.languages) {
            console.log('Monaco Editor detected, setting up PHP and Dusk support...');
            setupPhpLanguageSupport();
            setupDuskSnippets();
        } else {
            setTimeout(waitForMonaco, 500);
        }
    }

    // 暴露给全局作用域
    window.setupDuskSnippets = setupDuskSnippets;

    // 设置PHP语言支持
    function setupPhpLanguageSupport() {
        try {
            // 确保PHP语言已注册
            const languages = monaco.languages.getLanguages();
            const phpLanguage = languages.find(lang => lang.id === 'php');

            if (!phpLanguage) {
                console.log('Registering PHP language...');
                // 注册PHP语言
                monaco.languages.register({ id: 'php' });

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
            }

            console.log('PHP language support configured successfully');
        } catch (error) {
            console.error('Error setting up PHP language support:', error);
        }
    }

    // 页面加载完成后开始等待Monaco
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', waitForMonaco);
    } else {
        waitForMonaco();
    }
})();

function setupDuskSnippets() {
    try {
        console.log('Setting up Dusk snippets...');

        // 注册Dusk代码片段
        monaco.languages.registerCompletionItemProvider('php', {
            provideCompletionItems: function(model, position) {
                console.log('Providing completion items for PHP');
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

                // 智能等待宏
                {
                    label: '$browser->waitForAnyElement',
                    kind: monaco.languages.CompletionItemKind.Method,
                    insertText: '$browser->waitForAnyElement([\'${1:selector1}\', \'${2:selector2}\'], ${3:10});',
                    insertTextRules: monaco.languages.CompletionItemInsertTextRule.InsertAsSnippet,
                    documentation: '等待任意一个元素出现'
                },
                {
                    label: '$browser->waitForPageLoad',
                    kind: monaco.languages.CompletionItemKind.Method,
                    insertText: '$browser->waitForPageLoad(${1:30});',
                    insertTextRules: monaco.languages.CompletionItemInsertTextRule.InsertAsSnippet,
                    documentation: '等待页面完全加载'
                },
                {
                    label: '$browser->waitForAjax',
                    kind: monaco.languages.CompletionItemKind.Method,
                    insertText: '$browser->waitForAjax(${1:30});',
                    insertTextRules: monaco.languages.CompletionItemInsertTextRule.InsertAsSnippet,
                    documentation: '等待Ajax请求完成'
                },
                {
                    label: '$browser->waitForLoadingToFinish',
                    kind: monaco.languages.CompletionItemKind.Method,
                    insertText: '$browser->waitForLoadingToFinish([\'${1:.loading}\', \'${2:.spinner}\'], ${3:30});',
                    insertTextRules: monaco.languages.CompletionItemInsertTextRule.InsertAsSnippet,
                    documentation: '等待加载动画消失'
                },
                {
                    label: '$browser->waitUntilMissing',
                    kind: monaco.languages.CompletionItemKind.Method,
                    insertText: '$browser->waitUntilMissing(\'${1:selector}\', ${2:10});',
                    insertTextRules: monaco.languages.CompletionItemInsertTextRule.InsertAsSnippet,
                    documentation: '等待元素消失'
                },
                {
                    label: '$browser->waitAndGetText',
                    kind: monaco.languages.CompletionItemKind.Method,
                    insertText: '${1:text} = $browser->waitAndGetText(\'${2:selector}\', ${3:10});',
                    insertTextRules: monaco.languages.CompletionItemInsertTextRule.InsertAsSnippet,
                    documentation: '等待并获取元素文本'
                },
                {
                    label: '$browser->waitForLocation',
                    kind: monaco.languages.CompletionItemKind.Method,
                    insertText: '$browser->waitForLocation(\'${1:https://example.com/*}\', ${2:30});',
                    insertTextRules: monaco.languages.CompletionItemInsertTextRule.InsertAsSnippet,
                    documentation: '等待URL变化（支持通配符匹配）'
                },
                {
                    label: '$browser->waitForLocationContains',
                    kind: monaco.languages.CompletionItemKind.Method,
                    insertText: '$browser->waitForLocationContains(\'${1:dashboard}\', ${2:30});',
                    insertTextRules: monaco.languages.CompletionItemInsertTextRule.InsertAsSnippet,
                    documentation: '等待URL包含特定字符串'
                },
                {
                    label: '$browser->waitForUrlContains',
                    kind: monaco.languages.CompletionItemKind.Method,
                    insertText: '$browser->waitForUrlContains(\'${1:needle}\', ${2:10});',
                    insertTextRules: monaco.languages.CompletionItemInsertTextRule.InsertAsSnippet,
                    documentation: '等待URL包含特定字符串（已废弃，推荐使用waitForLocationContains）'
                },
                {
                    label: '$browser->waitForTitle',
                    kind: monaco.languages.CompletionItemKind.Method,
                    insertText: '$browser->waitForTitle(\'${1:title}\', ${2:10});',
                    insertTextRules: monaco.languages.CompletionItemInsertTextRule.InsertAsSnippet,
                    documentation: '等待页面标题包含特定文本'
                },

                // 智能交互宏
                {
                    label: '$browser->smartClick',
                    kind: monaco.languages.CompletionItemKind.Method,
                    insertText: '$browser->smartClick(\'${1:selector}\', ${2:10});',
                    insertTextRules: monaco.languages.CompletionItemInsertTextRule.InsertAsSnippet,
                    documentation: '智能点击（支持多种选择器策略）'
                },
                {
                    label: '$browser->smartType',
                    kind: monaco.languages.CompletionItemKind.Method,
                    insertText: '$browser->smartType(\'${1:selector}\', \'${2:text}\', ${3:true});',
                    insertTextRules: monaco.languages.CompletionItemInsertTextRule.InsertAsSnippet,
                    documentation: '智能输入（自动清空并输入）'
                },
                {
                    label: '$browser->humanType',
                    kind: monaco.languages.CompletionItemKind.Method,
                    insertText: '$browser->humanType(\'${1:selector}\', \'${2:text}\');',
                    insertTextRules: monaco.languages.CompletionItemInsertTextRule.InsertAsSnippet,
                    documentation: '模拟人类输入（带随机延迟）'
                },
                {
                    label: '$browser->scrollAndClick',
                    kind: monaco.languages.CompletionItemKind.Method,
                    insertText: '$browser->scrollAndClick(\'${1:selector}\');',
                    insertTextRules: monaco.languages.CompletionItemInsertTextRule.InsertAsSnippet,
                    documentation: '滚动到元素并点击'
                },
                {
                    label: '$browser->clickIfExists',
                    kind: monaco.languages.CompletionItemKind.Method,
                    insertText: '${1:result} = $browser->clickIfExists(\'${2:selector}\', ${3:5});',
                    insertTextRules: monaco.languages.CompletionItemInsertTextRule.InsertAsSnippet,
                    documentation: '如果元素存在则点击'
                },
                {
                    label: '$browser->clickAll',
                    kind: monaco.languages.CompletionItemKind.Method,
                    insertText: '$browser->clickAll(\'${1:selector}\', ${2:500});',
                    insertTextRules: monaco.languages.CompletionItemInsertTextRule.InsertAsSnippet,
                    documentation: '批量点击所有匹配的元素'
                },
                {
                    label: '$browser->smartSelect',
                    kind: monaco.languages.CompletionItemKind.Method,
                    insertText: '$browser->smartSelect(\'${1:selector}\', \'${2:option}\');',
                    insertTextRules: monaco.languages.CompletionItemInsertTextRule.InsertAsSnippet,
                    documentation: '智能下拉选择（支持文本和值）'
                },

                // 表单操作宏
                {
                    label: '$browser->fillForm',
                    kind: monaco.languages.CompletionItemKind.Method,
                    insertText: '$browser->fillForm([\n    \'${1:#selector1}\' => \'${2:value1}\',\n    \'${3:#selector2}\' => \'${4:value2}\'\n]);',
                    insertTextRules: monaco.languages.CompletionItemInsertTextRule.InsertAsSnippet,
                    documentation: '智能表单填写'
                },
                {
                    label: '$browser->smartLogin',
                    kind: monaco.languages.CompletionItemKind.Method,
                    insertText: '$browser->smartLogin(\'${1:#email}\', \'${2:#password}\', \'${3:user@example.com}\', \'${4:password123}\', \'${5:#login-btn}\');',
                    insertTextRules: monaco.languages.CompletionItemInsertTextRule.InsertAsSnippet,
                    documentation: '智能登录'
                },
                {
                    label: '$browser->smartSearch',
                    kind: monaco.languages.CompletionItemKind.Method,
                    insertText: '$browser->smartSearch(\'${1:#search}\', \'${2:搜索内容}\', \'${3:#search-btn}\');',
                    insertTextRules: monaco.languages.CompletionItemInsertTextRule.InsertAsSnippet,
                    documentation: '智能搜索'
                },
                {
                    label: '$browser->fillTableRow',
                    kind: monaco.languages.CompletionItemKind.Method,
                    insertText: '$browser->fillTableRow(\'${1:#table}\', ${2:1}, [\'${3:value1}\', \'${4:value2}\']);',
                    insertTextRules: monaco.languages.CompletionItemInsertTextRule.InsertAsSnippet,
                    documentation: '填写表格行数据'
                },
                {
                    label: '$browser->smartUpload',
                    kind: monaco.languages.CompletionItemKind.Method,
                    insertText: '$browser->smartUpload(\'${1:#file-input}\', \'${2:/path/to/file.jpg}\');',
                    insertTextRules: monaco.languages.CompletionItemInsertTextRule.InsertAsSnippet,
                    documentation: '智能文件上传'
                },

                // 页面检测宏
                {
                    label: '$browser->hasElement',
                    kind: monaco.languages.CompletionItemKind.Method,
                    insertText: '${1:exists} = $browser->hasElement(\'${2:selector}\');',
                    insertTextRules: monaco.languages.CompletionItemInsertTextRule.InsertAsSnippet,
                    documentation: '检查元素是否存在（不抛出异常）'
                },
                {
                    label: '$browser->getAttribute',
                    kind: monaco.languages.CompletionItemKind.Method,
                    insertText: '${1:value} = $browser->getAttribute(\'${2:selector}\', \'${3:attribute}\');',
                    insertTextRules: monaco.languages.CompletionItemInsertTextRule.InsertAsSnippet,
                    documentation: '获取元素属性值'
                },
                {
                    label: '$browser->setAttribute',
                    kind: monaco.languages.CompletionItemKind.Method,
                    insertText: '$browser->setAttribute(\'${1:selector}\', \'${2:attribute}\', \'${3:value}\');',
                    insertTextRules: monaco.languages.CompletionItemInsertTextRule.InsertAsSnippet,
                    documentation: '设置元素属性'
                },
                {
                    label: '$browser->removeAttribute',
                    kind: monaco.languages.CompletionItemKind.Method,
                    insertText: '$browser->removeAttribute(\'${1:selector}\', \'${2:attribute}\');',
                    insertTextRules: monaco.languages.CompletionItemInsertTextRule.InsertAsSnippet,
                    documentation: '移除元素属性'
                },
                {
                    label: '$browser->getAllText',
                    kind: monaco.languages.CompletionItemKind.Method,
                    insertText: '${1:texts} = $browser->getAllText(\'${2:selector}\');',
                    insertTextRules: monaco.languages.CompletionItemInsertTextRule.InsertAsSnippet,
                    documentation: '获取所有匹配元素的文本'
                },

                // 标签页管理宏
                {
                    label: '$browser->switchToNewTab',
                    kind: monaco.languages.CompletionItemKind.Method,
                    insertText: '$browser->switchToNewTab();',
                    insertTextRules: monaco.languages.CompletionItemInsertTextRule.InsertAsSnippet,
                    documentation: '切换到新打开的标签页'
                },
                {
                    label: '$browser->closeTabAndSwitchBack',
                    kind: monaco.languages.CompletionItemKind.Method,
                    insertText: '$browser->closeTabAndSwitchBack();',
                    insertTextRules: monaco.languages.CompletionItemInsertTextRule.InsertAsSnippet,
                    documentation: '关闭当前标签页并切换回主标签页'
                },
                {
                    label: '$browser->handleAlert',
                    kind: monaco.languages.CompletionItemKind.Method,
                    insertText: '$browser->handleAlert(${1:true});',
                    insertTextRules: monaco.languages.CompletionItemInsertTextRule.InsertAsSnippet,
                    documentation: '处理弹窗（接受或取消）'
                },

                // 实用工具宏
                {
                    label: '$browser->screenshotWithTimestamp',
                    kind: monaco.languages.CompletionItemKind.Method,
                    insertText: '$browser->screenshotWithTimestamp(\'${1:screenshot_name}\');',
                    insertTextRules: monaco.languages.CompletionItemInsertTextRule.InsertAsSnippet,
                    documentation: '带时间戳的截图'
                },
                {
                    label: '$browser->randomPause',
                    kind: monaco.languages.CompletionItemKind.Method,
                    insertText: '$browser->randomPause(${1:500}, ${2:2000});',
                    insertTextRules: monaco.languages.CompletionItemInsertTextRule.InsertAsSnippet,
                    documentation: '随机等待（模拟人类行为）'
                },
                {
                    label: '$browser->acceptCookies',
                    kind: monaco.languages.CompletionItemKind.Method,
                    insertText: '$browser->acceptCookies([\'${1:.cookie-accept}\', \'${2:.accept-cookies}\']);',
                    insertTextRules: monaco.languages.CompletionItemInsertTextRule.InsertAsSnippet,
                    documentation: '智能Cookie接受'
                },
                {
                    label: '$browser->closeAds',
                    kind: monaco.languages.CompletionItemKind.Method,
                    insertText: '$browser->closeAds([\'${1:.ad-close}\', \'${2:.close-ad}\']);',
                    insertTextRules: monaco.languages.CompletionItemInsertTextRule.InsertAsSnippet,
                    documentation: '智能广告关闭'
                },
                {
                    label: '$browser->measurePageLoad',
                    kind: monaco.languages.CompletionItemKind.Method,
                    insertText: '${1:metrics} = $browser->measurePageLoad();',
                    insertTextRules: monaco.languages.CompletionItemInsertTextRule.InsertAsSnippet,
                    documentation: '页面性能监控，返回加载时间等信息'
                },

                // 常用代码模板
                {
                    label: 'dusk-basic',
                    kind: monaco.languages.CompletionItemKind.Snippet,
                    insertText: [
                        '// 基础Dusk脚本模板',
                        '$browser->visit(\'${1:https://example.com}\')',
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
                        '$browser->visit(\'${1:https://example.com/login}\')',
                        '        ->waitForPageLoad()',
                        '        ->smartLogin(\'${2:#email}\', \'${3:#password}\', \'${4:user@example.com}\', \'${5:password123}\')',
                        '        ->waitForLocation(\'${6:https://example.com/dashboard*}\')',
                        '        ->screenshotWithTimestamp(\'login_success\');'
                    ].join('\n'),
                    insertTextRules: monaco.languages.CompletionItemInsertTextRule.InsertAsSnippet,
                    documentation: '登录脚本模板（使用URL变化验证登录成功）'
                },
                {
                    label: 'dusk-form',
                    kind: monaco.languages.CompletionItemKind.Snippet,
                    insertText: [
                        '// 表单填写模板',
                        '$browser->visit(\'${1:https://example.com/form}\')',
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
                        '$browser->visit(\'${1:https://example.com}\')',
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
                        '$data = [];',
                        '$browser->visit(\'${1:https://example.com/products}\')',
                        '        ->waitForPageLoad();',
                        '',
                        '$names = $browser->getAllText(\'${2:.product-name}\');',
                        '$prices = $browser->getAllText(\'${3:.product-price}\');',
                        '',
                        'for ($i = 0; $i < count($names); $i++) {',
                        '    $data[] = [',
                        '        \'name\' => $names[$i] ?? \'\',',
                        '        \'price\' => $prices[$i] ?? \'\'',
                        '    ];',
                        '}',
                        '',
                        'file_put_contents(\'${4:scraped_data.json}\', json_encode($data, JSON_PRETTY_PRINT));'
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
                        '    $browser->visit(\'${1:https://example.com}\')',
                        '            ->waitForPageLoad();',
                        '    ',
                        '    // 主要操作',
                        '    if ($browser->hasElement(\'${2:.login-required}\')) {',
                        '        $browser->smartLogin(\'${3:#email}\', \'${4:#password}\', \'${5:user@example.com}\', \'${6:password123}\');',
                        '    }',
                        '    ',
                        '    $browser->smartClick(\'${7:.main-action}\');',
                        '    ',
                        '} catch (\\Exception $e) {',
                        '    $browser->screenshotWithTimestamp(\'error_occurred\');',
                        '    ',
                        '    // 记录错误',
                        '    file_put_contents(\'error_log.txt\', date(\'Y-m-d H:i:s\') . \' - \' . $e->getMessage() . "\\n", FILE_APPEND);',
                        '    ',
                        '    // 尝试恢复',
                        '    $browser->refresh()->waitForPageLoad();',
                        '}'
                    ].join('\n'),
                    insertTextRules: monaco.languages.CompletionItemInsertTextRule.InsertAsSnippet,
                    documentation: '错误处理模板'
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
                
                // 等待操作
                {
                    label: '$browser->waitFor',
                    kind: monaco.languages.CompletionItemKind.Method,
                    insertText: '$browser->waitFor(\'${1:selector}\');',
                    insertTextRules: monaco.languages.CompletionItemInsertTextRule.InsertAsSnippet,
                    documentation: '等待元素出现'
                },
                {
                    label: '$browser->waitUntilMissing',
                    kind: monaco.languages.CompletionItemKind.Method,
                    insertText: '$browser->waitUntilMissing(\'${1:selector}\');',
                    insertTextRules: monaco.languages.CompletionItemInsertTextRule.InsertAsSnippet,
                    documentation: '等待元素消失'
                },
                {
                    label: '$browser->pause',
                    kind: monaco.languages.CompletionItemKind.Method,
                    insertText: '$browser->pause(${1:3000});',
                    insertTextRules: monaco.languages.CompletionItemInsertTextRule.InsertAsSnippet,
                    documentation: '暂停指定毫秒数'
                },
                
                // 表单操作
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
                
                // 断言操作
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
                
                // 页面操作
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
                
                // 窗口操作
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
                
                // 滚动操作
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
                
                // 完整的Dusk测试模板
                {
                    label: 'dusk-template',
                    kind: monaco.languages.CompletionItemKind.Snippet,
                    insertText: [
                        '// 访问页面',
                        '$browser->visit(\'${1:https://example.com}\');',
                        '',
                        '// 等待页面加载',
                        '$browser->pause(${2:3000});',
                        '',
                        '// 执行操作',
                        '$browser->click(\'${3:button}\');',
                        '',
                        '// 截图',
                        '$browser->screenshot(\'${4:screenshot_name}\');'
                    ].join('\n'),
                    insertTextRules: monaco.languages.CompletionItemInsertTextRule.InsertAsSnippet,
                    documentation: 'Dusk测试基础模板'
                }
            ];
            
            return { suggestions: suggestions };
        }
        });

        console.log('Dusk Monaco Editor snippets loaded successfully!');
    } catch (error) {
        console.error('Error setting up Dusk snippets:', error);
    }
}

    // 启动等待Monaco Editor
    waitForMonaco();

})();

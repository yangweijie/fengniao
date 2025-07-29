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

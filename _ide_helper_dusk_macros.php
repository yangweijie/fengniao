<?php

/**
 * IDE Helper for Dusk Macros
 * 
 * This file provides IDE autocompletion for custom Dusk macros.
 * Do not include this file in your application.
 * 
 * @author Dusk Macro System
 */

namespace Laravel\Dusk {

    /**
     * @method Browser waitForAnyElement(array $selectors, int $seconds = 10) 等待任意一个元素出现
     * @method Browser waitForPageLoad(int $timeout = 30) 等待页面完全加载
     * @method Browser waitForAjax(int $timeout = 30) 等待Ajax请求完成
     * @method Browser waitForLoadingToFinish(array $loadingSelectors = ['.loading', '.spinner', '[data-loading]'], int $timeout = 30) 等待加载动画消失
     * @method Browser waitUntilMissing(string $selector, int $seconds = 10) 等待元素消失
     * @method string waitAndGetText(string $selector, int $timeout = 10) 等待并获取元素文本
     * @method Browser waitForUrlContains(string $needle, int $seconds = 10) 等待URL包含特定字符串
     * @method Browser waitForTitle(string $title, int $timeout = 10) 等待页面标题包含特定文本
     * 
     * @method Browser smartClick(string $selector, int $timeout = 10) 智能点击（支持多种选择器策略）
     * @method Browser smartType(string $selector, string $text, bool $clear = true) 智能输入（自动清空并输入）
     * @method Browser humanType(string $selector, string $text) 模拟人类输入（带随机延迟）
     * @method Browser scrollAndClick(string $selector) 滚动到元素并点击
     * @method bool clickIfExists(string $selector, int $timeout = 5) 如果元素存在则点击
     * @method Browser clickAll(string $selector, int $delay = 500) 批量点击所有匹配的元素
     * @method Browser smartSelect(string $selector, string $option) 智能下拉选择（支持文本和值）
     * 
     * @method Browser fillForm(array $data) 智能表单填写
     * @method Browser smartLogin(string $usernameSelector, string $passwordSelector, string $username, string $password, string $submitSelector = 'button[type="submit"]') 智能登录
     * @method Browser smartSearch(string $searchSelector, string $query, string $submitSelector = null) 智能搜索
     * @method Browser fillTableRow(string $tableSelector, int $rowIndex, array $data) 填写表格行数据
     * @method Browser smartUpload(string $selector, string $filePath) 智能文件上传
     * 
     * @method bool hasElement(string $selector) 检查元素是否存在（不抛出异常）
     * @method string getAttribute(string $selector, string $attribute) 获取元素属性值
     * @method Browser setAttribute(string $selector, string $attribute, string $value) 设置元素属性
     * @method Browser removeAttribute(string $selector, string $attribute) 移除元素属性
     * @method array getAllText(string $selector) 获取所有匹配元素的文本
     * 
     * @method Browser switchToNewTab() 切换到新打开的标签页
     * @method Browser closeTabAndSwitchBack() 关闭当前标签页并切换回主标签页
     * @method Browser handleAlert(bool $accept = true) 处理弹窗（接受或取消）
     * 
     * @method Browser screenshotWithTimestamp(string $name = 'screenshot') 带时间戳的截图
     * @method Browser randomPause(int $minMs = 500, int $maxMs = 2000) 随机等待（模拟人类行为）
     * @method Browser acceptCookies(array $selectors = ['.cookie-accept', '.accept-cookies', '[data-accept-cookies]']) 智能Cookie接受
     * @method Browser closeAds(array $selectors = ['.ad-close', '.close-ad', '[data-close-ad]', '.modal-close']) 智能广告关闭
     * @method array measurePageLoad() 页面性能监控，返回加载时间等信息
     */
    class Browser
    {
        // IDE helper methods - these are implemented as macros
        
        /**
         * 等待任意一个元素出现
         * 
         * @param array $selectors CSS选择器数组
         * @param int $seconds 超时时间（秒）
         * @return Browser
         * @throws \Exception 当所有元素都未在指定时间内出现时
         */
        public function waitForAnyElement(array $selectors, int $seconds = 10): Browser {}
        
        /**
         * 等待页面完全加载
         * 
         * @param int $timeout 超时时间（秒）
         * @return Browser
         */
        public function waitForPageLoad(int $timeout = 30): Browser {}
        
        /**
         * 等待Ajax请求完成
         * 
         * @param int $timeout 超时时间（秒）
         * @return Browser
         */
        public function waitForAjax(int $timeout = 30): Browser {}
        
        /**
         * 等待加载动画消失
         * 
         * @param array $loadingSelectors 加载动画选择器数组
         * @param int $timeout 超时时间（秒）
         * @return Browser
         */
        public function waitForLoadingToFinish(array $loadingSelectors = ['.loading', '.spinner', '[data-loading]'], int $timeout = 30): Browser {}
        
        /**
         * 等待元素消失
         * 
         * @param string $selector CSS选择器
         * @param int $seconds 超时时间（秒）
         * @return Browser
         */
        public function waitUntilMissing(string $selector, int $seconds = 10): Browser {}
        
        /**
         * 等待并获取元素文本
         * 
         * @param string $selector CSS选择器
         * @param int $timeout 超时时间（秒）
         * @return string 元素文本内容
         */
        public function waitAndGetText(string $selector, int $timeout = 10): string {}
        
        /**
         * 等待URL包含特定字符串
         * 
         * @param string $needle 要查找的字符串
         * @param int $seconds 超时时间（秒）
         * @return Browser
         */
        public function waitForUrlContains(string $needle, int $seconds = 10): Browser {}
        
        /**
         * 等待页面标题包含特定文本
         * 
         * @param string $title 标题文本
         * @param int $timeout 超时时间（秒）
         * @return Browser
         */
        public function waitForTitle(string $title, int $timeout = 10): Browser {}
        
        /**
         * 智能点击（支持多种选择器策略）
         * 
         * @param string $selector CSS选择器、文本内容、或其他标识符
         * @param int $timeout 超时时间（秒）
         * @return Browser
         * @throws \Exception 当无法找到可点击元素时
         */
        public function smartClick(string $selector, int $timeout = 10): Browser {}
        
        /**
         * 智能输入（自动清空并输入）
         * 
         * @param string $selector CSS选择器
         * @param string $text 要输入的文本
         * @param bool $clear 是否先清空现有内容
         * @return Browser
         */
        public function smartType(string $selector, string $text, bool $clear = true): Browser {}
        
        /**
         * 模拟人类输入（带随机延迟）
         * 
         * @param string $selector CSS选择器
         * @param string $text 要输入的文本
         * @return Browser
         */
        public function humanType(string $selector, string $text): Browser {}
        
        /**
         * 滚动到元素并点击
         * 
         * @param string $selector CSS选择器
         * @return Browser
         */
        public function scrollAndClick(string $selector): Browser {}
        
        /**
         * 如果元素存在则点击
         * 
         * @param string $selector CSS选择器
         * @param int $timeout 超时时间（秒）
         * @return bool 是否成功点击
         */
        public function clickIfExists(string $selector, int $timeout = 5): bool {}
        
        /**
         * 批量点击所有匹配的元素
         * 
         * @param string $selector CSS选择器
         * @param int $delay 每次点击间的延迟（毫秒）
         * @return Browser
         */
        public function clickAll(string $selector, int $delay = 500): Browser {}
        
        /**
         * 智能下拉选择（支持文本和值）
         * 
         * @param string $selector CSS选择器
         * @param string $option 选项值或文本
         * @return Browser
         */
        public function smartSelect(string $selector, string $option): Browser {}
        
        /**
         * 智能表单填写
         * 
         * @param array $data 表单数据数组，键为选择器，值为要填入的数据
         * @return Browser
         */
        public function fillForm(array $data): Browser {}
        
        /**
         * 智能登录
         * 
         * @param string $usernameSelector 用户名输入框选择器
         * @param string $passwordSelector 密码输入框选择器
         * @param string $username 用户名
         * @param string $password 密码
         * @param string $submitSelector 提交按钮选择器
         * @return Browser
         */
        public function smartLogin(string $usernameSelector, string $passwordSelector, string $username, string $password, string $submitSelector = 'button[type="submit"]'): Browser {}
        
        /**
         * 智能搜索
         * 
         * @param string $searchSelector 搜索框选择器
         * @param string $query 搜索关键词
         * @param string|null $submitSelector 搜索按钮选择器（可选）
         * @return Browser
         */
        public function smartSearch(string $searchSelector, string $query, string $submitSelector = null): Browser {}
        
        /**
         * 填写表格行数据
         * 
         * @param string $tableSelector 表格选择器
         * @param int $rowIndex 行索引（从1开始）
         * @param array $data 列数据数组
         * @return Browser
         */
        public function fillTableRow(string $tableSelector, int $rowIndex, array $data): Browser {}
        
        /**
         * 智能文件上传
         * 
         * @param string $selector 文件输入框选择器
         * @param string $filePath 文件路径
         * @return Browser
         * @throws \Exception 当文件不存在时
         */
        public function smartUpload(string $selector, string $filePath): Browser {}
        
        /**
         * 检查元素是否存在（不抛出异常）
         * 
         * @param string $selector CSS选择器
         * @return bool 元素是否存在
         */
        public function hasElement(string $selector): bool {}
        
        /**
         * 获取元素属性值
         * 
         * @param string $selector CSS选择器
         * @param string $attribute 属性名
         * @return string 属性值
         */
        public function getAttribute(string $selector, string $attribute): string {}
        
        /**
         * 设置元素属性
         * 
         * @param string $selector CSS选择器
         * @param string $attribute 属性名
         * @param string $value 属性值
         * @return Browser
         */
        public function setAttribute(string $selector, string $attribute, string $value): Browser {}
        
        /**
         * 移除元素属性
         * 
         * @param string $selector CSS选择器
         * @param string $attribute 属性名
         * @return Browser
         */
        public function removeAttribute(string $selector, string $attribute): Browser {}
        
        /**
         * 获取所有匹配元素的文本
         * 
         * @param string $selector CSS选择器
         * @return array 文本数组
         */
        public function getAllText(string $selector): array {}
        
        /**
         * 切换到新打开的标签页
         * 
         * @return Browser
         */
        public function switchToNewTab(): Browser {}
        
        /**
         * 关闭当前标签页并切换回主标签页
         * 
         * @return Browser
         */
        public function closeTabAndSwitchBack(): Browser {}
        
        /**
         * 处理弹窗（接受或取消）
         * 
         * @param bool $accept 是否接受弹窗
         * @return Browser
         */
        public function handleAlert(bool $accept = true): Browser {}
        
        /**
         * 带时间戳的截图
         * 
         * @param string $name 截图文件名前缀
         * @return Browser
         */
        public function screenshotWithTimestamp(string $name = 'screenshot'): Browser {}
        
        /**
         * 随机等待（模拟人类行为）
         * 
         * @param int $minMs 最小等待时间（毫秒）
         * @param int $maxMs 最大等待时间（毫秒）
         * @return Browser
         */
        public function randomPause(int $minMs = 500, int $maxMs = 2000): Browser {}
        
        /**
         * 智能Cookie接受
         * 
         * @param array $selectors Cookie接受按钮选择器数组
         * @return Browser
         */
        public function acceptCookies(array $selectors = ['.cookie-accept', '.accept-cookies', '[data-accept-cookies]']): Browser {}
        
        /**
         * 智能广告关闭
         * 
         * @param array $selectors 广告关闭按钮选择器数组
         * @return Browser
         */
        public function closeAds(array $selectors = ['.ad-close', '.close-ad', '[data-close-ad]', '.modal-close']): Browser {}
        
        /**
         * 页面性能监控
         * 
         * @return array 包含load_time_ms和timestamp的数组
         */
        public function measurePageLoad(): array {}
    }
}

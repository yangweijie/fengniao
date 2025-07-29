<?php

namespace App\Macros;

use Laravel\Dusk\Browser;
use Laravel\Dusk\ElementResolver;
use Facebook\WebDriver\WebDriverBy;
use Facebook\WebDriver\WebDriverWait;
use Facebook\WebDriver\WebDriverExpectedCondition;
use Facebook\WebDriver\Exception\TimeoutException;
use Facebook\WebDriver\Exception\NoSuchElementException;

/**
 * Dusk 自动化操作宏集合
 * 提供常用的自动化操作方法，简化用户脚本编写
 */
class DuskMacros
{
    /**
     * 注册所有宏
     */
    public static function register(): void
    {
        // 智能等待宏
        Browser::macro('waitForAnyElement', function (array $selectors, int $seconds = 10) {
            $wait = new WebDriverWait($this->driver, $seconds);
            
            try {
                $wait->until(function () use ($selectors) {
                    foreach ($selectors as $selector) {
                        try {
                            $this->driver->findElement(WebDriverBy::cssSelector($selector));
                            return true;
                        } catch (NoSuchElementException $e) {
                            continue;
                        }
                    }
                    return false;
                });
                return $this;
            } catch (TimeoutException $e) {
                throw new \Exception("等待元素超时: " . implode(', ', $selectors));
            }
        });

        // 智能点击宏（支持多种选择器）
        Browser::macro('smartClick', function (string $selector, int $timeout = 10) {
            // 尝试不同的选择器策略
            $strategies = [
                ['css', $selector],
                ['xpath', "//*[contains(text(), '$selector')]"],
                ['xpath', "//*[@title='$selector']"],
                ['xpath', "//*[@alt='$selector']"],
                ['xpath', "//*[@placeholder='$selector']"]
            ];

            foreach ($strategies as [$type, $sel]) {
                try {
                    if ($type === 'css') {
                        $this->waitFor($sel, $timeout)->click($sel);
                    } else {
                        $element = $this->driver->findElement(WebDriverBy::xpath($sel));
                        $element->click();
                    }
                    return $this;
                } catch (\Exception $e) {
                    continue;
                }
            }
            
            throw new \Exception("无法找到可点击的元素: $selector");
        });

        // 智能输入宏（自动清空并输入）
        Browser::macro('smartType', function (string $selector, string $text, bool $clear = true) {
            $this->waitFor($selector);
            
            if ($clear) {
                // 全选并删除现有内容
                $this->keys($selector, ['{ctrl}', 'a'])->keys($selector, '{delete}');
            }
            
            return $this->type($selector, $text);
        });

        // 滚动到元素并点击
        Browser::macro('scrollAndClick', function (string $selector) {
            $this->waitFor($selector);
            $this->script("document.querySelector('$selector').scrollIntoView({behavior: 'smooth', block: 'center'});");
            $this->pause(500); // 等待滚动完成
            return $this->click($selector);
        });

        // 等待页面加载完成
        Browser::macro('waitForPageLoad', function (int $timeout = 30) {
            $wait = new WebDriverWait($this->driver, $timeout);
            $wait->until(WebDriverExpectedCondition::jsReturnsValue("return document.readyState === 'complete'"));
            return $this;
        });

        // 等待Ajax请求完成
        Browser::macro('waitForAjax', function (int $timeout = 30) {
            $wait = new WebDriverWait($this->driver, $timeout);
            $wait->until(WebDriverExpectedCondition::jsReturnsValue("return jQuery.active == 0"));
            return $this;
        });

        // 智能表单填写
        Browser::macro('fillForm', function (array $data) {
            foreach ($data as $selector => $value) {
                if (is_array($value)) {
                    // 处理下拉选择
                    $this->select($selector, $value['value'] ?? $value[0]);
                } elseif (is_bool($value)) {
                    // 处理复选框
                    if ($value) {
                        $this->check($selector);
                    } else {
                        $this->uncheck($selector);
                    }
                } else {
                    // 处理普通输入
                    $this->smartType($selector, $value);
                }
            }
            return $this;
        });

        // 截图并保存（带时间戳）
        Browser::macro('screenshotWithTimestamp', function (string $name = 'screenshot') {
            $timestamp = date('Y-m-d_H-i-s');
            $filename = "{$name}_{$timestamp}.png";
            return $this->screenshot($filename);
        });

        // 等待元素消失
        Browser::macro('waitUntilMissing', function (string $selector, int $seconds = 10) {
            $wait = new WebDriverWait($this->driver, $seconds);
            $wait->until(function () use ($selector) {
                try {
                    $this->driver->findElement(WebDriverBy::cssSelector($selector));
                    return false; // 元素还存在
                } catch (NoSuchElementException $e) {
                    return true; // 元素已消失
                }
            });
            return $this;
        });

        // 智能等待并获取文本
        Browser::macro('waitAndGetText', function (string $selector, int $timeout = 10) {
            $this->waitFor($selector, $timeout);
            return $this->text($selector);
        });

        // 模拟人类输入（带随机延迟）
        Browser::macro('humanType', function (string $selector, string $text) {
            $this->waitFor($selector)->click($selector);
            
            foreach (str_split($text) as $char) {
                $this->keys($selector, $char);
                // 随机延迟 50-150ms
                usleep(rand(50000, 150000));
            }
            
            return $this;
        });

        // 检查元素是否存在（不抛出异常）
        Browser::macro('hasElement', function (string $selector) {
            try {
                $this->driver->findElement(WebDriverBy::cssSelector($selector));
                return true;
            } catch (NoSuchElementException $e) {
                return false;
            }
        });

        // 等待并点击（如果元素存在）
        Browser::macro('clickIfExists', function (string $selector, int $timeout = 5) {
            try {
                $this->waitFor($selector, $timeout)->click($selector);
                return true;
            } catch (\Exception $e) {
                return false;
            }
        });

        // 处理弹窗
        Browser::macro('handleAlert', function (bool $accept = true) {
            try {
                $alert = $this->driver->switchTo()->alert();
                if ($accept) {
                    $alert->accept();
                } else {
                    $alert->dismiss();
                }
                return $this;
            } catch (\Exception $e) {
                // 没有弹窗，忽略
                return $this;
            }
        });

        // 切换到新标签页
        Browser::macro('switchToNewTab', function () {
            $handles = $this->driver->getWindowHandles();
            $this->driver->switchTo()->window(end($handles));
            return $this;
        });

        // 关闭当前标签页并切换回主标签页
        Browser::macro('closeTabAndSwitchBack', function () {
            $handles = $this->driver->getWindowHandles();
            $this->driver->close();
            $this->driver->switchTo()->window($handles[0]);
            return $this;
        });

        // 等待URL包含特定字符串
        Browser::macro('waitForUrlContains', function (string $needle, int $seconds = 10) {
            $wait = new WebDriverWait($this->driver, $seconds);
            $wait->until(function () use ($needle) {
                return strpos($this->driver->getCurrentURL(), $needle) !== false;
            });
            return $this;
        });

        // 智能登录宏
        Browser::macro('smartLogin', function (string $usernameSelector, string $passwordSelector, string $username, string $password, string $submitSelector = 'button[type="submit"]') {
            $this->smartType($usernameSelector, $username)
                 ->smartType($passwordSelector, $password)
                 ->click($submitSelector);
            return $this;
        });

        // 等待加载动画消失
        Browser::macro('waitForLoadingToFinish', function (array $loadingSelectors = ['.loading', '.spinner', '[data-loading]'], int $timeout = 30) {
            foreach ($loadingSelectors as $selector) {
                try {
                    $this->waitUntilMissing($selector, $timeout);
                } catch (\Exception $e) {
                    // 继续检查下一个加载选择器
                    continue;
                }
            }
            return $this;
        });

        // 智能搜索宏
        Browser::macro('smartSearch', function (string $searchSelector, string $query, string $submitSelector = null) {
            $this->smartType($searchSelector, $query);
            
            if ($submitSelector) {
                $this->click($submitSelector);
            } else {
                // 尝试按回车键
                $this->keys($searchSelector, '{enter}');
            }
            
            return $this;
        });

        // 批量点击元素
        Browser::macro('clickAll', function (string $selector, int $delay = 500) {
            $elements = $this->driver->findElements(WebDriverBy::cssSelector($selector));
            
            foreach ($elements as $element) {
                $element->click();
                if ($delay > 0) {
                    usleep($delay * 1000);
                }
            }
            
            return $this;
        });

        // 获取所有匹配元素的文本
        Browser::macro('getAllText', function (string $selector) {
            $elements = $this->driver->findElements(WebDriverBy::cssSelector($selector));
            $texts = [];
            
            foreach ($elements as $element) {
                $texts[] = $element->getText();
            }
            
            return $texts;
        });

        // 随机等待（模拟人类行为）
        Browser::macro('randomPause', function (int $minMs = 500, int $maxMs = 2000) {
            $delay = rand($minMs, $maxMs);
            usleep($delay * 1000);
            return $this;
        });

        // 智能表格操作
        Browser::macro('fillTableRow', function (string $tableSelector, int $rowIndex, array $data) {
            foreach ($data as $columnIndex => $value) {
                $cellSelector = "$tableSelector tbody tr:nth-child($rowIndex) td:nth-child($columnIndex) input, $tableSelector tbody tr:nth-child($rowIndex) td:nth-child($columnIndex) select";
                if ($this->hasElement($cellSelector)) {
                    $this->smartType($cellSelector, $value);
                }
            }
            return $this;
        });

        // 智能下拉选择（支持文本和值）
        Browser::macro('smartSelect', function (string $selector, string $option) {
            $this->waitFor($selector);

            // 尝试按值选择
            try {
                $this->select($selector, $option);
                return $this;
            } catch (\Exception $e) {
                // 尝试按文本选择
                $this->script("
                    const select = document.querySelector('$selector');
                    const options = select.options;
                    for (let i = 0; i < options.length; i++) {
                        if (options[i].text.includes('$option')) {
                            select.selectedIndex = i;
                            select.dispatchEvent(new Event('change'));
                            break;
                        }
                    }
                ");
                return $this;
            }
        });

        // 等待并验证页面标题
        Browser::macro('waitForTitle', function (string $title, int $timeout = 10) {
            $wait = new WebDriverWait($this->driver, $timeout);
            $wait->until(function () use ($title) {
                return strpos($this->driver->getTitle(), $title) !== false;
            });
            return $this;
        });

        // 智能文件上传
        Browser::macro('smartUpload', function (string $selector, string $filePath) {
            if (!file_exists($filePath)) {
                throw new \Exception("文件不存在: $filePath");
            }

            $this->waitFor($selector);
            $this->attach($selector, $filePath);
            return $this;
        });

        // 获取元素属性
        Browser::macro('getAttribute', function (string $selector, string $attribute) {
            $this->waitFor($selector);
            return $this->driver->findElement(WebDriverBy::cssSelector($selector))->getAttribute($attribute);
        });

        // 设置元素属性
        Browser::macro('setAttribute', function (string $selector, string $attribute, string $value) {
            $this->waitFor($selector);
            $this->script("document.querySelector('$selector').setAttribute('$attribute', '$value');");
            return $this;
        });

        // 移除元素属性
        Browser::macro('removeAttribute', function (string $selector, string $attribute) {
            $this->waitFor($selector);
            $this->script("document.querySelector('$selector').removeAttribute('$attribute');");
            return $this;
        });

        // 智能Cookie管理
        Browser::macro('acceptCookies', function (array $selectors = ['.cookie-accept', '.accept-cookies', '[data-accept-cookies]']) {
            foreach ($selectors as $selector) {
                if ($this->clickIfExists($selector)) {
                    return $this;
                }
            }
            return $this;
        });

        // 智能广告关闭
        Browser::macro('closeAds', function (array $selectors = ['.ad-close', '.close-ad', '[data-close-ad]', '.modal-close']) {
            foreach ($selectors as $selector) {
                $this->clickIfExists($selector, 2);
            }
            return $this;
        });

        // 页面性能监控
        Browser::macro('measurePageLoad', function () {
            $startTime = microtime(true);
            $this->waitForPageLoad();
            $endTime = microtime(true);
            $loadTime = ($endTime - $startTime) * 1000; // 转换为毫秒

            return [
                'load_time_ms' => round($loadTime, 2),
                'timestamp' => date('Y-m-d H:i:s')
            ];
        });
    }
}

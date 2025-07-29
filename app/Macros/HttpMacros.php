<?php

namespace App\Macros;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class HttpMacros
{
    /**
     * 注册所有HTTP宏
     */
    public static function register()
    {
        // 智能重试请求
        Http::macro('smartRetry', function (string $url, array $options = [], int $maxRetries = 3, int $delay = 1000) {
            $attempt = 0;
            
            while ($attempt < $maxRetries) {
                try {
                    $response = Http::timeout(30)->get($url, $options);
                    
                    if ($response->successful()) {
                        return $response;
                    }
                    
                    $attempt++;
                    if ($attempt < $maxRetries) {
                        usleep($delay * 1000); // 转换为微秒
                        $delay *= 2; // 指数退避
                    }
                } catch (\Exception $e) {
                    $attempt++;
                    if ($attempt >= $maxRetries) {
                        throw $e;
                    }
                    usleep($delay * 1000);
                    $delay *= 2;
                }
            }
            
            throw new \Exception("请求失败，已重试 {$maxRetries} 次");
        });

        // 带日志的请求
        Http::macro('withLogging', function (string $logPrefix = 'HTTP') {
            return Http::beforeSending(function ($request, $options) use ($logPrefix) {
                Log::info("{$logPrefix} 请求", [
                    'method' => $request->method(),
                    'url' => $request->url(),
                    'headers' => $request->headers(),
                    'body' => $request->body()
                ]);
            })->afterSending(function ($request, $response) use ($logPrefix) {
                Log::info("{$logPrefix} 响应", [
                    'status' => $response->status(),
                    'headers' => $response->headers(),
                    'body' => $response->body()
                ]);
            });
        });

        // 快速JSON API请求
        Http::macro('jsonApi', function (string $baseUrl = '') {
            return Http::withHeaders([
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
            ])->baseUrl($baseUrl);
        });

        // 带认证的API请求
        Http::macro('apiWithAuth', function (string $token, string $type = 'Bearer', string $baseUrl = '') {
            return Http::withHeaders([
                'Authorization' => "{$type} {$token}",
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
            ])->baseUrl($baseUrl);
        });

        // 表单数据请求
        Http::macro('formData', function (array $data = []) {
            return Http::asForm()->withBody(http_build_query($data), 'application/x-www-form-urlencoded');
        });

        // 文件上传请求
        Http::macro('uploadFile', function (string $fieldName, string $filePath, array $additionalData = []) {
            $multipart = [
                [
                    'name' => $fieldName,
                    'contents' => fopen($filePath, 'r'),
                    'filename' => basename($filePath)
                ]
            ];

            foreach ($additionalData as $key => $value) {
                $multipart[] = [
                    'name' => $key,
                    'contents' => $value
                ];
            }

            return Http::attach($multipart);
        });

        // 下载文件
        Http::macro('downloadFile', function (string $url, string $savePath, array $headers = []) {
            $response = Http::withHeaders($headers)->get($url);
            
            if ($response->successful()) {
                file_put_contents($savePath, $response->body());
                return [
                    'success' => true,
                    'path' => $savePath,
                    'size' => strlen($response->body())
                ];
            }
            
            return [
                'success' => false,
                'error' => "下载失败: HTTP {$response->status()}"
            ];
        });

        // 批量请求
        Http::macro('batchRequests', function (array $requests, int $concurrent = 5) {
            $responses = [];
            $chunks = array_chunk($requests, $concurrent);
            
            foreach ($chunks as $chunk) {
                $promises = [];
                
                foreach ($chunk as $key => $request) {
                    $method = $request['method'] ?? 'GET';
                    $url = $request['url'];
                    $data = $request['data'] ?? [];
                    $headers = $request['headers'] ?? [];
                    
                    $promises[$key] = Http::withHeaders($headers)->async()->{strtolower($method)}($url, $data);
                }
                
                $chunkResponses = Http::pool(fn ($pool) => $promises);
                $responses = array_merge($responses, $chunkResponses);
            }
            
            return $responses;
        });

        // 健康检查
        Http::macro('healthCheck', function (string $url, int $timeout = 10) {
            try {
                $start = microtime(true);
                $response = Http::timeout($timeout)->get($url);
                $duration = (microtime(true) - $start) * 1000; // 转换为毫秒
                
                return [
                    'status' => $response->status(),
                    'success' => $response->successful(),
                    'response_time' => round($duration, 2),
                    'headers' => $response->headers(),
                    'body_size' => strlen($response->body())
                ];
            } catch (\Exception $e) {
                return [
                    'status' => 0,
                    'success' => false,
                    'error' => $e->getMessage(),
                    'response_time' => null
                ];
            }
        });

        // 分页数据获取
        Http::macro('getAllPages', function (string $baseUrl, array $params = [], string $pageParam = 'page', string $dataKey = 'data') {
            $allData = [];
            $page = 1;
            
            do {
                $params[$pageParam] = $page;
                $response = Http::get($baseUrl, $params);
                
                if (!$response->successful()) {
                    break;
                }
                
                $data = $response->json();
                $pageData = $data[$dataKey] ?? [];
                
                if (empty($pageData)) {
                    break;
                }
                
                $allData = array_merge($allData, $pageData);
                $page++;
                
                // 防止无限循环
                if ($page > 1000) {
                    break;
                }
                
            } while (!empty($pageData));
            
            return $allData;
        });

        // 速率限制请求
        Http::macro('withRateLimit', function (int $requestsPerMinute = 60) {
            static $lastRequestTime = 0;
            static $requestCount = 0;
            
            $now = time();
            $interval = 60 / $requestsPerMinute; // 每个请求之间的间隔（秒）
            
            if ($now - $lastRequestTime < $interval) {
                $sleepTime = $interval - ($now - $lastRequestTime);
                usleep($sleepTime * 1000000); // 转换为微秒
            }
            
            $lastRequestTime = time();
            $requestCount++;
            
            return Http::beforeSending(function ($request) use ($requestCount) {
                Log::debug("速率限制请求 #{$requestCount}", ['url' => $request->url()]);
            });
        });

        // 缓存响应
        Http::macro('withCache', function (int $ttl = 300) {
            return Http::beforeSending(function ($request) use ($ttl) {
                $cacheKey = 'http_cache_' . md5($request->url() . serialize($request->data()));
                
                if (cache()->has($cacheKey)) {
                    return cache()->get($cacheKey);
                }
                
                return null;
            })->afterSending(function ($request, $response) use ($ttl) {
                if ($response->successful()) {
                    $cacheKey = 'http_cache_' . md5($request->url() . serialize($request->data()));
                    cache()->put($cacheKey, $response, $ttl);
                }
            });
        });

        // 模拟浏览器请求
        Http::macro('asBrowser', function (string $userAgent = null) {
            $defaultUserAgent = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36';
            
            return Http::withHeaders([
                'User-Agent' => $userAgent ?: $defaultUserAgent,
                'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8',
                'Accept-Language' => 'zh-CN,zh;q=0.9,en;q=0.8',
                'Accept-Encoding' => 'gzip, deflate, br',
                'DNT' => '1',
                'Connection' => 'keep-alive',
                'Upgrade-Insecure-Requests' => '1',
            ]);
        });

        // 代理请求
        Http::macro('withProxy', function (string $proxy, string $auth = null) {
            $options = ['proxy' => $proxy];
            
            if ($auth) {
                $options['proxy'] = [
                    'http' => $proxy,
                    'https' => $proxy,
                    'auth' => $auth
                ];
            }
            
            return Http::withOptions($options);
        });
    }
}

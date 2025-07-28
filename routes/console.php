<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// 任务调度配置
Schedule::command('tasks:schedule')
    ->everyMinute()
    ->withoutOverlapping()
    ->runInBackground();

// 清理过期Cookie
Schedule::call(function () {
    app(\App\Services\CookieManager::class)->cleanExpiredCookies();
})->daily();

// 清理旧日志（保留30天）
Schedule::call(function () {
    \App\Models\TaskLog::where('created_at', '<', now()->subDays(30))->delete();
})->daily();

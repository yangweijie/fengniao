<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;

use Illuminate\Support\Facades\Session;
use App\Http\Controllers\AuthController;

Route::get('/', function () {
    return view('welcome');
});

// 登录相关路由
Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login'])->name('login');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

// 测试页面路由（登录保护）
Route::get('/test-page', function () {
    if (!Session::has('user')) {
        return redirect()->route('login');
    }
    return view('test-page');
})->name('test-page');

// 健康检查路由
Route::get('/health', function () {
    return response()->json([
        'status' => 'healthy',
        'timestamp' => now()->toISOString(),
        'app_name' => config('app.name'),
        'app_locale' => config('app.locale'),
        'version' => '1.0.0'
    ]);
});

// 中文语言测试路由
Route::get('/lang-test', function () {
    return response()->json([
        'locale' => config('app.locale'),
        'messages' => [
            'welcome' => __('messages.welcome'),
            'tasks' => __('messages.tasks'),
            'workflows' => __('messages.workflows'),
            'browsers' => __('messages.browsers'),
            'monitoring' => __('messages.monitoring'),
            'users' => __('messages.users'),
            'settings' => __('messages.settings'),
        ]
    ], 200, [], JSON_UNESCAPED_UNICODE);
});

// 截图查看路由 - 现在直接使用storage链接
Route::get('/screenshot/{filename}', function (string $filename) {
    $path = "screenshots/{$filename}";

    if (!Storage::disk('public')->exists($path)) {
        abort(404, '截图不存在');
    }

    return response()->file(Storage::disk('public')->path($path));
})->name('screenshot.view');

// 实时日志查看器路由
Route::get('/logs/realtime/{executionId?}', function (?int $executionId = null) {
    return view('logs.terminal-logs', compact('executionId'));
})->name('logs.realtime');

Route::get('/logs/task/{taskId}', function (int $taskId) {
    return view('logs.terminal-logs', ['taskId' => $taskId, 'executionId' => null]);
})->name('logs.task');

// API路由用于日志数据
Route::prefix('api')->group(function () {
    Route::get('/tasks/{taskId}/logs', [App\Http\Controllers\Api\LogController::class, 'getTaskLogs']);
    Route::get('/executions/{executionId}/logs', [App\Http\Controllers\Api\LogController::class, 'getExecutionLogs']);
    Route::get('/tasks/{taskId}/logs/text', [App\Http\Controllers\Api\LogController::class, 'getLogText']);
});

// 截图预览功能测试页面
Route::get('/test-screenshot', function () {
    return view('test-screenshot');
})->name('test.screenshot');

// 测试日志弹窗
Route::get('/test-logs-modal', function () {
    return view('test-logs-modal');
});

// 工作流编辑器路由
Route::get('/workflow/editor/{workflowId?}', function (?int $workflowId = null) {
    return view('workflow.editor', compact('workflowId'));
})->name('workflow.editor');

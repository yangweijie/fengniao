<?php

use Illuminate\Support\Facades\Route;
use App\Services\LogManager;

Route::get('/', function () {
    return view('welcome');
});

// 截图查看路由
Route::get('/screenshot/{filename}', function (string $filename, LogManager $logManager) {
    $content = $logManager->getScreenshotContent($filename);

    if (!$content) {
        abort(404, '截图不存在');
    }

    return response($content)
        ->header('Content-Type', 'image/png')
        ->header('Content-Disposition', 'inline; filename="' . $filename . '"');
})->name('screenshot.view');

// 实时日志查看器路由
Route::get('/logs/realtime/{executionId?}', function (?int $executionId = null) {
    return view('logs.realtime', compact('executionId'));
})->name('logs.realtime');

Route::get('/logs/task/{taskId}', function (int $taskId) {
    return view('logs.realtime', ['taskId' => $taskId, 'executionId' => null]);
})->name('logs.task');

// 工作流编辑器路由
Route::get('/workflow/editor/{workflowId?}', function (?int $workflowId = null) {
    return view('workflow.editor', compact('workflowId'));
})->name('workflow.editor');

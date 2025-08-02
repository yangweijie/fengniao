<?php
namespace App\Console\Commands;

use App\Models\Task;
use Illuminate\Console\Command;

class CreateTask extends Command
{
    protected $signature = 'task:create {name} '
        . '{--type=browser} '
        . '{--url=} '
        . '{--schedule=} '
        . '{--login_config=} '
        . '{--env_vars=} '
        . '{--notification_config=} '
        . '{--script_content=}';
    protected $description = '创建一个自动化任务';

    public function handle()
    {
        $name = $this->argument('name');
        $type = $this->option('type');
        $url = $this->option('url');
        $schedule = $this->option('schedule');
        $loginConfig = $this->option('login_config');
        $envVars = $this->option('env_vars');
        $notificationConfig = $this->option('notification_config');
        $scriptContent = $this->option('script_content');

        $task = new Task();
        $task->name = $name;
        $task->type = $type;
        $task->cron_expression = $schedule;
        $task->status = 'enabled';
        $task->description = '命令行创建';
        if ($url) {
            $task->domain = parse_url($url, PHP_URL_HOST);
            if ($scriptContent) {
                $task->script_content = $scriptContent;
            } else {
                $task->script_content = $this->generateDuskScript($url, $loginConfig, $envVars);
            }
        } else if ($scriptContent) {
            $task->script_content = $scriptContent;
        }
        if ($loginConfig) {
            $task->login_config = json_decode($loginConfig, true);
        }
        if ($envVars) {
            $task->env_vars = json_decode($envVars, true);
        }
        if ($notificationConfig) {
            $task->notification_config = json_decode($notificationConfig, true);
        }
        $task->save();
        $this->info("任务已创建，ID: {$task->id}");
    }

    private function generateDuskScript($url, $loginConfig, $envVars)
    {
        // 生成 Dusk 脚本，自动访问 /test-page 并登录
        // 注意：task_id 可通过上下文传入或在脚本执行环境中注入
        return <<<'EOD'
$browser->visit('/test-page');
if ($browser->see('登录') || $browser->see('账号')) {
    $browser->visit('/login')
        ->type('username', env('TASK_USERNAME', 'test'))
        ->type('password', env('TASK_PASSWORD', 'test'))
        ->press('登录');
}
$cookie = $browser->driver->manage()->getCookieNamed('laravel_session');
if ($cookie) {
    // 需在脚本环境中注入 $task_id 变量
    saveEnvVar($task_id, 'LARAVEL_SESSION', $cookie['value']);
}
EOD;
    }
}

// 全局辅助函数：更新任务环境变量
function saveEnvVar($task_id, $key, $value) {
    $task = \App\Models\Task::find($task_id);
    if ($task) {
        $env = $task->env_vars ?: [];
        $env[$key] = $value;
        $task->env_vars = $env;
        $task->save();
    }
}

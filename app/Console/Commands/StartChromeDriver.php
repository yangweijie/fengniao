<?php

namespace App\Console\Commands;

use App\Services\ChromeDriverManager;
use Illuminate\Console\Command;

class StartChromeDriver extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'chrome:start {--restart : 重启ChromeDriver}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '启动ChromeDriver服务';

    /**
     * Execute the console command.
     */
    public function handle(ChromeDriverManager $chromeDriverManager)
    {
        if ($this->option('restart')) {
            $this->info('重启ChromeDriver...');
            if ($chromeDriverManager->restart()) {
                $this->info('ChromeDriver重启成功');
            } else {
                $this->error('ChromeDriver重启失败');
                return Command::FAILURE;
            }
        } else {
            $this->info('启动ChromeDriver...');
            if ($chromeDriverManager->start()) {
                $this->info('ChromeDriver启动成功');
            } else {
                $this->error('ChromeDriver启动失败');
                return Command::FAILURE;
            }
        }

        // 显示状态
        $status = $chromeDriverManager->getStatus();
        $this->table(
            ['属性', '值'],
            [
                ['运行状态', $status['is_running'] ? '运行中' : '已停止'],
                ['端口', $status['port']],
                ['进程ID', $status['process_id'] ?? 'N/A']
            ]
        );

        return Command::SUCCESS;
    }
}

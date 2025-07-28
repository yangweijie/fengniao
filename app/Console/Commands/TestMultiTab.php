<?php

namespace App\Console\Commands;

use App\Models\Task;
use App\Services\BrowserPoolManager;
use Illuminate\Console\Command;

class TestMultiTab extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test:multitab';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '测试多标签页并行执行功能';

    /**
     * Execute the console command.
     */
    public function handle(BrowserPoolManager $browserPool)
    {
        $this->info('开始测试多标签页功能...');

        // 创建两个测试任务
        $task1 = new Task([
            'name' => '测试任务1',
            'type' => 'browser',
            'domain' => 'httpbin.org',
            'is_exclusive' => false
        ]);

        $task2 = new Task([
            'name' => '测试任务2',
            'type' => 'browser',
            'domain' => 'httpbin.org',
            'is_exclusive' => false
        ]);

        try {
            // 获取浏览器实例
            $this->info('获取第一个浏览器实例...');
            $browser1 = $browserPool->getBrowser($task1);
            $this->info("浏览器实例1: {$browser1->id}");

            // 创建第一个标签页
            $this->info('创建第一个标签页...');
            $tab1 = $browser1->newTab($task1);
            $this->info("标签页1: {$tab1->id}");

            // 获取第二个浏览器实例（应该复用同一个实例）
            $this->info('获取第二个浏览器实例...');
            $browser2 = $browserPool->getBrowser($task2);
            $this->info("浏览器实例2: {$browser2->id}");

            // 创建第二个标签页
            $this->info('创建第二个标签页...');
            $tab2 = $browser2->newTab($task2);
            $this->info("标签页2: {$tab2->id}");

            // 显示浏览器池状态
            $status = $browserPool->getPoolStatus();
            $this->info('浏览器池状态:');
            $this->table(
                ['属性', '值'],
                [
                    ['总实例数', $status['total_instances']],
                    ['最大实例数', $status['max_instances']]
                ]
            );

            if (!empty($status['instances'])) {
                $this->info('实例详情:');
                foreach ($status['instances'] as $instance) {
                    $this->line("实例ID: {$instance['id']}");
                    $this->line("独占模式: " . ($instance['is_exclusive'] ? '是' : '否'));
                    $this->line("主域名: {$instance['primary_domain']}");
                    $this->line("活跃标签页: {$instance['active_tabs']}");
                    $this->line("状态: {$instance['status']}");
                    $this->line('---');
                }
            }

            // 清理资源
            $this->info('清理资源...');
            $browser1->closeTab($tab1);
            $browser2->closeTab($tab2);
            $browserPool->releaseBrowser($browser1);
            $browserPool->releaseBrowser($browser2);

            $this->info('多标签页测试完成！');

        } catch (\Exception $e) {
            $this->error("测试失败: " . $e->getMessage());
            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }
}

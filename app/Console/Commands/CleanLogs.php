<?php

namespace App\Console\Commands;

use App\Services\LogManager;
use Illuminate\Console\Command;

class CleanLogs extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'logs:clean {--days=30 : 保留天数} {--force : 强制清理不询问}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '清理旧的任务日志和截图';

    /**
     * Execute the console command.
     */
    public function handle(LogManager $logManager)
    {
        $days = (int) $this->option('days');
        $force = $this->option('force');

        $this->info("准备清理 {$days} 天前的日志...");

        // 获取统计信息
        $stats = $logManager->getLogStatistics($days);

        $this->table(
            ['统计项', '数量'],
            [
                ['总日志数', $stats['total_logs']],
                ['截图数量', $stats['screenshot_count']],
                ['统计周期', "{$stats['period_days']} 天"],
                ['开始日期', $stats['start_date']]
            ]
        );

        if (!$force && !$this->confirm("确定要清理 {$days} 天前的日志吗？")) {
            $this->info('操作已取消');
            return Command::SUCCESS;
        }

        try {
            $this->info('开始清理日志...');

            $deletedCount = $logManager->cleanOldLogs($days);

            $this->info("清理完成！");
            $this->info("删除了 {$deletedCount} 条日志记录");

            // 显示清理后的统计
            $newStats = $logManager->getLogStatistics(7);
            $this->info("当前7天内日志数量: {$newStats['total_logs']}");

        } catch (\Exception $e) {
            $this->error("清理失败: " . $e->getMessage());
            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }
}

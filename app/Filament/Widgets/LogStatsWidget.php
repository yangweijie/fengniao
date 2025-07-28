<?php

namespace App\Filament\Widgets;

use App\Services\LogManager;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class LogStatsWidget extends BaseWidget
{
    protected static ?int $sort = 2;

    protected int | string | array $columnSpan = 'full';

    protected function getStats(): array
    {
        $logManager = app(LogManager::class);
        $stats = $logManager->getLogStatistics(7);

        return [
            Stat::make('总日志数', $stats['total_logs'])
                ->description('过去7天的日志总数')
                ->descriptionIcon('heroicon-m-document-text')
                ->color('primary'),

            Stat::make('错误日志', $stats['by_level']['error'] ?? 0)
                ->description('需要关注的错误')
                ->descriptionIcon('heroicon-m-exclamation-triangle')
                ->color('danger'),

            Stat::make('警告日志', $stats['by_level']['warning'] ?? 0)
                ->description('警告信息')
                ->descriptionIcon('heroicon-m-exclamation-circle')
                ->color('warning'),

            Stat::make('截图数量', $stats['screenshot_count'])
                ->description('自动截图记录')
                ->descriptionIcon('heroicon-m-camera')
                ->color('success'),

            Stat::make('信息日志', $stats['by_level']['info'] ?? 0)
                ->description('一般信息记录')
                ->descriptionIcon('heroicon-m-information-circle')
                ->color('info'),
        ];
    }
}

<?php

namespace App\Filament\Widgets;

use App\Services\CookieManager;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class CookieStatsWidget extends BaseWidget
{
    protected function getStats(): array
    {
        $cookieManager = app(CookieManager::class);
        $stats = $cookieManager->getStatistics();

        return [
            Stat::make('总Cookie数', $stats['total_cookies'])
                ->description('系统中所有Cookie记录')
                ->descriptionIcon('heroicon-m-key')
                ->color('primary'),

            Stat::make('有效Cookie', $stats['valid_cookies'])
                ->description('当前有效的Cookie')
                ->descriptionIcon('heroicon-m-check-circle')
                ->color('success'),

            Stat::make('已过期', $stats['expired_cookies'])
                ->description('已过期的Cookie')
                ->descriptionIcon('heroicon-m-x-circle')
                ->color('danger'),

            Stat::make('即将过期', $stats['expiring_soon'])
                ->description('24小时内过期')
                ->descriptionIcon('heroicon-m-exclamation-triangle')
                ->color('warning'),

            Stat::make('管理域名', $stats['domains'])
                ->description('涉及的域名数量')
                ->descriptionIcon('heroicon-m-globe-alt')
                ->color('info'),
        ];
    }
}

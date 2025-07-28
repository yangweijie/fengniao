<?php

namespace App\Filament\Resources\TaskResource\Pages;

use App\Filament\Resources\TaskResource;
use App\Models\Task;
use App\Models\TaskLog;
use Filament\Resources\Pages\Page;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Contracts\View\View;

class ViewTaskLogs extends Page implements HasTable
{
    use InteractsWithTable;

    protected static string $resource = TaskResource::class;

    protected static string $view = 'filament.resources.task-resource.pages.view-task-logs';

    public Task $record;

    public function mount(int|string $record): void
    {
        $this->record = $this->resolveRecord($record);
        
        static::authorizeResourceAccess();
    }

    protected function resolveRecord(int|string $key): Task
    {
        return Task::findOrFail($key);
    }

    public function getTitle(): string
    {
        return "任务日志 - {$this->record->name}";
    }

    public function getBreadcrumbs(): array
    {
        return [
            url()->route('filament.admin.resources.tasks.index') => '任务管理',
            '#' => $this->getTitle(),
        ];
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(
                TaskLog::query()
                    ->whereHas('execution', function (Builder $query) {
                        $query->where('task_id', $this->record->id);
                    })
                    ->with(['execution'])
                    ->latest()
            )
            ->columns([
                Tables\Columns\TextColumn::make('execution.id')
                    ->label('执行ID')
                    ->sortable()
                    ->searchable(),
                
                Tables\Columns\TextColumn::make('level')
                    ->label('级别')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'error' => 'danger',
                        'warning' => 'warning',
                        'info' => 'info',
                        'debug' => 'gray',
                        default => 'success',
                    })
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('message')
                    ->label('消息')
                    ->limit(100)
                    ->tooltip(function (TaskLog $record): ?string {
                        return strlen($record->message) > 100 ? $record->message : null;
                    })
                    ->searchable(),
                
                Tables\Columns\ImageColumn::make('screenshot_path')
                    ->label('截图预览')
                    ->getStateUsing(function ($record) {
                        return $record->screenshot_path ? route('screenshot.view', $record->screenshot_path) : null;
                    })
                    ->size(64)
                    ->square()
                    ->defaultImageUrl('data:image/svg+xml;base64,' . base64_encode('
                        <svg xmlns="http://www.w3.org/2000/svg" width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728L5.636 5.636m12.728 12.728L18.364 5.636M5.636 18.364l12.728-12.728"/>
                        </svg>
                    '))
                    ->action(
                        Tables\Actions\Action::make('preview_screenshot')
                            ->modalHeading('截图预览')
                            ->modalContent(function (TaskLog $record) {
                                if (!$record->screenshot_path) {
                                    return view('filament.components.no-screenshot');
                                }
                                return view('filament.components.screenshot-preview', [
                                    'screenshotUrl' => route('screenshot.view', $record->screenshot_path),
                                    'filename' => $record->screenshot_path
                                ]);
                            })
                            ->modalSubmitAction(false)
                            ->modalCancelActionLabel('关闭')
                            ->modalWidth('5xl')
                            ->visible(fn ($record) => !empty($record->screenshot_path))
                    )
                    ->sortable(false)
                    ->searchable(false),

                Tables\Columns\TextColumn::make('context')
                    ->label('上下文')
                    ->limit(50)
                    ->tooltip(function (TaskLog $record): ?string {
                        return $record->context ? json_encode($record->context, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) : null;
                    })
                    ->formatStateUsing(function ($state) {
                        if (is_array($state) && !empty($state)) {
                            return '有数据';
                        }
                        return '无';
                    }),
                
                Tables\Columns\TextColumn::make('created_at')
                    ->label('创建时间')
                    ->dateTime('Y-m-d H:i:s')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('level')
                    ->label('日志级别')
                    ->options([
                        'debug' => 'Debug',
                        'info' => 'Info',
                        'warning' => 'Warning',
                        'error' => 'Error',
                    ]),
                
                Tables\Filters\Filter::make('created_at')
                    ->label('创建时间')
                    ->form([
                        \Filament\Forms\Components\DatePicker::make('created_from')
                            ->label('开始日期'),
                        \Filament\Forms\Components\DatePicker::make('created_until')
                            ->label('结束日期'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['created_from'],
                                fn (Builder $query, $date): Builder => $query->whereDate('created_at', '>=', $date),
                            )
                            ->when(
                                $data['created_until'],
                                fn (Builder $query, $date): Builder => $query->whereDate('created_at', '<=', $date),
                            );
                    }),
            ])
            ->actions([
                Tables\Actions\Action::make('preview_screenshot')
                    ->label('预览截图')
                    ->icon('heroicon-o-eye')
                    ->color('info')
                    ->visible(fn ($record) => !empty($record->screenshot_path))
                    ->modalHeading('截图预览')
                    ->modalContent(function (TaskLog $record) {
                        return view('filament.components.screenshot-preview', [
                            'screenshotUrl' => route('screenshot.view', $record->screenshot_path),
                            'filename' => $record->screenshot_path
                        ]);
                    })
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('关闭')
                    ->modalWidth('5xl'),

                Tables\Actions\ViewAction::make()
                    ->label('查看详情')
                    ->modalHeading('日志详情')
                    ->modalContent(function (TaskLog $record): View {
                        return view('filament.resources.task-resource.pages.log-detail-modal', [
                            'log' => $record
                        ]);
                    }),
            ])
            ->defaultSort('created_at', 'desc')
            ->poll('30s')
            ->emptyStateHeading('暂无日志')
            ->emptyStateDescription('此任务还没有生成任何日志记录。')
            ->emptyStateIcon('heroicon-o-document-text');
    }

    protected function getHeaderActions(): array
    {
        return [
            \Filament\Actions\Action::make('back')
                ->label('返回任务列表')
                ->icon('heroicon-o-arrow-left')
                ->color('gray')
                ->url(route('filament.admin.resources.tasks.index')),
            
            \Filament\Actions\Action::make('refresh')
                ->label('刷新')
                ->icon('heroicon-o-arrow-path')
                ->color('info')
                ->action(function () {
                    $this->resetTable();
                }),
        ];
    }


}

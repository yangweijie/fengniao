<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TaskResource\Pages;
use App\Models\Task;
use App\Services\TaskService;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Tabs;
use Filament\Forms\Components\Actions;
use Filament\Forms\Components\Actions\Action;
use Filament\Support\Enums\MaxWidth;
use AbdelhamidErrahmouni\FilamentMonacoEditor\MonacoEditor;

class TaskResource extends Resource
{
    protected static ?string $model = Task::class;

    protected static ?string $navigationIcon = 'heroicon-o-cog-6-tooth';

    protected static ?string $navigationLabel = '任务管理';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Tabs::make('任务配置')
                    ->tabs([
                        Tabs\Tab::make('基本信息')
                            ->schema([
                                TextInput::make('name')
                                    ->label('任务名称')
                                    ->required()
                                    ->maxLength(255),

                                Textarea::make('description')
                                    ->label('任务描述')
                                    ->rows(3),

                                Select::make('type')
                                    ->label('任务类型')
                                    ->options([
                                        'browser' => '浏览器任务',
                                        'api' => 'API任务'
                                    ])
                                    ->default('browser')
                                    ->required(),

                                Select::make('status')
                                    ->label('状态')
                                    ->options([
                                        'enabled' => '启用',
                                        'disabled' => '禁用'
                                    ])
                                    ->default('enabled')
                                    ->required(),

                                TextInput::make('cron_expression')
                                    ->label('Cron表达式')
                                    ->required()
                                    ->placeholder('0 9 * * *')
                                    ->helperText('例如: 0 9 * * * (每天9点执行)'),

                                TextInput::make('domain')
                                    ->label('主域名')
                                    ->placeholder('example.com')
                                    ->helperText('用于浏览器实例优化分配'),

                                Toggle::make('is_exclusive')
                                    ->label('独占模式')
                                    ->helperText('独占模式下任务将独占整个浏览器实例'),

                                Toggle::make('debug_mode')
                                    ->label('调试模式')
                                    ->helperText('开启后显示浏览器界面，便于调试；关闭后使用无头模式')
                                    ->default(false)
                            ]),

                        Tabs\Tab::make('脚本内容')
                            ->schema([
                                Actions::make([
                                    Action::make('script_help')
                                        ->label('脚本语法帮助')
                                        ->icon('heroicon-o-question-mark-circle')
                                        ->color('info')
                                        ->size('sm')
                                        ->modalHeading('脚本语法帮助')
                                        ->modalWidth(MaxWidth::SevenExtraLarge)
                                        ->modalContent(view('filament.modals.script-help-test'))
                                        ->modalSubmitAction(false)
                                        ->modalCancelActionLabel('关闭')
                                ])->alignEnd(),

                                MonacoEditor::make('script_content')
                                    ->label('脚本内容')
                                    ->language('php')
                                    ->placeholderText('请输入Dusk脚本代码...')
                                    ->helperText('支持PHP语法高亮和智能提示。点击上方"脚本语法帮助"查看可用的宏方法和变量。')
                                    ->theme('vs-dark')
                                    ->fontSize('14px')
                                    ->automaticLayout(true)
                                    ->showFullScreenToggle(true)
                                    ->lineNumbersMinChars(3)
                            ]),

                        Tabs\Tab::make('登录配置')
                            ->schema([
                                Actions::make([
                                    Action::make('login_config_help')
                                        ->label('使用说明')
                                        ->icon('heroicon-o-question-mark-circle')
                                        ->color('info')
                                        ->size('sm')
                                        ->modalHeading('登录配置使用指南')
                                        ->modalWidth(MaxWidth::SevenExtraLarge)
                                        ->modalContent(view('filament.modals.login-config-help'))
                                        ->modalSubmitAction(false)
                                        ->modalCancelActionLabel('关闭')
                                ])->alignEnd(),

                                KeyValue::make('login_config')
                                    ->label('登录配置')
                                    ->keyLabel('配置项')
                                    ->valueLabel('值')
                                    ->helperText('配置自动登录相关信息。点击上方"使用说明"查看详细配置方法和示例。')
                                    ->addActionLabel('添加配置项')
                                    ->reorderable()
                                    ->deletable()
                                    ->editableKeys()
                                    ->editableValues()
                                    ->default([
                                        'login_url' => '',
                                        'username' => '',
                                        'password' => '',
                                        'username_selector' => '',
                                        'password_selector' => '',
                                        'login_button_selector' => '',
                                        'success_url' => '',
                                    ])
                            ]),

                        Tabs\Tab::make('环境变量')
                            ->schema([
                                KeyValue::make('env_vars')
                                    ->label('环境变量')
                                    ->keyLabel('变量名')
                                    ->valueLabel('变量值')
                                    ->helperText('任务执行时的环境变量')
                            ]),

                        Tabs\Tab::make('通知配置')
                            ->schema([
                                KeyValue::make('notification_config')
                                    ->label('通知配置')
                                    ->keyLabel('配置项')
                                    ->valueLabel('值')
                                    ->helperText('任务执行结果通知配置')
                            ])
                    ])
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('任务名称')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('type')
                    ->label('类型')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'browser' => 'primary',
                        'api' => 'success',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'browser' => '浏览器',
                        'api' => 'API',
                        default => $state,
                    }),

                Tables\Columns\TextColumn::make('status')
                    ->label('状态')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'enabled' => 'success',
                        'disabled' => 'danger',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'enabled' => '启用',
                        'disabled' => '禁用',
                        default => $state,
                    }),

                Tables\Columns\TextColumn::make('cron_expression')
                    ->label('Cron表达式')
                    ->fontFamily('mono'),

                Tables\Columns\TextColumn::make('domain')
                    ->label('域名')
                    ->placeholder('未设置'),

                Tables\Columns\IconColumn::make('is_exclusive')
                    ->label('独占')
                    ->boolean(),

                Tables\Columns\IconColumn::make('debug_mode')
                    ->label('调试')
                    ->boolean()
                    ->trueIcon('heroicon-o-eye')
                    ->falseIcon('heroicon-o-eye-slash')
                    ->trueColor('warning')
                    ->falseColor('success'),

                Tables\Columns\TextColumn::make('next_run_time')
                    ->label('下次运行时间')
                    ->getStateUsing(function ($record) {
                        $taskService = app(TaskService::class);
                        $nextRun = $taskService->getNextRunTime($record);
                        return $nextRun ? $nextRun->format('Y-m-d H:i:s') : '未启用';
                    })
                    ->placeholder('未启用'),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('创建时间')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('type')
                    ->label('任务类型')
                    ->options([
                        'browser' => '浏览器任务',
                        'api' => 'API任务'
                    ]),

                Tables\Filters\SelectFilter::make('status')
                    ->label('状态')
                    ->options([
                        'enabled' => '启用',
                        'disabled' => '禁用'
                    ]),

                Tables\Filters\TernaryFilter::make('is_exclusive')
                    ->label('独占模式')
            ])
            ->actions([
                Tables\Actions\Action::make('toggle_status')
                    ->label(fn ($record) => $record->status === 'enabled' ? '禁用' : '启用')
                    ->icon(fn ($record) => $record->status === 'enabled' ? 'heroicon-o-pause' : 'heroicon-o-play')
                    ->color(fn ($record) => $record->status === 'enabled' ? 'warning' : 'success')
                    ->action(function ($record) {
                        $taskService = app(TaskService::class);
                        $taskService->toggleTaskStatus($record->id);
                    })
                    ->requiresConfirmation(),

                Tables\Actions\Action::make('execute')
                    ->label('执行')
                    ->icon('heroicon-o-play-circle')
                    ->color('primary')
                    ->action(function ($record) {
                        try {
                            $taskService = app(TaskService::class);
                            $execution = $taskService->executeTask($record->id);

                            \Filament\Notifications\Notification::make()
                                ->title('任务已开始执行')
                                ->body("执行ID: {$execution->id}")
                                ->success()
                                ->send();

                        } catch (\Exception $e) {
                            \Filament\Notifications\Notification::make()
                                ->title('执行失败')
                                ->body($e->getMessage())
                                ->danger()
                                ->send();
                        }
                    })
                    ->requiresConfirmation()
                    ->modalHeading('执行任务')
                    ->modalDescription('确定要立即执行此任务吗？')
                    ->visible(fn ($record) => !static::hasRunningExecution($record)),

                Tables\Actions\Action::make('stop')
                    ->label('停止')
                    ->icon('heroicon-o-stop-circle')
                    ->color('danger')
                    ->action(function ($record) {
                        try {
                            $taskService = app(TaskService::class);
                            $taskService->stopTask($record->id);

                            \Filament\Notifications\Notification::make()
                                ->title('任务已停止')
                                ->body('正在运行的任务已被停止')
                                ->success()
                                ->send();

                        } catch (\Exception $e) {
                            \Filament\Notifications\Notification::make()
                                ->title('停止失败')
                                ->body($e->getMessage())
                                ->danger()
                                ->send();
                        }
                    })
                    ->requiresConfirmation()
                    ->modalHeading('停止任务')
                    ->modalDescription('确定要停止正在运行的任务吗？')
                    ->visible(fn ($record) => static::hasRunningExecution($record)),

                Tables\Actions\Action::make('logs')
                    ->label('日志')
                    ->icon('heroicon-o-document-text')
                    ->color('info')
                    ->openUrlInNewTab()
                    ->url(fn ($record) => "/logs/task/{$record->id}"),

                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),

                    Tables\Actions\BulkAction::make('enable')
                        ->label('批量启用')
                        ->icon('heroicon-o-play')
                        ->color('success')
                        ->action(function ($records) {
                            $records->each(function ($record) {
                                $record->update(['status' => 'enabled']);
                            });
                        })
                        ->requiresConfirmation(),

                    Tables\Actions\BulkAction::make('disable')
                        ->label('批量禁用')
                        ->icon('heroicon-o-pause')
                        ->color('warning')
                        ->action(function ($records) {
                            $records->each(function ($record) {
                                $record->update(['status' => 'disabled']);
                            });
                        })
                        ->requiresConfirmation(),
                ]),
            ])
            ->poll('5s'); // 每5秒自动刷新表格
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTasks::route('/'),
            'create' => Pages\CreateTask::route('/create'),
            'edit' => Pages\EditTask::route('/{record}/edit'),
            'logs' => Pages\ViewTaskLogs::route('/{record}/logs'),
        ];
    }

    /**
     * 检查任务是否有正在运行的执行记录
     */
    protected static function hasRunningExecution($record): bool
    {
        return \App\Models\TaskExecution::where('task_id', $record->id)
            ->where('status', 'running')
            ->whereNull('end_time')
            ->exists();
    }
}

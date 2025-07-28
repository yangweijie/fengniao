<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TaskLogResource\Pages;
use App\Filament\Resources\TaskLogResource\RelationManagers;
use App\Models\TaskLog;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class TaskLogResource extends Resource
{
    protected static ?string $model = TaskLog::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static ?string $navigationLabel = '任务日志';

    protected static ?string $navigationGroup = '监控管理';

    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('execution_id')
                    ->relationship('execution', 'id')
                    ->required(),
                Forms\Components\TextInput::make('level')
                    ->required(),
                Forms\Components\Textarea::make('message')
                    ->required()
                    ->columnSpanFull(),
                Forms\Components\Textarea::make('context')
                    ->columnSpanFull(),
                Forms\Components\TextInput::make('screenshot_path'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('execution.task.name')
                    ->label('任务名称')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('execution.id')
                    ->label('执行ID')
                    ->numeric()
                    ->sortable(),

                Tables\Columns\TextColumn::make('level')
                    ->label('级别')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'info' => 'success',
                        'warning' => 'warning',
                        'error', 'critical' => 'danger',
                        'debug' => 'gray',
                        default => 'gray',
                    })
                    ->searchable(),

                Tables\Columns\TextColumn::make('message')
                    ->label('消息')
                    ->limit(100)
                    ->searchable(),

                Tables\Columns\IconColumn::make('screenshot_path')
                    ->label('截图')
                    ->boolean()
                    ->getStateUsing(fn ($record) => !empty($record->screenshot_path))
                    ->trueIcon('heroicon-o-camera')
                    ->falseIcon('heroicon-o-minus'),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('创建时间')
                    ->dateTime()
                    ->sortable()
                    ->since(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('level')
                    ->label('日志级别')
                    ->options([
                        'debug' => 'Debug',
                        'info' => 'Info',
                        'warning' => 'Warning',
                        'error' => 'Error',
                        'critical' => 'Critical'
                    ]),

                Tables\Filters\Filter::make('has_screenshot')
                    ->label('有截图')
                    ->query(fn ($query) => $query->whereNotNull('screenshot_path')),

                Tables\Filters\Filter::make('created_at')
                    ->label('时间范围')
                    ->form([
                        Forms\Components\DatePicker::make('created_from')
                            ->label('开始时间'),
                        Forms\Components\DatePicker::make('created_until')
                            ->label('结束时间'),
                    ])
                    ->query(function ($query, array $data) {
                        return $query
                            ->when($data['created_from'], fn ($q, $date) => $q->whereDate('created_at', '>=', $date))
                            ->when($data['created_until'], fn ($q, $date) => $q->whereDate('created_at', '<=', $date));
                    }),
            ])
            ->actions([
                Tables\Actions\Action::make('view_screenshot')
                    ->label('查看截图')
                    ->icon('heroicon-o-camera')
                    ->color('info')
                    ->visible(fn ($record) => !empty($record->screenshot_path))
                    ->url(fn ($record) => route('screenshot.view', $record->screenshot_path))
                    ->openUrlInNewTab(),

                Tables\Actions\ViewAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),

                    Tables\Actions\BulkAction::make('export')
                        ->label('导出日志')
                        ->icon('heroicon-o-arrow-down-tray')
                        ->action(function ($records) {
                            $logManager = app(\App\Services\LogManager::class);
                            $criteria = ['execution_ids' => $records->pluck('execution_id')->toArray()];
                            $path = $logManager->exportLogs($criteria, 'json');

                            return response()->download($path);
                        }),
                ]),
            ])
            ->defaultSort('created_at', 'desc')
            ->poll('5s'); // 每5秒自动刷新
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
            'index' => Pages\ListTaskLogs::route('/'),
            'create' => Pages\CreateTaskLog::route('/create'),
            'edit' => Pages\EditTaskLog::route('/{record}/edit'),
        ];
    }
}

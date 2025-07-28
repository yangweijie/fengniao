<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CookieResource\Pages;
use App\Models\Cookie;
use App\Services\CookieManager;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\DateTimePicker;
use Illuminate\Database\Eloquent\Builder;

class CookieResource extends Resource
{
    protected static ?string $model = Cookie::class;

    protected static ?string $navigationIcon = 'heroicon-o-key';

    protected static ?string $navigationLabel = 'Cookie管理';

    protected static ?string $navigationGroup = '系统管理';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('domain')
                    ->label('域名')
                    ->required()
                    ->maxLength(255)
                    ->placeholder('example.com'),

                TextInput::make('account')
                    ->label('账号')
                    ->maxLength(255)
                    ->placeholder('可选，用于区分同域名下的不同账号'),

                Textarea::make('cookie_data')
                    ->label('Cookie数据')
                    ->rows(5)
                    ->disabled()
                    ->helperText('Cookie数据已加密存储，无法直接编辑'),

                DateTimePicker::make('expires_at')
                    ->label('过期时间')
                    ->nullable(),

                DateTimePicker::make('last_used_at')
                    ->label('最后使用时间')
                    ->disabled(),

                Toggle::make('is_valid')
                    ->label('是否有效')
                    ->default(true)
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('domain')
                    ->label('域名')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('account')
                    ->label('账号')
                    ->placeholder('默认账号')
                    ->searchable(),

                Tables\Columns\TextColumn::make('expires_at')
                    ->label('过期时间')
                    ->dateTime()
                    ->sortable()
                    ->color(fn ($record) => $record->isExpired() ? 'danger' : 'success'),

                Tables\Columns\TextColumn::make('last_used_at')
                    ->label('最后使用')
                    ->dateTime()
                    ->sortable(),

                Tables\Columns\IconColumn::make('is_valid')
                    ->label('有效')
                    ->boolean(),

                Tables\Columns\TextColumn::make('status')
                    ->label('状态')
                    ->badge()
                    ->getStateUsing(function ($record) {
                        if (!$record->is_valid) return '无效';
                        if ($record->isExpired()) return '已过期';
                        if ($record->willExpireSoon()) return '即将过期';
                        return '正常';
                    })
                    ->color(fn (string $state): string => match ($state) {
                        '正常' => 'success',
                        '即将过期' => 'warning',
                        '已过期' => 'danger',
                        '无效' => 'gray',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('创建时间')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('is_valid')
                    ->label('状态')
                    ->options([
                        1 => '有效',
                        0 => '无效'
                    ]),

                Tables\Filters\Filter::make('expired')
                    ->label('已过期')
                    ->query(fn (Builder $query): Builder => $query->where('expires_at', '<', now())),

                Tables\Filters\Filter::make('expiring_soon')
                    ->label('即将过期')
                    ->query(fn (Builder $query): Builder => $query
                        ->where('expires_at', '>', now())
                        ->where('expires_at', '<', now()->addDays(1))
                    ),
            ])
            ->actions([
                Tables\Actions\Action::make('refresh')
                    ->label('刷新')
                    ->icon('heroicon-o-arrow-path')
                    ->color('warning')
                    ->action(function ($record) {
                        $cookieManager = app(CookieManager::class);
                        if ($cookieManager->refreshCookies($record->domain, $record->account)) {
                            \Filament\Notifications\Notification::make()
                                ->title('Cookie刷新成功')
                                ->success()
                                ->send();
                        } else {
                            \Filament\Notifications\Notification::make()
                                ->title('Cookie刷新失败')
                                ->danger()
                                ->send();
                        }
                    })
                    ->requiresConfirmation(),

                Tables\Actions\Action::make('test')
                    ->label('测试')
                    ->icon('heroicon-o-beaker')
                    ->color('info')
                    ->action(function ($record) {
                        $cookieManager = app(CookieManager::class);
                        $isValid = $cookieManager->isCookieValid($record->domain, $record->account);

                        \Filament\Notifications\Notification::make()
                            ->title($isValid ? 'Cookie有效' : 'Cookie无效')
                            ->color($isValid ? 'success' : 'danger')
                            ->send();
                    }),

                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),

                    Tables\Actions\BulkAction::make('clean_expired')
                        ->label('清理过期')
                        ->icon('heroicon-o-trash')
                        ->color('danger')
                        ->action(function ($records) {
                            $cookieManager = app(CookieManager::class);
                            $cleaned = $cookieManager->cleanExpiredCookies();

                            \Filament\Notifications\Notification::make()
                                ->title("已清理 {$cleaned} 个过期Cookie")
                                ->success()
                                ->send();
                        })
                        ->requiresConfirmation(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
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
            'index' => Pages\ListCookies::route('/'),
            'create' => Pages\CreateCookie::route('/create'),
            'edit' => Pages\EditCookie::route('/{record}/edit'),
        ];
    }
}

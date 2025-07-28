<?php

namespace App\Filament\Resources\TaskLogResource\Pages;

use App\Filament\Resources\TaskLogResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListTaskLogs extends ListRecords
{
    protected static string $resource = TaskLogResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}

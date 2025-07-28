<?php

namespace App\Filament\Resources\TaskLogResource\Pages;

use App\Filament\Resources\TaskLogResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateTaskLog extends CreateRecord
{
    protected static string $resource = TaskLogResource::class;
}

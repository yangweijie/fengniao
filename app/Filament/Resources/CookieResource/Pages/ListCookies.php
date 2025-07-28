<?php

namespace App\Filament\Resources\CookieResource\Pages;

use App\Filament\Resources\CookieResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListCookies extends ListRecords
{
    protected static string $resource = CookieResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}

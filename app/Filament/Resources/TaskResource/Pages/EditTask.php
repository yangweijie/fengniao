<?php

namespace App\Filament\Resources\TaskResource\Pages;

use App\Filament\Resources\TaskResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Filament\Support\Facades\FilamentAsset;
use Filament\Support\Assets\Js;
use Filament\Support\Assets\Css;

class EditTask extends EditRecord
{
    protected static string $resource = TaskResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    public function mount(int | string $record): void
    {
        parent::mount($record);

        // 注册Dusk语法提示JavaScript和自定义CSS
        FilamentAsset::register([
            Js::make('dusk-monaco-snippets', asset('js/dusk-monaco-snippets.js'))
                ->loadedOnRequest(),
            Css::make('monaco-editor-custom', asset('css/monaco-editor-custom.css'))
                ->loadedOnRequest(),
        ]);
    }

    protected function getViewData(): array
    {
        return array_merge(parent::getViewData(), [
            'duskSnippetsScript' => asset('js/dusk-monaco-snippets.js'),
        ]);
    }


}

<?php

namespace App\Filament\Resources\TimeSessions\Pages;

use App\Filament\Resources\TimeSessions\TimeSessionResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListTimeSessions extends ListRecords
{
    protected static string $resource = TimeSessionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}

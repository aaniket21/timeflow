<?php

namespace App\Filament\Resources\TimeSessions\Pages;

use App\Filament\Resources\TimeSessions\TimeSessionResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditTimeSession extends EditRecord
{
    protected static string $resource = TimeSessionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}

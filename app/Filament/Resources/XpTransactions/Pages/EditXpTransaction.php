<?php

namespace App\Filament\Resources\XpTransactions\Pages;

use App\Filament\Resources\XpTransactions\XpTransactionResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditXpTransaction extends EditRecord
{
    protected static string $resource = XpTransactionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}

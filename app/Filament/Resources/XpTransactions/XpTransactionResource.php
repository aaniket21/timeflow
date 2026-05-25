<?php

namespace App\Filament\Resources\XpTransactions;

use App\Filament\Resources\XpTransactions\Pages\CreateXpTransaction;
use App\Filament\Resources\XpTransactions\Pages\EditXpTransaction;
use App\Filament\Resources\XpTransactions\Pages\ListXpTransactions;
use App\Filament\Resources\XpTransactions\Schemas\XpTransactionForm;
use App\Filament\Resources\XpTransactions\Tables\XpTransactionsTable;
use App\Models\XpTransaction;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class XpTransactionResource extends Resource
{
    protected static ?string $model = XpTransaction::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    public static function form(Schema $schema): Schema
    {
        return XpTransactionForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return XpTransactionsTable::configure($table);
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
            'index' => ListXpTransactions::route('/'),
            'create' => CreateXpTransaction::route('/create'),
            'edit' => EditXpTransaction::route('/{record}/edit'),
        ];
    }
}

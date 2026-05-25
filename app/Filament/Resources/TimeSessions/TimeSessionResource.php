<?php

namespace App\Filament\Resources\TimeSessions;

use App\Filament\Resources\TimeSessions\Pages\CreateTimeSession;
use App\Filament\Resources\TimeSessions\Pages\EditTimeSession;
use App\Filament\Resources\TimeSessions\Pages\ListTimeSessions;
use App\Filament\Resources\TimeSessions\Schemas\TimeSessionForm;
use App\Filament\Resources\TimeSessions\Tables\TimeSessionsTable;
use App\Models\TimeSession;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class TimeSessionResource extends Resource
{
    protected static ?string $model = TimeSession::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    public static function form(Schema $schema): Schema
    {
        return TimeSessionForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return TimeSessionsTable::configure($table);
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
            'index' => ListTimeSessions::route('/'),
            'create' => CreateTimeSession::route('/create'),
            'edit' => EditTimeSession::route('/{record}/edit'),
        ];
    }
}

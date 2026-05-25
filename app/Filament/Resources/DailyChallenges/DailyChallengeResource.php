<?php

namespace App\Filament\Resources\DailyChallenges;

use App\Filament\Resources\DailyChallenges\Pages\CreateDailyChallenge;
use App\Filament\Resources\DailyChallenges\Pages\EditDailyChallenge;
use App\Filament\Resources\DailyChallenges\Pages\ListDailyChallenges;
use App\Filament\Resources\DailyChallenges\Schemas\DailyChallengeForm;
use App\Filament\Resources\DailyChallenges\Tables\DailyChallengesTable;
use App\Models\DailyChallenge;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class DailyChallengeResource extends Resource
{
    protected static ?string $model = DailyChallenge::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    public static function form(Schema $schema): Schema
    {
        return DailyChallengeForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return DailyChallengesTable::configure($table);
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
            'index' => ListDailyChallenges::route('/'),
            'create' => CreateDailyChallenge::route('/create'),
            'edit' => EditDailyChallenge::route('/{record}/edit'),
        ];
    }
}

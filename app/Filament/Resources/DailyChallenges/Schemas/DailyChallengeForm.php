<?php

namespace App\Filament\Resources\DailyChallenges\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class DailyChallengeForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('slug')
                    ->required(),
                TextInput::make('title')
                    ->required(),
                TextInput::make('description')
                    ->required(),
                Select::make('difficulty')
                    ->options(['easy' => 'Easy', 'medium' => 'Medium', 'hard' => 'Hard'])
                    ->default('medium')
                    ->required(),
                TextInput::make('xp_reward')
                    ->required()
                    ->numeric(),
                TextInput::make('condition_type')
                    ->required(),
                TextInput::make('condition_value')
                    ->required()
                    ->numeric()
                    ->default(0),
            ]);
    }
}

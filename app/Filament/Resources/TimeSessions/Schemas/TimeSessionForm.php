<?php

namespace App\Filament\Resources\TimeSessions\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class TimeSessionForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('user_id')
                    ->relationship('user', 'name')
                    ->required(),
                Select::make('project_id')
                    ->relationship('project', 'name'),
                TextInput::make('label'),
                Select::make('label_type')
                    ->options([
            'focus_mode' => 'Focus mode',
            'project' => 'Project',
            'activity' => 'Activity',
            'other' => 'Other',
        ])
                    ->default('other')
                    ->required(),
                Textarea::make('notes')
                    ->columnSpanFull(),
                DateTimePicker::make('started_at')
                    ->required(),
                DateTimePicker::make('ended_at'),
                TextInput::make('duration_seconds')
                    ->numeric(),
                TextInput::make('xp_earned')
                    ->required()
                    ->numeric()
                    ->default(0),
                Toggle::make('is_pomodoro')
                    ->required(),
            ]);
    }
}

<?php

namespace App\Filament\Resources\Reports\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class ReportForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('user_id')
                    ->relationship('user', 'name')
                    ->required(),
                TextInput::make('title')
                    ->required(),
                DatePicker::make('date_from')
                    ->required(),
                DatePicker::make('date_to')
                    ->required(),
                TextInput::make('project_ids'),
                TextInput::make('file_path'),
            ]);
    }
}

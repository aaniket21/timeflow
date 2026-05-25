<?php

namespace App\Filament\Resources\Users\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class UserForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->required(),
                TextInput::make('email')
                    ->label('Email address')
                    ->email()
                    ->required(),
                DateTimePicker::make('email_verified_at'),
                TextInput::make('password')
                    ->password()
                    ->required(),
                Textarea::make('two_factor_secret')
                    ->columnSpanFull(),
                Textarea::make('two_factor_recovery_codes')
                    ->columnSpanFull(),
                DateTimePicker::make('two_factor_confirmed_at'),
                TextInput::make('level')
                    ->required()
                    ->numeric()
                    ->default(1),
                TextInput::make('xp_total')
                    ->required()
                    ->numeric()
                    ->default(0),
                TextInput::make('streak_current')
                    ->required()
                    ->numeric()
                    ->default(0),
                TextInput::make('streak_longest')
                    ->required()
                    ->numeric()
                    ->default(0),
                TextInput::make('streak_shield_count')
                    ->required()
                    ->numeric()
                    ->default(0),
                DatePicker::make('last_active_date'),
                TextInput::make('timezone')
                    ->required()
                    ->default('UTC'),
                TextInput::make('daily_goal_hours')
                    ->required()
                    ->numeric()
                    ->default(6.0),
                TextInput::make('theme')
                    ->required()
                    ->default('dark'),
                TextInput::make('locale')
                    ->required()
                    ->default('en'),
                TextInput::make('pomodoro_work_min')
                    ->required()
                    ->numeric()
                    ->default(25),
                TextInput::make('pomodoro_break_min')
                    ->required()
                    ->numeric()
                    ->default(5),
                Toggle::make('notifications_enabled')
                    ->required(),
                Toggle::make('email_digest_enabled')
                    ->required(),
                Toggle::make('leaderboard_opt_in')
                    ->required(),
                TextInput::make('leaderboard_alias'),
                Toggle::make('plan_auto_rollover')
                    ->required(),
                TextInput::make('avatar_url')
                    ->url(),
                Toggle::make('is_admin')
                    ->required(),
            ]);
    }
}

<?php

namespace App\Filament\Resources\Users\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;

class UsersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->searchable(),
                TextColumn::make('email')
                    ->label('Email address')
                    ->searchable(),
                TextColumn::make('email_verified_at')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('two_factor_confirmed_at')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('level')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('xp_total')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('streak_current')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('streak_longest')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('streak_shield_count')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('last_active_date')
                    ->date()
                    ->sortable(),
                TextColumn::make('timezone')
                    ->searchable(),
                TextColumn::make('daily_goal_hours')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('theme')
                    ->searchable(),
                TextColumn::make('locale')
                    ->searchable(),
                TextColumn::make('pomodoro_work_min')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('pomodoro_break_min')
                    ->numeric()
                    ->sortable(),
                IconColumn::make('notifications_enabled')
                    ->boolean(),
                IconColumn::make('email_digest_enabled')
                    ->boolean(),
                IconColumn::make('leaderboard_opt_in')
                    ->boolean(),
                TextColumn::make('leaderboard_alias')
                    ->searchable(),
                IconColumn::make('plan_auto_rollover')
                    ->boolean(),
                TextColumn::make('avatar_url')
                    ->searchable(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('deleted_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                IconColumn::make('is_admin')
                    ->boolean(),
            ])
            ->filters([
                TrashedFilter::make(),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    ForceDeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                ]),
            ]);
    }
}

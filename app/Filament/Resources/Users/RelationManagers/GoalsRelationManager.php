<?php

namespace App\Filament\Resources\Users\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class GoalsRelationManager extends RelationManager
{
    protected static string $relationship = 'goals';

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('title'),
                TextColumn::make('type')
                    ->badge(),
                TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'not_started' => 'gray',
                        'in_progress' => 'info',
                        'completed' => 'success',
                        'paused' => 'warning',
                        'abandoned' => 'danger',
                        default => 'gray',
                    }),
                TextColumn::make('created_at')
                    ->since()
                    ->dateTimeTooltip(),
            ]);
    }
}

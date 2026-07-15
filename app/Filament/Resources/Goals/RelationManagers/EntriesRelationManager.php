<?php

namespace App\Filament\Resources\Goals\RelationManagers;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class EntriesRelationManager extends RelationManager
{
    protected static string $relationship = 'entries';

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('value')
            ->defaultSort('entry_date', 'desc')
            ->columns([
                TextColumn::make('entry_date')
                    ->date()
                    ->sortable(),
                TextColumn::make('value'),
                TextColumn::make('previous_value')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('increment_value')
                    ->label('Increment')
                    ->toggleable(),
                TextColumn::make('note')
                    ->limit(50)
                    ->placeholder('-')
                    ->toggleable(),
            ])
            ->filters([
                //
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}

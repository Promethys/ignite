<?php

namespace App\Filament\Widgets;

use App\Models\GoalEntry;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;

class RecentActivity extends TableWidget
{
    protected static ?int $sort = 3;

    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->query(fn (): Builder => GoalEntry::query()->latest()->take(15))
            ->paginated(false)
            ->defaultSort('created_at', direction: 'desc')
            ->columns([
                TextColumn::make('goal.user.name'),
                TextColumn::make('goal.title'),
                TextColumn::make('value')
                    ->numeric()
                    ->badge(),
                TextColumn::make('entry_date')
                    ->date(),
                TextColumn::make('created_at')
                    ->since()
                    ->dateTimeTooltip(),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                //
            ])
            ->recordActions([
                //
            ]);
    }
}

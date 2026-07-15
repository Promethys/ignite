<?php

namespace App\Filament\Resources\Goals\Tables;

use App\Models\Goal;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\Indicator;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class GoalsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('title')
                    ->searchable()
                    ->sortable()
                    ->limit(40),
                TextColumn::make('user.name')
                    ->label('Owner')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('category.name')
                    ->label('Category')
                    ->badge()
                    ->placeholder('-')
                    ->color('gray'),
                TextColumn::make('type')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'simple' => 'gray',
                        'quantifiable' => 'info',
                        'recurring' => 'warning',
                        'multi_step' => 'success',
                        default => 'gray',
                    }),
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
                TextColumn::make('polarity')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'positive' => 'success',
                        'negative' => 'danger',
                        default => 'gray',
                    })
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('progress_percentage')
                    ->label('Progress')
                    ->state(fn (Goal $record): ?string => $record->progress_percentage === null
                        ? null
                        : round($record->progress_percentage).'%')
                    ->placeholder('-'),
                TextColumn::make('current_value')
                    ->label('Current')
                    ->formatStateUsing(fn (Goal $record): string => $record->current_value.($record->unit ? ' '.$record->unit : ''))
                    ->toggleable(),
                TextColumn::make('target_value')
                    ->label('Target')
                    ->formatStateUsing(fn (Goal $record): string => $record->target_value.($record->unit ? ' '.$record->unit : ''))
                    ->toggleable(),
                TextColumn::make('deadline')
                    ->date()
                    ->sortable()
                    ->color(fn (Goal $record): ?string => $record->is_overdue ? 'danger' : null),
                TextColumn::make('entries_count')
                    ->counts('entries')
                    ->label('Entries')
                    ->badge()
                    ->sortable(),
                TextColumn::make('created_at')
                    ->since()
                    ->dateTimeTooltip()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('type')
                    ->options([
                        'simple' => 'Simple',
                        'quantifiable' => 'Quantifiable',
                        'recurring' => 'Recurring',
                        'multi_step' => 'Multi-step',
                    ]),
                SelectFilter::make('status')
                    ->options([
                        'not_started' => 'Not started',
                        'in_progress' => 'In progress',
                        'completed' => 'Completed',
                        'paused' => 'Paused',
                        'abandoned' => 'Abandoned',
                    ]),
                SelectFilter::make('polarity')
                    ->options([
                        'positive' => 'Positive',
                        'negative' => 'Negative',
                    ]),
                SelectFilter::make('category')
                    ->relationship('category', 'name')
                    ->searchable()
                    ->preload(),
                SelectFilter::make('user')
                    ->relationship('user', 'name')
                    ->searchable()
                    ->preload(),
                Filter::make('overdue')
                    ->label('Overdue')
                    ->query(fn (Builder $query): Builder => $query
                        ->whereNotNull('deadline')
                        ->where('deadline', '<', now())
                        ->whereNotIn('status', ['completed', 'abandoned']))
                    ->indicateUsing(fn (): array => [
                        Indicator::make('Overdue'),
                    ]),
                Filter::make('no_entries')
                    ->label('No entries')
                    ->query(fn (Builder $query): Builder => $query->doesntHave('entries'))
                    ->indicateUsing(fn (): array => [
                        Indicator::make('No entries'),
                    ]),
            ])
            ->recordActions([
                ViewAction::make(),
                ActionGroup::make([
                    DeleteAction::make(),
                ]),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}

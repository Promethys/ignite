<?php

namespace App\Filament\Resources\Goals\Schemas;

use App\Models\Goal;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class GoalInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Goal')
                    ->schema([
                        TextEntry::make('title'),
                        TextEntry::make('description')
                            ->placeholder('-'),
                        TextEntry::make('user.name')
                            ->label('Owner'),
                        TextEntry::make('category.name')
                            ->label('Category')
                            ->badge()
                            ->placeholder('-'),
                        TextEntry::make('type')
                            ->badge(),
                        TextEntry::make('status')
                            ->badge()
                            ->color(fn (string $state): string => match ($state) {
                                'not_started' => 'gray',
                                'in_progress' => 'info',
                                'completed' => 'success',
                                'paused' => 'warning',
                                'abandoned' => 'danger',
                                default => 'gray',
                            }),
                        TextEntry::make('polarity')
                            ->badge(),
                        TextEntry::make('progress_percentage')
                            ->label('Progress')
                            ->state(fn (Goal $record): ?string => $record->progress_percentage === null
                                ? null
                                : round($record->progress_percentage).'%')
                            ->placeholder('-'),
                    ])
                    ->columns(2),
                Section::make('Values')
                    ->schema([
                        TextEntry::make('initial_value'),
                        TextEntry::make('current_value'),
                        TextEntry::make('target_value'),
                        TextEntry::make('unit')
                            ->placeholder('-'),
                        TextEntry::make('direction'),
                        TextEntry::make('priority'),
                    ])
                    ->columns(3),
                Section::make('Timeline')
                    ->schema([
                        TextEntry::make('start_date')
                            ->date()
                            ->placeholder('-'),
                        TextEntry::make('deadline')
                            ->date()
                            ->placeholder('-')
                            ->color(fn (Goal $record): ?string => $record->is_overdue ? 'danger' : null),
                        TextEntry::make('completed_at')
                            ->dateTime()
                            ->placeholder('-'),
                        TextEntry::make('created_at')
                            ->dateTime(),
                        TextEntry::make('updated_at')
                            ->dateTime(),
                    ])
                    ->columns(3),
            ]);
    }
}

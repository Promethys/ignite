<?php

namespace App\Filament\Resources\Goals;

use App\Filament\Resources\Goals\Pages\ListGoals;
use App\Filament\Resources\Goals\Pages\ViewGoal;
use App\Filament\Resources\Goals\RelationManagers\EntriesRelationManager;
use App\Filament\Resources\Goals\RelationManagers\MilestonesRelationManager;
use App\Filament\Resources\Goals\Schemas\GoalInfolist;
use App\Filament\Resources\Goals\Tables\GoalsTable;
use App\Models\Goal;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class GoalResource extends Resource
{
    protected static ?string $model = Goal::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedFlag;

    protected static ?string $recordTitleAttribute = 'title';

    public static function infolist(Schema $schema): Schema
    {
        return GoalInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return GoalsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            EntriesRelationManager::class,
            MilestonesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListGoals::route('/'),
            'view' => ViewGoal::route('/{record}'),
        ];
    }
}

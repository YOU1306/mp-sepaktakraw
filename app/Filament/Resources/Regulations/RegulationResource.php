<?php

namespace App\Filament\Resources\Regulations;

use App\Filament\Resources\Regulations\Pages\CreateRegulation;
use App\Filament\Resources\Regulations\Pages\EditRegulation;
use App\Filament\Resources\Regulations\Pages\ListRegulations;
use App\Filament\Resources\Regulations\Schemas\RegulationForm;
use App\Filament\Resources\Regulations\Tables\RegulationsTable;
use App\Models\Regulation;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

/**
 * Rules & Regulations documents (Laws of the Game, score sheets, etc.). Only
 * Admin / Super Admin manage this — district federations (Super User) have
 * no reason to edit national/state rule documents.
 */
class RegulationResource extends Resource
{
    protected static ?string $model = Regulation::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedScale;

    protected static ?string $navigationLabel = 'Rules & Regulations';

    public static function form(Schema $schema): Schema
    {
        return RegulationForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return RegulationsTable::configure($table);
    }

    public static function canAccess(): bool
    {
        return auth()->user()?->hasAnyRole(['admin', 'super-admin']) ?? false;
    }

    public static function canViewAny(): bool
    {
        return static::canAccess();
    }

    public static function canCreate(): bool
    {
        return static::canAccess();
    }

    public static function canEdit($record): bool
    {
        return static::canAccess();
    }

    public static function canDelete($record): bool
    {
        return static::canAccess();
    }

    public static function getPages(): array
    {
        return [
            'index' => ListRegulations::route('/'),
            'create' => CreateRegulation::route('/create'),
            'edit' => EditRegulation::route('/{record}/edit'),
        ];
    }
}

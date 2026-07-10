<?php

namespace App\Filament\Resources\IntakeOpenings;

use App\Filament\Resources\IntakeOpenings\Pages\CreateIntakeOpening;
use App\Filament\Resources\IntakeOpenings\Pages\EditIntakeOpening;
use App\Filament\Resources\IntakeOpenings\Pages\ListIntakeOpenings;
use App\Filament\Resources\IntakeOpenings\Schemas\IntakeOpeningForm;
use App\Filament\Resources\IntakeOpenings\Tables\IntakeOpeningsTable;
use App\Models\IntakeOpening;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class IntakeOpeningResource extends Resource
{
    protected static ?string $model = IntakeOpening::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    public static function form(Schema $schema): Schema
    {
        return IntakeOpeningForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return IntakeOpeningsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListIntakeOpenings::route('/'),
            'create' => CreateIntakeOpening::route('/create'),
            'edit' => EditIntakeOpening::route('/{record}/edit'),
        ];
    }
}

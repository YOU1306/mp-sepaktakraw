<?php

namespace App\Filament\Resources\RegistrationApplications;

use App\Filament\Resources\RegistrationApplications\Pages\ListRegistrationApplications;
use App\Filament\Resources\RegistrationApplications\Tables\RegistrationApplicationsTable;
use App\Models\RegistrationApplication;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class RegistrationApplicationResource extends Resource
{
    protected static ?string $model = RegistrationApplication::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedInboxArrowDown;

    protected static ?string $navigationLabel = 'Registrations';

    protected static ?string $recordTitleAttribute = 'reference_no';

    public static function table(Table $table): Table
    {
        return RegistrationApplicationsTable::configure($table);
    }

    public static function canViewAny(): bool
    {
        return auth()->user()?->hasAnyRole(['admin', 'super-admin']) ?? false;
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function getPages(): array
    {
        return [
            'index' => ListRegistrationApplications::route('/'),
        ];
    }
}

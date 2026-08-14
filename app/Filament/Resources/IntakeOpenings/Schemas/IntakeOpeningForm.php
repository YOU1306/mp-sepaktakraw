<?php

namespace App\Filament\Resources\IntakeOpenings\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class IntakeOpeningForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('title')
                    ->required(),
                Textarea::make('description')
                    ->columnSpanFull(),
                Select::make('district_id')
                    ->relationship('district', 'name'),
                TextInput::make('fee_amount')
                    ->required()
                    ->numeric(),
                Textarea::make('form_schema')
                    ->columnSpanFull(),
                DateTimePicker::make('opens_at'),
                DateTimePicker::make('closes_at'),
                TextInput::make('status')
                    ->required()
                    ->default('draft'),
            ]);
    }
}

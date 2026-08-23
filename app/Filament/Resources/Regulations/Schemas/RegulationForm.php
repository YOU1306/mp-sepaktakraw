<?php

namespace App\Filament\Resources\Regulations\Schemas;

use App\Models\Regulation;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class RegulationForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('title')
                    ->required()
                    ->maxLength(255)
                    ->columnSpanFull(),
                Textarea::make('description')
                    ->helperText('Optional short note shown under the title on the public page.')
                    ->columnSpanFull(),
                FileUpload::make('path')
                    ->label('PDF file')
                    ->disk('local')
                    ->directory('regulations')
                    ->visibility('private')
                    ->acceptedFileTypes(['application/pdf'])
                    ->maxSize(20480)
                    ->required(fn (string $operation): bool => $operation === 'create')
                    ->helperText('PDF only, max 20MB. Uploading a new file while editing replaces the existing one.')
                    ->columnSpanFull(),
                TextInput::make('sort_order')
                    ->label('Display order')
                    ->numeric()
                    ->default(fn () => (Regulation::max('sort_order') ?? 0) + 1)
                    ->helperText('Lower numbers appear first on the public page.')
                    ->required(),
                Toggle::make('is_active')
                    ->label('Published')
                    ->default(true)
                    ->helperText('Only published rules are visible to the public.'),
            ]);
    }
}

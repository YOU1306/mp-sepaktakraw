<?php

namespace App\Filament\Resources\Contents\Schemas;

use App\Models\Content;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class ContentForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('type')
                    ->options([
                        Content::TYPE_NEWS => 'News',
                        Content::TYPE_NOTICE => 'Notice',
                        Content::TYPE_RESULT => 'Result',
                        Content::TYPE_EVENT => 'Event',
                        Content::TYPE_PAGE => 'Page',
                    ])
                    ->required()
                    ->native(false),
                TextInput::make('title')
                    ->required()
                    ->maxLength(255)
                    ->live(onBlur: true),
                TextInput::make('slug')
                    ->maxLength(255)
                    ->helperText('Leave blank to auto-generate from title'),
                RichEditor::make('body')
                    ->columnSpanFull(),
                Select::make('status')
                    ->options([
                        Content::STATUS_DRAFT => 'Draft',
                        Content::STATUS_PUBLISHED => 'Published',
                    ])
                    ->default(Content::STATUS_DRAFT)
                    ->required()
                    ->native(false),
                Select::make('district_id')
                    ->relationship('district', 'name')
                    ->searchable()
                    ->preload()
                    ->nullable(),
                DateTimePicker::make('published_at')
                    ->helperText('Set when publishing'),
            ]);
    }
}

<?php

namespace App\Filament\Resources\Regulations\Tables;

use App\Models\Regulation;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class RegulationsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('sort_order')
            ->reorderable('sort_order')
            ->columns([
                TextColumn::make('sort_order')->label('#')->sortable(),
                TextColumn::make('title')->searchable(),
                TextColumn::make('description')->limit(50)->toggleable(),
                TextColumn::make('sizeForHumans')->label('Size')->state(fn (Regulation $record) => $record->sizeForHumans() ?? '—'),
                IconColumn::make('is_active')->label('Published')->boolean(),
                TextColumn::make('updated_at')->dateTime('d M Y, H:i')->sortable()->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                Action::make('view')
                    ->label('View PDF')
                    ->icon('heroicon-o-eye')
                    ->url(fn (Regulation $record) => route('regulations.show', $record))
                    ->openUrlInNewTab(),
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}

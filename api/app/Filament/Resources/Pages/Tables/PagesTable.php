<?php

namespace App\Filament\Resources\Pages\Tables;

use App\Models\Page;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class PagesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('title')
                    ->label('Page Title')
                    ->weight('bold')
                    ->icon('heroicon-m-document-text')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('slug')
                    ->label('URL Path')
                    ->badge()
                    ->color('gray')
                    ->formatStateUsing(fn($state) => "/{$state}")
                    ->copyable(),

                IconColumn::make('is_published')
                    ->label('Published')
                    ->boolean(),

                TextColumn::make('updated_at')
                    ->label('Last Updated')
                    ->since()
                    ->sortable(),
            ])
            ->recordActions([
                Action::make('preview')
                    ->label('View live')
                    ->icon('heroicon-o-arrow-top-right-on-square')
                    ->color('gray')
                    ->url(fn(Page $record) => rtrim(config('services.storefront.url'), '/') . "/{$record->slug}")
                    ->openUrlInNewTab()
                    ->visible(fn(Page $record) => $record->is_published),
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([DeleteBulkAction::make()]),
            ]);
    }
}

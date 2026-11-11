<?php

namespace App\Filament\Resources\ContactMessages\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ContactMessagesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Sender')
                    ->weight('bold')
                    ->icon('heroicon-m-user')
                    ->searchable()
                    ->description(fn ($record) => $record->email ?: $record->phone),

                TextColumn::make('phone')
                    ->icon('heroicon-m-phone')
                    ->copyable()
                    ->searchable()
                    ->placeholder('—'),

                TextColumn::make('subject')
                    ->weight('medium')
                    ->searchable()
                    ->placeholder('General Inquiry')
                    ->description(fn ($record) => str($record->message)->limit(60)),

                IconColumn::make('is_read')
                    ->label('Read')
                    ->boolean(),

                TextColumn::make('created_at')
                    ->label('Received')
                    ->since()
                    ->sortable(),
            ])
            ->defaultSort('id', 'desc')
            ->recordActions([
                EditAction::make()->label('Open'),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}

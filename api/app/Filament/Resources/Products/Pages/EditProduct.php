<?php

namespace App\Filament\Resources\Products\Pages;

use App\Filament\Resources\Products\ProductResource;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditProduct extends EditRecord
{
    protected static string $resource = ProductResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('preview')
                ->label('View on storefront')
                ->icon('heroicon-o-arrow-top-right-on-square')
                ->color('gray')
                ->url(fn () => $this->storefrontUrl())
                ->openUrlInNewTab()
                ->visible(fn () => (bool) $this->record->is_active),

            DeleteAction::make(),
        ];
    }

    protected function getRedirectUrl(): ?string
    {
        // Stay on the page after saving so the shop owner can keep editing
        // and hit "View on storefront" to check the result.
        return null;
    }

    protected function getSavedNotification(): ?Notification
    {
        return Notification::make()
            ->success()
            ->title('Product saved')
            ->body($this->record->is_active
                ? 'The storefront has been refreshed.'
                : 'Saved as unpublished, so it will not appear on the storefront yet.')
            ->actions($this->record->is_active ? [
                Action::make('view')
                    ->label('View live')
                    ->url($this->storefrontUrl())
                    ->openUrlInNewTab(),
            ] : []);
    }

    protected function storefrontUrl(): string
    {
        return rtrim(config('services.storefront.url'), '/')."/products/{$this->record->slug}";
    }
}

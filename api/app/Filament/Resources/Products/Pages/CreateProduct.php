<?php

namespace App\Filament\Resources\Products\Pages;

use App\Filament\Resources\Products\ProductResource;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;

class CreateProduct extends CreateRecord
{
    protected static string $resource = ProductResource::class;

    protected function getRedirectUrl(): string
    {
        // Land on the edit page rather than back in the list, so the new
        // product and its "View on storefront" button are right there.
        return static::getResource()::getUrl('edit', ['record' => $this->record]);
    }

    protected function getCreatedNotification(): ?Notification
    {
        $url = rtrim(config('services.storefront.url'), '/')."/products/{$this->record->slug}";

        return Notification::make()
            ->success()
            ->title('Product created')
            ->body($this->record->is_active
                ? 'It is live on the storefront now.'
                : 'Saved as unpublished. Turn on "Published" to show it in the shop.')
            ->actions($this->record->is_active ? [
                Action::make('view')
                    ->label('View live')
                    ->url($url)
                    ->openUrlInNewTab(),
            ] : []);
    }
}

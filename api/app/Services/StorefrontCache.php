<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Tells the Next.js storefront to drop its cached copies of catalogue data.
 *
 * Next caches product responses so pages stay fast. Without this ping, an
 * admin who saves a product and immediately refreshes the shop sees the old
 * data and reasonably concludes the save failed.
 */
class StorefrontCache
{
    /**
     * @param  array<int, string>  $tags
     */
    public function flush(array $tags = ['products', 'categories']): bool
    {
        $url = config('services.storefront.url');
        $secret = config('services.storefront.revalidate_secret');

        if (blank($url) || blank($secret)) {
            return false;
        }

        try {
            $response = Http::timeout(3)
                ->withHeaders(['x-revalidate-secret' => $secret])
                ->post(rtrim($url, '/').'/api/revalidate', ['tags' => $tags]);

            return $response->successful();
        } catch (\Throwable $e) {
            // The storefront being down must never block an admin save.
            Log::warning('Storefront cache flush failed: '.$e->getMessage());

            return false;
        }
    }
}

<?php

namespace App\Providers;

use App\Models\Category;
use App\Models\Product;
use App\Observers\CatalogueObserver;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Keeps the storefront's cached pages in step with admin edits.
        Product::observe(CatalogueObserver::class);
        Category::observe(CatalogueObserver::class);
    }
}

<?php

namespace App\Observers;

use App\Models\Category;
use App\Models\Product;
use App\Services\StorefrontCache;
use Illuminate\Database\Eloquent\Model;

class CatalogueObserver
{
    public function __construct(protected StorefrontCache $cache) {}

    public function saved(Model $model): void
    {
        $this->flushFor($model);
    }

    public function deleted(Model $model): void
    {
        $this->flushFor($model);
    }

    protected function flushFor(Model $model): void
    {
        $tags = ['products', 'categories'];

        if ($model instanceof Product) {
            $tags[] = 'product-'.$model->getKey();
        }

        if ($model instanceof Category) {
            $tags = ['products', 'categories'];
        }

        $this->cache->flush($tags);
    }
}

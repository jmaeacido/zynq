<?php

namespace App\Domains\Products\Services;

use App\Domains\Products\Models\ProductCategory;
use Illuminate\Support\Facades\DB;

class ProductCategoryService
{
    public function create(array $data): ProductCategory
    {
        return DB::transaction(fn () => ProductCategory::create($data));
    }

    public function update(ProductCategory $category, array $data): ProductCategory
    {
        return DB::transaction(function () use ($category, $data) {
            $category->update($data);

            return $category->refresh();
        });
    }
}

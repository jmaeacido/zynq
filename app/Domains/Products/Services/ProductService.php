<?php

namespace App\Domains\Products\Services;

use App\Domains\Products\Models\Product;
use Illuminate\Support\Facades\DB;

class ProductService
{
    public function create(array $data): Product
    {
        return DB::transaction(fn () => Product::create($data));
    }

    public function update(Product $product, array $data): Product
    {
        return DB::transaction(function () use ($product, $data) {
            $product->update($data);

            return $product->refresh();
        });
    }

    public function archive(Product $product): Product
    {
        return DB::transaction(function () use ($product) {
            $product->update(['active' => false, 'archived_at' => now()]);

            return $product->refresh();
        });
    }
}

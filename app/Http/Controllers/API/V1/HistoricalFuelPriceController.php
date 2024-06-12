<?php

namespace App\Http\Controllers\API\V1;

use App\Http\Controllers\Controller;
use App\Models\Brand;
use App\Models\Product;
use App\Models\Province;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class HistoricalFuelPriceController extends Controller
{
    /**
     * Get the historical fuel prices for a specific province.
     *
     * This endpoint will return the historical prices for all products in a specific province.
     * The response will include the product name, brand name, and the prices for each product.
     *
     * @group Historical Fuel Pricing
     * @urlParam province required The ID of the province. Example: 1
     * @authenticated
     * @param Province $province
     * @return JsonResponse
     */
    public function byProvinceHistorical(Province $province): JsonResponse
    {
        $prices = $province->prices->map(function ($price) {
            return [
                'product_id' => $price->product->id,
                'product_name' => $price->product->name,
                'brand_name' => $price->product->brand->name,
                'price' => $price->price,
                'last_updated' => $price->created_at->format('U'),
            ];
        });

        return response()->json($prices);
    }

    /**
     * Get the historical fuel prices for a specific brand.
     *
     * This endpoint will return the historical prices for all products of a specific brand.
     * The response will include the product name, brand name, and the prices for each product.
     *
     * @group Historical Fuel Pricing
     * @urlParam brand required The ID of the brand. Example: 1
     * @authenticated
     * @param Brand $brand
     * @return JsonResponse
     */
    public function byBrandHistorical(Brand $brand): JsonResponse
    {
        $products = $brand->products;
        return $this->mapAndDisplayProducts($products);
    }

    /**
     * Get the historical fuel prices for a specific product.
     *
     * This endpoint will return the historical prices for a specific product.
     * The response will include the product name, brand name, and the prices for each province.
     *
     * @group Historical Fuel Pricing
     * @urlParam product required The ID of the product. Example: 1
     * @authenticated
     * @param Product $product
     * @return JsonResponse
     */
    public function byProductHistorical(Product $product): JsonResponse
    {
        return $this->mapAndDisplayProducts(collect([$product]));
    }

    private function mapAndDisplayProducts(Collection $products): JsonResponse
    {
        $data = $products->map(function ($product) {
            // Group prices by creation date and sort them
            $prices = $product->prices()->with('province')
                ->orderBy('created_at', 'desc')
                ->get()
                ->groupBy(function ($price) {
                    // Grouping by date only for simplification
                    return $price->created_at->format('Y-m-d');
                })
                ->map(function ($dayPrices) {
                    // Map each group to show prices details
                    return $dayPrices->map(function ($price) {
                        return [
                            'province' => $price->province->name,
                            'price' => $price->price,
                            'last_updated' => $price->created_at->format('U'),
                        ];
                    });
                });

            return [
                'product_id' => $product->id,
                'product_name' => $product->name,
                'brand_name' => $product->brand->name,
                'historical_prices' => $prices,
            ];
        });

        if ($products->count() == 1) {
            $data = $data->first();
        }

        return response()->json($data);
    }

}

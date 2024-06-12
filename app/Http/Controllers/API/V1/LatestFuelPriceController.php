<?php

namespace App\Http\Controllers\API\V1;

use App\Http\Controllers\Controller;
use App\Models\Brand;
use App\Models\Price;
use App\Models\Product;
use App\Models\Province;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class LatestFuelPriceController extends Controller
{
    /**
     * Display all products with their latest prices.
     *
     * This endpoint will return the latest prices for all products.
     * The response will include the product name, brand name, and the prices for each province.
     *
     * @group Latest Fuel Pricing
     * @authenticated
     * @return JsonResponse
     */
    public function latestPrices()
    {
        $products = Product::with('brand', 'prices.province')->get();
        return $this->mapAndDisplayProducts($products);
    }

    /**
     * Get the latest fuel prices for a specific province.
     *
     * This endpoint will return the latest prices for all products in a specific province.
     * The response will include the product name, brand name, and the prices for each product.
     *
     * @group Latest Fuel Pricing
     * @urlParam province required The ID of the province. Example: 1
     * @authenticated
     * @param Province $province
     * @return JsonResponse
     */
    public function byProvinceLatest(Province $province): JsonResponse
    {
        $prices = Price::with('product.brand')
            ->where('province_id', $province->id)
            ->get();

        $data = $prices->map(function (Price $price) {
            return [
                'product_id' => $price->product->id,
                'product_name' => $price->product->name,
                'brand_name' => $price->product->brand->name,
                'price' => $price->price,
            ];
        });

        return response()->json($data);
    }

    /**
     * Get the latest prices for all products of a specific brand.
     *
     * This endpoint will return the latest prices for all products of a specific brand.
     * The response will include the product name, brand name, and the prices for each province.
     *
     * @group Latest Fuel Pricing
     * @urlParam brand required The ID of the brand. Example: 1
     * @authenticated
     * @param Brand $brand
     * @return JsonResponse
     */
    public function byBrandLatest(Brand $brand): JsonResponse
    {
        $products = Product::with('prices.province')
            ->where('brand_id', $brand->id)
            ->get();

        return $this->mapAndDisplayProducts($products);
    }

    /**
     * Get the latest prices for a specific product.
     *
     * This endpoint will return the latest prices for a specific product.
     * The response will include the product name, brand name, and the prices for each province.
     *
     * @group Latest Fuel Pricing
     * @urlParam product required The ID of the product. Example: 2
     * @authenticated
     * @param Product $product
     * @return JsonResponse
     */
    public function byProductLatest(Product $product)
    {
        $product->load('prices.province');
        return $this->mapAndDisplayProducts(collect([$product]));
    }

    /**
     * Maps product data and displays the relevant product information including prices.
     *
     * @group Latest Fuel Pricing
     * @param Collection $products
     * @return JsonResponse
     */
    private function mapAndDisplayProducts(Collection $products): JsonResponse
    {
        $data = $products->map(function ($product) {

            $pricesQuery = $product->prices()->with('province');
            $pricesQuery = $pricesQuery->orderBy('created_at', 'desc')->limit(1);

            $prices = $pricesQuery->get()->map(function ($price) {
                return [
                    'province' => $price->province->name,
                    'price' => $price->price,
                    'last_updated' => $price->created_at->format('U'),
                ];
            });

            return [
                'product_id' => $product->id,
                'product_name' => $product->name,
                'brand_name' => $product->brand->name,
                'prices' => $prices,
            ];
        });

        if ($products->count() == 1) {
            $data = $data->first(); // Ensures the response is consistent in format
        }

        return response()->json($data);
    }

}

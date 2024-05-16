<?php

namespace App\Jobs\Scraper;

use App\Models\Brand;
use App\Models\Price;
use App\Models\Product;
use App\Models\Province;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Symfony\Component\DomCrawler\Crawler;

class VivoScraper implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct()
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $url = 'https://isibens.in/';
        $response = Http::get($url);
        $html = $response->body();

        $crawler = new Crawler($html);

        $brand = Brand::firstOrCreate(['name' => 'Vivo']);
        $province = Province::firstOrCreate(['name' => 'SPECIAL_JABODETABEK']);

        // Define the products and their positions in the table
        $products = [
            'Revvo90' => 1,
            'Revvo92' => 1,
            'Revvo95' => 1
        ];

        // Loop through the rows for RON 90, 92, 95
        $crawler->filter('table.table')->eq(0)->filter('tbody > tr')->each(function (Crawler $row) use ($products, $brand, $province) {
            $ron = $row->filter('th')->text(); // Get the RON number
            if (array_key_exists("Revvo$ron", $products)) {
                $productName = "Revvo$ron";
                $product = Product::firstOrCreate(
                    ['name' => $productName],
                    ['brand_id' => $brand->id]
                );

                $priceCellHtml = $row->filter('td')->eq($products[$productName])->html();
                $priceParts = explode('<br>', $priceCellHtml);
                $price = isset($priceParts[0]) ? preg_replace('/\D/', '', $priceParts[0]) : '0';

                if ((int)$price > 0) { // Check if price is valid
                    Price::create([
                        'product_id' => $product->id,
                        'province_id' => $province->id,
                        'price' => (int)$price
                    ]);
                }
            }
        });
    }


}

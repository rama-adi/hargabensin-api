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

class BPScraper implements ShouldQueue
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
        $url = 'https://www.bp.com/id_id/indonesia/home/produk-dan-layanan/spbu/harga.html';
        $response = Http::get($url);
        $html = $response->body();

        $crawler = new Crawler($html);

        $brand = Brand::firstOrCreate(['name' => 'BP']);

        // Extract the headers for provinces
        $provinces = $crawler->filter('table > tbody > tr')->first()->filter('th')->each(function (Crawler $th, $index) {
            if ($index == 0) { // Skip the product name column
                return null;
            }
            return trim($th->text());
        });

        $crawler->filter('table > tbody > tr')->slice(1)->each(function (Crawler $row) use ($brand, $provinces) {
            $productName = $row->filter('th')->text();
            $productName = str_replace('BP ', '', $productName); // Remove 'BP ' prefix
            $productName = ucwords(strtolower($productName));
            $product = Product::firstOrCreate(['name' => $productName], ['brand_id' => $brand->id]);

            $row->filter('td')->each(function (Crawler $td, $index) use ($product, $provinces) {
                $price = $td->text();
                // Adjusted clean-up regex to replace non-breaking spaces and other whitespace correctly
                $price = preg_replace('/\D/', '', $price);

                if (!empty($price)) {
                    $provinceName = $provinces[$index + 1]; // Offset by one because the first header is for product names
                    $provinceName = match ($provinceName) {
                        'JABODETABEK' => 'SPECIAL_JABODETABEK',
                        'JAWA TIMUR' => 'Jawa Timur',
                        default => 'UNKNOWN',
                    };

                    $province = Province::firstOrCreate(['name' => $provinceName]);

                    if ((int)$price === 0) {
                        return;
                    }

                    Price::create([
                        'product_id' => $product->id,
                        'province_id' => $province->id,
                        'price' => (int)$price,
                    ]);
                }
            });
        });
    }


}

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

class ShellScraper implements ShouldQueue
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
        $url = 'https://www.shell.co.id/in_id/pengendara-bermotor/bahan-bakar-shell/how-shell-price-fuel.html';
        $response = Http::get($url);
        $html = $response->body();

        $crawler = new Crawler($html);

        $brand = Brand::firstOrCreate(['name' => 'Shell']);

        $crawler->filter('table > tbody > tr')->each(function (Crawler $row) use ($brand) {
            if (!$row->filter('td')->count()) {
                return; // Skip the header row
            }

            $provinceName = $row->filter('td')->first()->text();
            $province = $this->handleProvince(strtoupper(trim($provinceName)));

            $productNames = ['Super', 'V-Power', 'V-Power Diesel', 'Diesel Extra', 'V-power Nitro+'];
            $prices = $row->filter('td')->slice(1); // Skip the first column (province)

            $prices->each(function (Crawler $priceNode, $index) use ($brand, $province, $productNames) {
                if ($index >= count($productNames)) {
                    return; // Prevent index out of bounds if there are more cells than expected
                }

                $productName = $productNames[$index];
                $product = Product::firstOrCreate(
                    ['name' => $productName],
                    ['brand_id' => $brand->id]
                );

                if ($product) {
                    $price = $priceNode->text();
                    $price = trim($price); // Trim any surrounding whitespace

                    if (!empty($price) && $price !== 'N/A') {
                        $priceValue = str_replace(['IDR', ',', ' '], '', $price); // Remove IDR and commas, and any extra spaces

                        // if price is 0, skip it
                        if ((int)$priceValue === 0) {
                            return;
                        }

                        Price::create([
                            'product_id' => $product->id,
                            'province_id' => $province->id,
                            'price' => (int)$priceValue,
                        ]);
                    }
                }
            });
        });
    }

    private function handleProvince(string $name): Province
    {
        return match ($name) {
            'JAKARTA' => Province::firstOrCreate(['name' => 'DKI Jakarta']),
            'BANTEN' => Province::firstOrCreate(['name' => 'Banten']),
            'JAWA BARAT' => Province::firstOrCreate(['name' => 'Jawa Barat']),
            'JAWA TIMUR' => Province::firstOrCreate(['name' => 'Jawa Timur']),
            'SUMATRA UTARA' => Province::firstOrCreate(['name' => 'Sumatera Utara']),
            default => Province::firstOrCreate(['name' => $name]), // handle unexpected provinces
        };
    }

}

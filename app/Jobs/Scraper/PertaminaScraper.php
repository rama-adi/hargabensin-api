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

class PertaminaScraper implements ShouldQueue
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
        $url = 'https://mypertamina.id/fuels-harga';
        $response = Http::get($url);
        $html = $response->body();

        $crawler = new Crawler($html);

        $brand = Brand::firstOrCreate(['name' => 'Pertamina']);

        $crawler->filter('.card')->each(function (Crawler $card) use ($brand) {
            $productName = $card->filter('img')->attr('src');
            $productName = $this->parseNameFromImage($productName);

            $product = Product::firstOrCreate(['name' => $productName], ['brand_id' => $brand->id]);

            $card->filter('.d-flex')->each(function (Crawler $row) use ($product) {
                $provinceName = $row->filter('label')->first()->text();
                $provinceName = $this->handleProvince($provinceName);
                $price = $row->filter('label')->last()->text();
                $price = str($price)
                    ->replace('Rp', '')
                    ->replace(',', '')
                    ->toInteger();

                $province = Province::firstOrCreate(['name' => $provinceName]);

                if ($price !== 0) {
                    Price::create([
                        'product_id' => $product->id,
                        'province_id' => $province->id,
                        'price' => $price,
                    ]);
                }
            });
        });
    }

    public function parseNameFromImage(string $url): string
    {
        return match ($url) {
            'https://mypertamina.id/assets/img/products/turbo.png' => 'Pertamax Turbo',
            'https://mypertamina.id/assets/img/products/pertamax-green95.png' => 'Pertamax Green95',
            'https://mypertamina.id/assets/img/products/1.png' => 'Pertamax',
            'https://mypertamina.id/assets/img/products/lite.png' => 'Pertalite',
            'https://mypertamina.id/assets/img/products/dex.png' => 'Dex',
            'https://mypertamina.id/assets/img/products/dexlite.png' => 'Dexlite',
            'https://mypertamina.id/assets/img/products/bio-solar.png' => 'Biosolar',
            default => 'Unknown',
        };
    }

    public function handleProvince(string $province): string
    {
        return str($province)
            ->replace('Prov. ', '');
    }
}

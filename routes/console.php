<?php

use App\Jobs\Scraper\BPScraper;
use App\Jobs\Scraper\PertaminaScraper;
use App\Jobs\Scraper\ShellScraper;
use App\Jobs\Scraper\VivoScraper;
use Illuminate\Support\Facades\Schedule;

Schedule::call(function () {
    $scrapers = [
        PertaminaScraper::class,
        ShellScraper::class,
        BPScraper::class,
        VivoScraper::class
    ];

    foreach ($scrapers as $scraper) {
        dispatch(new $scraper());
    }

})->dailyAt('13:00');

//Artisan::command('inspire', function () {
//    $this->comment(Inspiring::quote());
//})->purpose('Display an inspiring quote')->hourly();

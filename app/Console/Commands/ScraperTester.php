<?php

namespace App\Console\Commands;

use App\Jobs\Scraper\BPScraper;
use App\Jobs\Scraper\PertaminaScraper;
use App\Jobs\Scraper\ShellScraper;
use App\Jobs\Scraper\VivoScraper;
use Illuminate\Console\Command;
use function Laravel\Prompts\confirm;
use function Laravel\Prompts\select;

class ScraperTester extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:scraper-tester';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test the scraper';

    /**
     * Execute the console command.
     * @throws \Exception
     */
    public function handle()
    {
        $scaper = select(
            label: 'Select scraper that you want to test',
            options: [
                'pertamina' => 'Pertamina',
                'shell' => 'Shell',
                'bp' => 'BP',
                'vivo' => 'Vivo',
                'all' => 'All together'
            ],
            default: 'pertamina',
            required: true
        );


        $migrateFresh = confirm(
            label: 'Do you want to migrate:fresh the database?',
            default: false,
            required: true
        );

        $this->info("Testing $scaper scraper...");

        if ($migrateFresh) {
            $this->info('Migrating fresh...');
            $this->call('migrate:fresh');
        }

        $selectedScraper = match ($scaper) {
            'pertamina' => [new PertaminaScraper()],
            'shell' => [new ShellScraper()],
            'bp' => [new BPScraper()],
            'vivo' => [new VivoScraper()],
            'all' => [
                new PertaminaScraper(),
                new ShellScraper(),
                new BPScraper(),
                new VivoScraper()
            ],
            default => throw new \Exception('Scraper not found'),
        };

        foreach ($selectedScraper as $scraper) {
            $this->info('Dispatching ' . get_class($scraper));
            dispatch_sync($scraper);
        }
    }
}

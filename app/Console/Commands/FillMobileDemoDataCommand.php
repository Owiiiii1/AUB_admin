<?php

namespace App\Console\Commands;

use App\Services\MobileDemoDataService;
use Illuminate\Console\Command;

class FillMobileDemoDataCommand extends Command
{
    protected $signature = 'aub:fill-mobile-demo {--force : Write published weeks, lessons, and attendance}';

    protected $description = 'Fill published Europe/Rome weeks, lessons, and attendance so mobile apps are not empty';

    public function handle(MobileDemoDataService $service): int
    {
        if (! $this->laravel->environment('testing') && ! $this->option('force')) {
            $this->error('Refusing to write demo data without --force.');

            return self::FAILURE;
        }

        $summary = $service->fill();
        $this->info('Mobile demo data filled.');
        foreach ($summary as $key => $value) {
            $this->line($key.': '.$value);
        }

        return self::SUCCESS;
    }
}

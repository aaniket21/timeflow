<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class DeployCheck extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'timeflow:deploy-check';

    protected $description = 'Checks if the application is ready for deployment';

    public function handle()
    {
        $this->info('Starting deployment checks...');

        // Check DB
        try {
            \Illuminate\Support\Facades\DB::connection()->getPdo();
            $this->info('✅ Database connected.');
        } catch (\Exception $e) {
            $this->error('❌ Database connection failed: ' . $e->getMessage());
            return 1;
        }

        // Check Redis/Cache
        try {
            \Illuminate\Support\Facades\Cache::store()->get('deploy-check');
            $this->info('✅ Cache is ready.');
        } catch (\Exception $e) {
            $this->error('❌ Cache connection failed: ' . $e->getMessage());
            return 1;
        }

        $this->info('All deployment checks passed.');
        return 0;
    }
}

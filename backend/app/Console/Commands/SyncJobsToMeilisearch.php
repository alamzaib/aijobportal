<?php

namespace App\Console\Commands;

use App\Models\Job;
use Illuminate\Console\Command;

class SyncJobsToMeilisearch extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'scout:sync-jobs 
                            {--chunk=500 : The number of records to import at a time}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sync all existing jobs to Meilisearch index';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Starting job sync to Meilisearch...');

        $chunkSize = (int) $this->option('chunk');
        
        $totalJobs = Job::where('is_active', true)->count();
        $this->info("Found {$totalJobs} active jobs to sync.");

        if ($totalJobs === 0) {
            $this->warn('No active jobs found to sync.');
            return Command::SUCCESS;
        }

        $bar = $this->output->createProgressBar($totalJobs);
        $bar->start();

        Job::where('is_active', true)
            ->with(['company', 'aiJob'])
            ->chunk($chunkSize, function ($jobs) use ($bar) {
                foreach ($jobs as $job) {
                    $job->searchable();
                    $bar->advance();
                }
            });

        $bar->finish();
        $this->newLine();
        $this->info("Successfully synced {$totalJobs} jobs to Meilisearch!");

        return Command::SUCCESS;
    }
}


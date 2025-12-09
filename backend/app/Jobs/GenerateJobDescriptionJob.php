<?php

namespace App\Jobs;

use App\Models\Job;
use App\Notifications\JobDescriptionGeneratedNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GenerateJobDescriptionJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of times the job may be attempted.
     *
     * @var int
     */
    public $tries = 3;

    /**
     * The number of seconds to wait before retrying the job.
     *
     * @var int
     */
    public $backoff = [10, 30, 60];

    /**
     * Create a new job instance.
     */
    public function __construct(
        public string $jobId,
        public string $title,
        public string $companyName,
        public ?string $prompts = null,
        public string $locale = 'en'
    ) {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $job = Job::find($this->jobId);

        if (!$job) {
            Log::warning("Job not found for description generation: {$this->jobId}");
            return;
        }

        try {
            $aiServiceUrl = env('AI_SERVICE_URL', 'http://ai:8000');
            
            Log::info("Calling FastAPI service for job description generation", [
                'job_id' => $this->jobId,
                'ai_service_url' => $aiServiceUrl,
            ]);

            $response = Http::timeout(60)->post("{$aiServiceUrl}/ai/generate-job-description", [
                'title' => $this->title,
                'company_name' => $this->companyName,
                'prompts' => $this->prompts,
                'locale' => $this->locale,
            ]);

            if (!$response->successful()) {
                Log::error('FastAPI service error in job', [
                    'job_id' => $this->jobId,
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);

                throw new \Exception("FastAPI service returned status {$response->status()}: {$response->body()}");
            }

            $data = $response->json();
            $generatedDescription = $data['job_description'] ?? '';

            if (empty($generatedDescription)) {
                throw new \Exception('Generated description is empty');
            }

            // Update job description
            $job->update([
                'description' => $generatedDescription
            ]);

            Log::info("Job description generated successfully", [
                'job_id' => $this->jobId,
            ]);

            // Send notification to company (employer)
            // Note: In a real application, you'd need to determine who to notify
            // For now, we'll notify via the company's email if available
            if ($job->company && $job->company->email) {
                try {
                    $job->company->notify(new JobDescriptionGeneratedNotification($job));
                } catch (\Exception $e) {
                    Log::warning("Failed to send notification", [
                        'job_id' => $this->jobId,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

        } catch (\Exception $e) {
            Log::error('Error in GenerateJobDescriptionJob', [
                'job_id' => $this->jobId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            // Re-throw to trigger retry mechanism
            throw $e;
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('GenerateJobDescriptionJob failed permanently', [
            'job_id' => $this->jobId,
            'error' => $exception->getMessage(),
        ]);

        // Optionally, you could update the job status or send a failure notification here
    }
}


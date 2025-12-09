<?php

namespace App\Jobs;

use App\Models\Application;
use App\Models\Job;
use App\Models\Resume;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class CalculateMatchScoreJob implements ShouldQueue
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
        public string $applicationId
    ) {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $application = Application::with(['job', 'resume'])->find($this->applicationId);

        if (!$application) {
            Log::warning("Application not found for match score calculation: {$this->applicationId}");
            return;
        }

        if (!$application->job) {
            Log::warning("Job not found for application: {$this->applicationId}");
            return;
        }

        // Skip if no resume or resume not parsed yet
        if (!$application->resume || !$application->resume->parsed_json) {
            Log::info("Resume not available or not parsed for application: {$this->applicationId}");
            return;
        }

        try {
            $aiServiceUrl = env('AI_SERVICE_URL', 'http://ai:8000');
            
            Log::info("Calling AI service for match score calculation", [
                'application_id' => $this->applicationId,
                'job_id' => $application->job_id,
                'ai_service_url' => $aiServiceUrl,
            ]);

            // Prepare request payload
            $payload = [
                'job_id' => $application->job_id,
                'job_description' => $application->job->description ?? '',
                'candidate_resume_parsed_json' => $application->resume->parsed_json,
            ];

            $response = Http::timeout(120)->post("{$aiServiceUrl}/ai/match", $payload);

            if (!$response->successful()) {
                Log::error('AI service error in match score calculation', [
                    'application_id' => $this->applicationId,
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);

                throw new \Exception("AI service returned status {$response->status()}: {$response->body()}");
            }

            $matchData = $response->json();

            if (empty($matchData) || !isset($matchData['match_score'])) {
                throw new \Exception('Invalid response from AI service: missing match_score');
            }

            // Update application with match score
            $application->update([
                'score' => $matchData['match_score']
            ]);

            Log::info("Match score calculated successfully", [
                'application_id' => $this->applicationId,
                'match_score' => $matchData['match_score'],
            ]);

        } catch (\Exception $e) {
            Log::error('Error in CalculateMatchScoreJob', [
                'application_id' => $this->applicationId,
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
        Log::error('CalculateMatchScoreJob failed permanently', [
            'application_id' => $this->applicationId,
            'error' => $exception->getMessage(),
        ]);

        // Optionally, you could update the application status or send a failure notification here
    }
}


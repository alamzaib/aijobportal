<?php

namespace App\Jobs;

use App\Models\Resume;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class AnalyzeCvJob implements ShouldQueue
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
        public string $resumeId
    ) {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $resume = Resume::find($this->resumeId);

        if (!$resume) {
            Log::warning("Resume not found for CV analysis: {$this->resumeId}");
            return;
        }

        // Skip if already parsed
        if ($resume->parsed_json) {
            Log::info("Resume already parsed, skipping: {$this->resumeId}");
            return;
        }

        try {
            $aiServiceUrl = env('AI_SERVICE_URL', 'http://ai:8000');
            
            Log::info("Calling FastAPI service for CV analysis", [
                'resume_id' => $this->resumeId,
                'ai_service_url' => $aiServiceUrl,
            ]);

            // Prepare request payload
            $payload = [];
            
            // Try to use S3 URL first if available
            if ($resume->s3_url) {
                $payload['s3_url'] = $resume->s3_url;
            } else {
                // Fallback: try to read file content if stored locally
                // For MVP, we'll need to extract text from PDF/DOCX
                // For now, we'll log a warning and skip
                Log::warning("Resume has no S3 URL and raw text extraction not implemented", [
                    'resume_id' => $this->resumeId,
                    'file_path' => $resume->file_path,
                ]);
                
                // Try to read file if it's a text file
                if ($resume->file_path && Storage::exists($resume->file_path)) {
                    $content = Storage::get($resume->file_path);
                    // Basic check: if it looks like text, use it
                    if (mb_check_encoding($content, 'UTF-8') && strlen($content) < 100000) {
                        $payload['raw_text'] = $content;
                    } else {
                        Log::warning("Resume file is not plain text, cannot analyze without S3 URL", [
                            'resume_id' => $this->resumeId,
                        ]);
                        return;
                    }
                } else {
                    Log::warning("Resume file not found or not accessible", [
                        'resume_id' => $this->resumeId,
                        'file_path' => $resume->file_path,
                    ]);
                    return;
                }
            }

            $response = Http::timeout(120)->post("{$aiServiceUrl}/ai/analyze-cv", $payload);

            if (!$response->successful()) {
                Log::error('FastAPI service error in CV analysis', [
                    'resume_id' => $this->resumeId,
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);

                throw new \Exception("FastAPI service returned status {$response->status()}: {$response->body()}");
            }

            $parsedData = $response->json();

            if (empty($parsedData)) {
                throw new \Exception('Parsed data is empty');
            }

            // Save parsed JSON to resume
            $resume->update([
                'parsed_json' => $parsedData
            ]);

            Log::info("CV analysis completed successfully", [
                'resume_id' => $this->resumeId,
            ]);

        } catch (\Exception $e) {
            Log::error('Error in AnalyzeCvJob', [
                'resume_id' => $this->resumeId,
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
        Log::error('AnalyzeCvJob failed permanently', [
            'resume_id' => $this->resumeId,
            'error' => $exception->getMessage(),
        ]);

        // Optionally, you could update the resume status or send a failure notification here
    }
}


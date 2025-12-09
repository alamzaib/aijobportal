<?php

namespace App\Http\Controllers;

use App\Jobs\GenerateJobDescriptionJob;
use App\Models\Company;
use App\Models\Job;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class CompanyJobController extends Controller
{
    /**
     * Generate a job description for a company's job.
     * 
     * POST /api/companies/:id/jobs/generate
     * 
     * Request body:
     * - job_id (required): UUID of the job
     * - title (required): Job title
     * - prompts (optional): Additional prompts/requirements
     * - locale (optional): Locale code (default: "en")
     * - async (optional): Boolean flag to process asynchronously (default: false)
     */
    public function generate(Request $request, string $companyId): JsonResponse
    {
        // Validate company exists
        $company = Company::find($companyId);
        if (!$company) {
            return response()->json([
                'message' => 'Company not found'
            ], 404);
        }

        // Validate request
        $validated = $request->validate([
            'job_id' => 'required|uuid|exists:jobs,id',
            'title' => 'required|string|max:255',
            'prompts' => 'nullable|string',
            'locale' => 'nullable|string|max:10',
            'async' => 'nullable|boolean',
        ]);

        // Verify job belongs to company
        $job = Job::where('id', $validated['job_id'])
            ->where('company_id', $companyId)
            ->first();

        if (!$job) {
            return response()->json([
                'message' => 'Job not found or does not belong to this company'
            ], 404);
        }

        $isAsync = $request->boolean('async', false);
        $locale = $validated['locale'] ?? 'en';

        // If async, dispatch job
        if ($isAsync) {
            GenerateJobDescriptionJob::dispatch(
                $job->id,
                $validated['title'],
                $company->name,
                $validated['prompts'] ?? null,
                $locale
            );

            return response()->json([
                'message' => 'Job description generation queued',
                'job_id' => $job->id,
                'status' => 'queued'
            ], 202);
        }

        // Synchronous processing - proxy to FastAPI
        try {
            $aiServiceUrl = env('AI_SERVICE_URL', 'http://ai:8000');
            
            $response = Http::timeout(30)->post("{$aiServiceUrl}/ai/generate-job-description", [
                'title' => $validated['title'],
                'company_name' => $company->name,
                'prompts' => $validated['prompts'] ?? null,
                'locale' => $locale,
            ]);

            if (!$response->successful()) {
                Log::error('FastAPI service error', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);

                return response()->json([
                    'message' => 'Failed to generate job description',
                    'error' => $response->json()['detail'] ?? 'Unknown error'
                ], $response->status());
            }

            $data = $response->json();
            $generatedDescription = $data['job_description'] ?? '';

            // Update job description
            $job->update([
                'description' => $generatedDescription
            ]);

            return response()->json([
                'message' => 'Job description generated successfully',
                'job' => $job->fresh(),
                'generated_description' => $generatedDescription
            ], 200);

        } catch (\Exception $e) {
            Log::error('Error generating job description', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'message' => 'Error generating job description',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }
}


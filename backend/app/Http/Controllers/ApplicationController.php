<?php

namespace App\Http\Controllers;

use App\Jobs\CalculateMatchScoreJob;
use App\Models\Application;
use App\Models\Job;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class ApplicationController extends Controller
{
    /**
     * Get authenticated user's applications.
     */
    public function index(Request $request): JsonResponse
    {
        $applications = Application::where('user_id', $request->user()->id)
            ->with(['job.company', 'resume'])
            ->orderBy('applied_at', 'desc')
            ->get();

        return response()->json($applications);
    }

    /**
     * Apply for a job.
     */
    public function apply(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'job_id' => 'required|uuid|exists:jobs,id',
            'resume_id' => 'nullable|uuid|exists:resumes,id',
            'cover_letter' => 'nullable|string',
        ]);

        $job = Job::findOrFail($validated['job_id']);

        if (!$job->is_active) {
            return response()->json([
                'message' => 'This job is no longer accepting applications.',
            ], 422);
        }

        // Check if user already applied
        $existingApplication = Application::where('user_id', $request->user()->id)
            ->where('job_id', $validated['job_id'])
            ->first();

        if ($existingApplication) {
            return response()->json([
                'message' => 'You have already applied for this job.',
            ], 422);
        }

        $application = Application::create([
            'user_id' => $request->user()->id,
            'job_id' => $validated['job_id'],
            'resume_id' => $validated['resume_id'] ?? null,
            'cover_letter' => $validated['cover_letter'] ?? null,
            'status' => 'pending',
            'applied_at' => now(),
        ]);

        // Dispatch job to calculate match score asynchronously
        if ($application->resume_id) {
            CalculateMatchScoreJob::dispatch($application->id);
        }

        return response()->json([
            'message' => 'Application submitted successfully.',
            'application' => $application->load(['job', 'resume']),
        ], 201);
    }

    /**
     * Get applications for a specific job (for employers).
     */
    public function forJob(Request $request, string $jobId): JsonResponse
    {
        $job = Job::with('company')->findOrFail($jobId);

        // TODO: Add authorization check to ensure user owns the company
        // For MVP, we'll allow any authenticated user to view applications

        $query = Application::where('job_id', $jobId)
            ->with(['user', 'resume']);

        // Handle sorting
        $sortBy = $request->query('sort_by', 'applied_at');
        $sortOrder = $request->query('sort_order', 'desc');

        if ($sortBy === 'score') {
            // Sort by score: nulls last (database-agnostic approach)
            $query->orderByRaw('CASE WHEN score IS NULL THEN 1 ELSE 0 END')
                  ->orderBy('score', 'desc');
        } else {
            $query->orderBy($sortBy, $sortOrder);
        }

        $applications = $query->get();

        return response()->json([
            'job' => $job,
            'applications' => $applications,
        ]);
    }
}


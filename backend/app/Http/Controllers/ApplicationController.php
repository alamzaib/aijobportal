<?php

namespace App\Http\Controllers;

use App\Models\Application;
use App\Models\Job;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class ApplicationController extends Controller
{
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

        return response()->json([
            'message' => 'Application submitted successfully.',
            'application' => $application->load(['job', 'resume']),
        ], 201);
    }
}


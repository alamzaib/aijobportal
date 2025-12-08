<?php

namespace App\Http\Controllers;

use App\Models\Job;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class JobController extends Controller
{
    /**
     * Display a listing of jobs.
     */
    public function index(Request $request): JsonResponse
    {
        $query = Job::with('company')
            ->where('is_active', true);

        // Filter by location
        if ($request->has('location')) {
            $query->where('location', 'like', '%' . $request->location . '%');
        }

        // Filter by type
        if ($request->has('type')) {
            $query->where('type', $request->type);
        }

        // Search by title or description
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', '%' . $search . '%')
                  ->orWhere('description', 'like', '%' . $search . '%');
            });
        }

        $jobs = $query->latest('posted_at')
            ->paginate($request->get('per_page', 15));

        return response()->json($jobs);
    }

    /**
     * Display the specified job.
     * Supports both UUID and slug formats.
     */
    public function show(string $id): JsonResponse
    {
        try {
            // Decode URL-encoded slug
            $id = urldecode($id);
            
            $job = Job::findBySlugOrId($id);
            
            if (!$job) {
                // Get list of available job slugs for debugging
                $availableJobs = Job::where('is_active', true)
                    ->get()
                    ->map(function ($j) {
                        $slug = strtolower($j->title);
                        $slug = preg_replace('/[^a-z0-9]+/', '-', $slug);
                        $slug = trim($slug, '-');
                        return [
                            'id' => $j->id,
                            'title' => $j->title,
                            'slug' => $slug
                        ];
                    })
                    ->toArray();
                
                \Log::warning('Job not found', [
                    'identifier' => $id,
                    'available_jobs' => $availableJobs
                ]);
                
                return response()->json([
                    'message' => 'Job not found',
                    'identifier' => $id,
                    'available_jobs' => config('app.debug') ? $availableJobs : null
                ], 404);
            }

            // Load relationships - make aiJob optional since table might not exist
            $job->load('company');
            
            // Only load aiJob if the table exists
            try {
                $job->load('aiJob');
            } catch (\Exception $e) {
                // Table doesn't exist or relationship fails - that's OK, continue without it
                \Log::debug('Could not load aiJob relationship: ' . $e->getMessage());
            }

            return response()->json($job);
        } catch (\Exception $e) {
            \Log::error('Job lookup error: ' . $e->getMessage(), [
                'identifier' => $id ?? 'unknown',
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'message' => 'Error finding job',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * Store a newly created job.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'company_id' => 'required|exists:companies,id',
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'location' => 'nullable|string|max:255',
            'type' => 'nullable|string|max:255',
            'salary_min' => 'nullable|numeric|min:0',
            'salary_max' => 'nullable|numeric|min:0|gte:salary_min',
            'salary_currency' => 'nullable|string|size:3',
            'requirements' => 'nullable|array',
            'benefits' => 'nullable|array',
            'is_active' => 'boolean',
            'posted_at' => 'nullable|date',
        ]);

        $job = Job::create($validated);

        return response()->json($job, 201);
    }
}


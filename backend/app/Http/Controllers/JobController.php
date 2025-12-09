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
        $perPage = $request->get('per_page', 15);
        $searchQuery = $request->get('search');
        
        // Use Meilisearch if search query is provided
        if ($searchQuery) {
            return $this->searchWithMeilisearch($request, $perPage);
        }

        // Otherwise use regular database query
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

        // Filter by salary range
        if ($request->has('salary_min')) {
            $query->where('salary_max', '>=', $request->salary_min)
                  ->orWhere(function ($q) use ($request) {
                      $q->whereNull('salary_max')
                        ->where('salary_min', '>=', $request->salary_min);
                  });
        }

        if ($request->has('salary_max')) {
            $query->where(function ($q) use ($request) {
                $q->where('salary_min', '<=', $request->salary_max)
                  ->orWhereNull('salary_min');
            });
        }

        $jobs = $query->latest('posted_at')
            ->paginate($perPage);

        return response()->json($jobs);
    }

    /**
     * Search jobs using Meilisearch with filters.
     */
    private function searchWithMeilisearch(Request $request, int $perPage): JsonResponse
    {
        $searchQuery = $request->get('search', '');
        $page = $request->get('page', 1);
        
        try {
            // Build filters array for Meilisearch
            // Laravel Scout Meilisearch uses where() method with field, operator, value
            $search = Job::search($searchQuery);
            
            // Always filter by active jobs
            $search->where('is_active', true);
            
            if ($request->has('location') && $request->location) {
                $location = $request->location;
                // Extract city from location if it contains a comma
                if (strpos($location, ',') !== false) {
                    $locationCity = trim(explode(',', $location)[0]);
                    // Try location_city first
                    $search->where('location_city', $locationCity);
                } else {
                    // Try location_city first
                    $search->where('location_city', $location);
                }
            }
            
            if ($request->has('type') && $request->type) {
                $search->where('type', $request->type);
            }
            
            if ($request->has('salary_min') && $request->salary_min) {
                $salaryMin = (float) $request->salary_min;
                $search->where('salary_max', '>=', $salaryMin);
            }
            
            if ($request->has('salary_max') && $request->salary_max) {
                $salaryMax = (float) $request->salary_max;
                $search->where('salary_min', '<=', $salaryMax);
            }
            
            // Perform search with ordering and pagination
            $jobs = $search->orderBy('posted_at', 'desc')
                ->paginate($perPage, 'page', $page);
            
            // Get the actual Job models from the search results
            $jobIds = $jobs->pluck('id')->toArray();
            $jobModels = Job::whereIn('id', $jobIds)
                ->with('company')
                ->get()
                ->sortBy(function ($job) use ($jobIds) {
                    return array_search($job->id, $jobIds);
                })
                ->values();
            
            // Transform to match expected format
            $transformedData = [
                'data' => $jobModels->toArray(),
                'current_page' => $jobs->currentPage(),
                'last_page' => $jobs->lastPage(),
                'per_page' => $jobs->perPage(),
                'total' => $jobs->total(),
                'from' => $jobs->firstItem(),
                'to' => $jobs->lastItem(),
            ];
            
            return response()->json($transformedData);
        } catch (\Exception $e) {
            \Log::error('Meilisearch search error: ' . $e->getMessage(), [
                'query' => $searchQuery,
                'filters' => $request->all(),
                'trace' => $e->getTraceAsString()
            ]);
            
            // Fallback to database search on error
            return $this->fallbackDatabaseSearch($request, $perPage);
        }
    }
    
    /**
     * Fallback to database search if Meilisearch fails.
     */
    private function fallbackDatabaseSearch(Request $request, int $perPage): JsonResponse
    {
        $query = Job::with('company')
            ->where('is_active', true);
        
        // Search by title or description
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', '%' . $search . '%')
                  ->orWhere('description', 'like', '%' . $search . '%');
            });
        }
        
        // Filter by location
        if ($request->has('location')) {
            $query->where('location', 'like', '%' . $request->location . '%');
        }
        
        // Filter by type
        if ($request->has('type')) {
            $query->where('type', $request->type);
        }
        
        // Filter by salary range
        if ($request->has('salary_min')) {
            $query->where('salary_max', '>=', $request->salary_min)
                  ->orWhere(function ($q) use ($request) {
                      $q->whereNull('salary_max')
                        ->where('salary_min', '>=', $request->salary_min);
                  });
        }
        
        if ($request->has('salary_max')) {
            $query->where(function ($q) use ($request) {
                $q->where('salary_min', '<=', $request->salary_max)
                  ->orWhereNull('salary_min');
            });
        }
        
        $jobs = $query->latest('posted_at')
            ->paginate($perPage);
        
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


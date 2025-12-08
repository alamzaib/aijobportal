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
     */
    public function show(string $id): JsonResponse
    {
        $job = Job::with(['company', 'aiJob'])
            ->findOrFail($id);

        return response()->json($job);
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


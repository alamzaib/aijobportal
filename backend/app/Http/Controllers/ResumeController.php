<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreResumeRequest;
use App\Jobs\AnalyzeCvJob;
use App\Jobs\ScanResumeJob;
use App\Models\Resume;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ResumeController extends Controller
{
    /**
     * Upload a resume file.
     */
    public function store(StoreResumeRequest $request): JsonResponse
    {
        try {
            $file = $request->file('resume');
            $user = $request->user();

            // Generate unique filename
            $filename = Str::uuid() . '.' . $file->getClientOriginalExtension();
            $directory = 'resumes/' . $user->id;

            // Determine storage disk (use local if S3 is not configured)
            $s3Config = config('filesystems.disks.s3');
            $useS3 = !empty($s3Config['key']) && !empty($s3Config['secret']) && !empty($s3Config['bucket']);
            $disk = $useS3 ? 's3' : 'local';
            
            if (!$useS3) {
                \Log::info('S3 not configured, using local storage', [
                    'user_id' => $user->id,
                    'filename' => $filename,
                ]);
                
                // Ensure local storage directory exists
                $storagePath = storage_path('app/' . $directory);
                if (!file_exists($storagePath)) {
                    if (!mkdir($storagePath, 0755, true)) {
                        \Log::error('Failed to create storage directory', [
                            'path' => $storagePath,
                        ]);
                        return response()->json([
                            'message' => 'Failed to create storage directory. Please check permissions.',
                        ], 500);
                    }
                }
            }

            // Stream file to storage
            $filePath = Storage::disk($disk)->putFileAs(
                $directory,
                $file,
                $filename
            );

            if (!$filePath) {
                \Log::error('Failed to upload file', [
                    'filename' => $filename,
                    'directory' => $directory,
                    'disk' => $disk,
                ]);
                return response()->json([
                    'message' => 'Failed to upload file. Please try again.',
                ], 500);
            }

            // Set visibility to private for security (S3 only)
            if ($useS3) {
                try {
                    Storage::disk('s3')->setVisibility($filePath, 'private');
                } catch (\Exception $e) {
                    \Log::warning('Failed to set file visibility', [
                        'path' => $filePath,
                        'error' => $e->getMessage(),
                    ]);
                    // Continue even if visibility setting fails
                }
            }

            // Get the full URL
            if ($useS3) {
                $fullUrl = Storage::disk('s3')->url($filePath);
            } else {
                // For local storage, we'll set s3_url to null
                // The file_path will contain the storage path
                $fullUrl = null;
            }

            // Create resume record
            $resume = Resume::create([
                'user_id' => $user->id,
                'title' => $file->getClientOriginalName(),
                'file_path' => $filePath,
                's3_url' => $useS3 ? $fullUrl : null, // Only set s3_url if using S3
                'parsed_json' => null,
                'is_default' => false,
            ]);

            // Dispatch virus scan job (stubbed) - use sync queue to avoid Redis issues
            try {
                // Use sync queue connection to avoid Redis dependency
                ScanResumeJob::dispatch($resume->id)->onConnection('sync');
            } catch (\Exception $e) {
                \Log::warning('Failed to dispatch scan job', [
                    'resume_id' => $resume->id,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);
                // Continue even if job dispatch fails - this is not critical
            }

            // Dispatch CV analysis job
            try {
                AnalyzeCvJob::dispatch($resume->id)->onConnection('sync');
            } catch (\Exception $e) {
                \Log::warning('Failed to dispatch CV analysis job', [
                    'resume_id' => $resume->id,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);
                // Continue even if job dispatch fails - this is not critical
            }

            return response()->json([
                'message' => 'Resume uploaded successfully.',
                'resume' => $resume,
            ], 201);
        } catch (\Exception $e) {
            \Log::error('Resume upload error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);

            return response()->json([
                'message' => 'An error occurred while uploading the resume. Please try again.',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * Get authenticated user's resumes.
     */
    public function index(Request $request): JsonResponse
    {
        $resumes = Resume::where('user_id', $request->user()->id)
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json($resumes);
    }
}


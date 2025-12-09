<?php

namespace Tests\Unit;

use App\Jobs\GenerateJobDescriptionJob;
use App\Models\Company;
use App\Models\Job;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class GenerateJobDescriptionJobTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Set AI service URL for testing
        config(['app.ai_service_url' => 'http://test-ai-service']);
    }

    /**
     * Test job executes successfully and updates job description.
     */
    public function test_job_executes_successfully(): void
    {
        Notification::fake();

        $company = Company::factory()->create([
            'email' => 'test@company.com',
        ]);
        $job = Job::factory()->create([
            'company_id' => $company->id,
            'title' => 'Software Engineer',
            'description' => 'Old description',
        ]);

        // Mock FastAPI response
        Http::fake([
            'test-ai-service/ai/generate-job-description' => Http::response([
                'job_description' => 'This is a generated job description.',
                'title' => 'Software Engineer',
                'company_name' => $company->name,
                'locale' => 'en',
            ], 200),
        ]);

        $jobInstance = new GenerateJobDescriptionJob(
            $job->id,
            'Software Engineer',
            $company->name,
            null,
            'en'
        );

        $jobInstance->handle();

        // Verify job description was updated
        $job->refresh();
        $this->assertEquals('This is a generated job description.', $job->description);

        // Verify notification was sent
        Notification::assertSentTo(
            $company,
            \App\Notifications\JobDescriptionGeneratedNotification::class
        );
    }

    /**
     * Test job handles FastAPI errors.
     */
    public function test_job_handles_fastapi_errors(): void
    {
        $company = Company::factory()->create();
        $job = Job::factory()->create([
            'company_id' => $company->id,
        ]);

        // Mock FastAPI error response
        Http::fake([
            'test-ai-service/ai/generate-job-description' => Http::response([
                'detail' => 'Service unavailable',
            ], 503),
        ]);

        $jobInstance = new GenerateJobDescriptionJob(
            $job->id,
            'Test Job',
            $company->name
        );

        $this->expectException(\Exception::class);
        $jobInstance->handle();
    }

    /**
     * Test job handles missing job.
     */
    public function test_job_handles_missing_job(): void
    {
        $jobInstance = new GenerateJobDescriptionJob(
            '00000000-0000-0000-0000-000000000000',
            'Test Job',
            'Test Company'
        );

        // Should not throw exception, just log warning
        $jobInstance->handle();

        // No assertions needed - just verify no exception is thrown
        $this->assertTrue(true);
    }

    /**
     * Test job handles empty description.
     */
    public function test_job_handles_empty_description(): void
    {
        $company = Company::factory()->create();
        $job = Job::factory()->create([
            'company_id' => $company->id,
        ]);

        // Mock FastAPI response with empty description
        Http::fake([
            'test-ai-service/ai/generate-job-description' => Http::response([
                'job_description' => '',
                'title' => 'Test Job',
                'company_name' => $company->name,
                'locale' => 'en',
            ], 200),
        ]);

        $jobInstance = new GenerateJobDescriptionJob(
            $job->id,
            'Test Job',
            $company->name
        );

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Generated description is empty');
        $jobInstance->handle();
    }

    /**
     * Test job retry configuration.
     */
    public function test_job_retry_configuration(): void
    {
        $jobInstance = new GenerateJobDescriptionJob(
            'test-id',
            'Test Job',
            'Test Company'
        );

        $this->assertEquals(3, $jobInstance->tries);
        $this->assertEquals([10, 30, 60], $jobInstance->backoff);
    }

    /**
     * Test job notification when company has no email.
     */
    public function test_job_skips_notification_when_no_email(): void
    {
        Notification::fake();

        $company = Company::factory()->create([
            'email' => null,
        ]);
        $job = Job::factory()->create([
            'company_id' => $company->id,
        ]);

        Http::fake([
            'test-ai-service/ai/generate-job-description' => Http::response([
                'job_description' => 'Generated description',
                'title' => 'Test Job',
                'company_name' => $company->name,
                'locale' => 'en',
            ], 200),
        ]);

        $jobInstance = new GenerateJobDescriptionJob(
            $job->id,
            'Test Job',
            $company->name
        );

        $jobInstance->handle();

        // Verify no notification was sent
        Notification::assertNothingSent();
    }
}


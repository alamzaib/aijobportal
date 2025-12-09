<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Job;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class CompanyJobGenerateTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Set AI service URL for testing
        config(['app.ai_service_url' => 'http://test-ai-service']);
    }

    /**
     * Test synchronous job description generation.
     */
    public function test_synchronous_job_description_generation(): void
    {
        /** @var User $user */
        $user = User::factory()->create();
        $company = Company::factory()->create();
        $job = Job::factory()->create([
            'company_id' => $company->id,
            'title' => 'Software Engineer',
            'description' => 'Old description',
        ]);

        // Mock FastAPI response
        Http::fake([
            'test-ai-service/ai/generate-job-description' => Http::response([
                'job_description' => 'This is a generated job description for Software Engineer.',
                'title' => 'Software Engineer',
                'company_name' => $company->name,
                'locale' => 'en',
            ], 200),
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->postJson("/api/companies/{$company->id}/jobs/generate", [
                'job_id' => $job->id,
                'title' => 'Software Engineer',
                'locale' => 'en',
                'async' => false,
            ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'message',
                'job',
                'generated_description',
            ]);

        // Verify job description was updated
        $job->refresh();
        $this->assertEquals('This is a generated job description for Software Engineer.', $job->description);
    }

    /**
     * Test asynchronous job description generation.
     */
    public function test_asynchronous_job_description_generation(): void
    {
        Queue::fake();

        /** @var User $user */
        $user = User::factory()->create();
        $company = Company::factory()->create();
        $job = Job::factory()->create([
            'company_id' => $company->id,
            'title' => 'Data Scientist',
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->postJson("/api/companies/{$company->id}/jobs/generate", [
                'job_id' => $job->id,
                'title' => 'Data Scientist',
                'prompts' => 'Focus on machine learning experience',
                'locale' => 'en',
                'async' => true,
            ]);

        $response->assertStatus(202)
            ->assertJson([
                'message' => 'Job description generation queued',
                'status' => 'queued',
            ]);

        // Assert job was dispatched
        Queue::assertPushed(\App\Jobs\GenerateJobDescriptionJob::class, function ($queuedJob) use ($job) {
            return $queuedJob->jobId === $job->id
                && $queuedJob->title === 'Data Scientist'
                && $queuedJob->prompts === 'Focus on machine learning experience';
        });
    }

    /**
     * Test validation errors.
     */
    public function test_validation_errors(): void
    {
        /** @var User $user */
        $user = User::factory()->create();
        $company = Company::factory()->create();

        $response = $this->actingAs($user, 'sanctum')
            ->postJson("/api/companies/{$company->id}/jobs/generate", [
                // Missing required fields
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['job_id', 'title']);
    }

    /**
     * Test company not found.
     */
    public function test_company_not_found(): void
    {
        /** @var User $user */
        $user = User::factory()->create();

        $response = $this->actingAs($user, 'sanctum')
            ->postJson("/api/companies/999/jobs/generate", [
                'job_id' => '00000000-0000-0000-0000-000000000000',
                'title' => 'Test Job',
            ]);

        $response->assertStatus(404)
            ->assertJson([
                'message' => 'Company not found',
            ]);
    }

    /**
     * Test job does not belong to company.
     */
    public function test_job_does_not_belong_to_company(): void
    {
        /** @var User $user */
        $user = User::factory()->create();
        $company1 = Company::factory()->create();
        $company2 = Company::factory()->create();
        $job = Job::factory()->create([
            'company_id' => $company2->id,
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->postJson("/api/companies/{$company1->id}/jobs/generate", [
                'job_id' => $job->id,
                'title' => 'Test Job',
            ]);

        $response->assertStatus(404)
            ->assertJson([
                'message' => 'Job not found or does not belong to this company',
            ]);
    }

    /**
     * Test FastAPI service error handling.
     */
    public function test_fastapi_service_error(): void
    {
        /** @var User $user */
        $user = User::factory()->create();
        $company = Company::factory()->create();
        $job = Job::factory()->create([
            'company_id' => $company->id,
        ]);

        // Mock FastAPI error response
        Http::fake([
            'test-ai-service/ai/generate-job-description' => Http::response([
                'detail' => 'OpenAI API key not configured',
            ], 500),
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->postJson("/api/companies/{$company->id}/jobs/generate", [
                'job_id' => $job->id,
                'title' => 'Test Job',
                'async' => false,
            ]);

        $response->assertStatus(500)
            ->assertJsonStructure([
                'message',
                'error',
            ]);
    }

    /**
     * Test unauthenticated access.
     */
    public function test_unauthenticated_access(): void
    {
        $company = Company::factory()->create();

        $response = $this->postJson("/api/companies/{$company->id}/jobs/generate", [
            'job_id' => '00000000-0000-0000-0000-000000000000',
            'title' => 'Test Job',
        ]);

        $response->assertStatus(401);
    }
}


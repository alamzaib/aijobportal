<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('ai_jobs', function (Blueprint $table) {
            $table->id();
            $table->foreignUuid('job_id')->constrained()->onDelete('cascade');
            $table->text('ai_generated_description')->nullable();
            $table->json('ai_extracted_requirements')->nullable();
            $table->json('ai_suggested_skills')->nullable();
            $table->json('ai_analysis')->nullable();
            $table->string('ai_model')->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ai_jobs');
    }
};


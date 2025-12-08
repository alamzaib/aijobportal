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
        Schema::create('applications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignUuid('job_id')->constrained()->onDelete('cascade');
            $table->foreignUuid('resume_id')->nullable()->constrained()->onDelete('set null');
            $table->text('cover_letter')->nullable();
            $table->enum('status', ['pending', 'reviewing', 'shortlisted', 'rejected', 'accepted'])->default('pending');
            $table->timestamp('applied_at')->useCurrent();
            $table->timestamps();
            
            $table->unique(['user_id', 'job_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('applications');
    }
};


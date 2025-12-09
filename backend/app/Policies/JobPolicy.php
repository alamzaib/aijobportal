<?php

namespace App\Policies;

use App\Models\Job;
use App\Models\User;

class JobPolicy
{
    /**
     * Determine if the user can view any jobs.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasRole('Admin');
    }

    /**
     * Determine if the user can view the job.
     */
    public function view(User $user, Job $job): bool
    {
        return $user->hasRole('Admin');
    }

    /**
     * Determine if the user can create jobs.
     */
    public function create(User $user): bool
    {
        return $user->hasRole('Admin') || $user->hasRole('Employer');
    }

    /**
     * Determine if the user can update the job.
     */
    public function update(User $user, Job $job): bool
    {
        return $user->hasRole('Admin') || 
               ($user->hasRole('Employer') && $job->company->user_id === $user->id);
    }

    /**
     * Determine if the user can delete the job.
     */
    public function delete(User $user, Job $job): bool
    {
        return $user->hasRole('Admin');
    }

    /**
     * Determine if the user can approve the job.
     */
    public function approve(User $user, Job $job): bool
    {
        return $user->hasRole('Admin');
    }

    /**
     * Determine if the user can reject the job.
     */
    public function reject(User $user, Job $job): bool
    {
        return $user->hasRole('Admin');
    }
}


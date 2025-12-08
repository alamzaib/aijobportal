<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\Job;
use Illuminate\Database\Seeder;

class JobSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get existing companies or create if none exist
        $companies = Company::all();
        
        if ($companies->isEmpty()) {
            $companies = Company::factory(5)->create();
        }

        // Create exactly 10 jobs distributed across companies
        $totalJobs = 10;
        $jobsPerCompany = (int) ceil($totalJobs / $companies->count());
        $created = 0;
        
        foreach ($companies as $company) {
            $remaining = $totalJobs - $created;
            $toCreate = min($jobsPerCompany, $remaining);
            
            if ($toCreate > 0) {
                Job::factory($toCreate)->create([
                    'company_id' => $company->id,
                ]);
                $created += $toCreate;
            }
            
            if ($created >= $totalJobs) {
                break;
            }
        }
    }
}


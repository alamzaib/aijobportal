<?php

namespace Database\Factories;

use App\Models\Company;
use App\Models\Job;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Job>
 */
class JobFactory extends Factory
{
    protected $model = Job::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $types = ['full-time', 'part-time', 'contract', 'freelance', 'internship'];
        $locations = ['Remote', 'New York, NY', 'San Francisco, CA', 'London, UK', 'Toronto, Canada', 'Berlin, Germany'];
        $currencies = ['USD', 'EUR', 'GBP', 'CAD'];

        $salaryMin = $this->faker->numberBetween(40000, 80000);
        $salaryMax = $salaryMin + $this->faker->numberBetween(20000, 50000);

        return [
            'company_id' => Company::factory(),
            'title' => $this->faker->jobTitle(),
            'description' => $this->faker->paragraphs(5, true),
            'location' => $this->faker->randomElement($locations),
            'type' => $this->faker->randomElement($types),
            'salary_min' => $salaryMin,
            'salary_max' => $salaryMax,
            'salary_currency' => $this->faker->randomElement($currencies),
            'requirements' => [
                $this->faker->sentence(),
                $this->faker->sentence(),
                $this->faker->sentence(),
            ],
            'benefits' => [
                'Health Insurance',
                'Dental Insurance',
                '401(k)',
                'Paid Time Off',
                'Remote Work',
            ],
            'is_active' => $this->faker->boolean(90),
            'posted_at' => $this->faker->dateTimeBetween('-30 days', 'now'),
        ];
    }
}


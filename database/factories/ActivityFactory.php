<?php

namespace Database\Factories;

use App\Models\Activity;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Activity>
 */
class ActivityFactory extends Factory
{
    protected $model = Activity::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $activities = [
            ['title' => 'Daily SMS count vs. log comparison', 'category' => 'SMS'],
            ['title' => 'Server health check', 'category' => 'Infrastructure'],
            ['title' => 'Ticket queue review', 'category' => 'Support'],
            ['title' => 'Database backup verification', 'category' => 'Infrastructure'],
            ['title' => 'API response time monitoring', 'category' => 'Performance'],
            ['title' => 'Error log review', 'category' => 'Monitoring'],
            ['title' => 'Scheduled job execution verification', 'category' => 'Automation'],
            ['title' => 'User access audit', 'category' => 'Security'],
            ['title' => 'Platform uptime check', 'category' => 'Infrastructure'],
            ['title' => 'Client escalation follow-up', 'category' => 'Support'],
        ];

        $activity = fake()->randomElement($activities);

        return [
            'title' => $activity['title'],
            'description' => fake()->sentence(),
            'category' => $activity['category'],
            'is_recurring' => true,
        ];
    }

    /**
     * Indicate that the activity is non-recurring.
     */
    public function nonRecurring(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_recurring' => false,
        ]);
    }
}

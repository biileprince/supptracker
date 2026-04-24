<?php

namespace Database\Factories;

use App\Models\Activity;
use App\Models\ActivityUpdate;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ActivityUpdate>
 */
class ActivityUpdateFactory extends Factory
{
    protected $model = ActivityUpdate::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'activity_id' => Activity::factory(),
            'user_id' => User::factory(),
            'status' => fake()->randomElement(['pending', 'in_progress', 'done']),
            'remark' => fake()->optional(0.7)->sentence(),
            'activity_date' => fake()->dateTimeBetween('-7 days', 'now')->format('Y-m-d'),
        ];
    }

    /**
     * Indicate that the update marks the activity as done.
     */
    public function done(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'done',
            'remark' => 'Completed successfully.',
        ]);
    }

    /**
     * Indicate that the update marks the activity as pending.
     */
    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'pending',
        ]);
    }

    /**
     * Indicate that the update marks the activity as in progress.
     */
    public function inProgress(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'in_progress',
        ]);
    }
}

<?php

namespace Tests\Feature;

use App\Models\Activity;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ActivityUpdateControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_auth_user_can_store_activity_update()
    {
        $user = User::factory()->create();
        $activity = Activity::factory()->create();

        $response = $this->actingAs($user)->post(route('activity_updates.store'), [
            'activity_id' => $activity->id,
            'status' => 'done',
            'remark' => 'Checked and verified.',
            'activity_date' => now()->toDateString(),
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('activity_updates', [
            'user_id' => $user->id,
            'activity_id' => $activity->id,
            'status' => 'done',
            'remark' => 'Checked and verified.',
            'activity_date' => now()->format('Y-m-d 00:00:00'),
        ]);
    }

    public function test_unauthenticated_user_cannot_store_activity_update()
    {
        $activity = Activity::factory()->create();

        $response = $this->post(route('activity_updates.store'), [
            'activity_id' => $activity->id,
            'status' => 'done',
            'activity_date' => now()->toDateString(),
        ]);

        $response->assertRedirect(route('login'));
    }

    public function test_store_validates_required_fields()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('activity_updates.store'), []);

        $response->assertSessionHasErrors(['activity_id', 'status', 'activity_date']);
    }
}

<?php

namespace Tests\Feature;

use App\Models\Activity;
use App\Models\ActivityUpdate;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DailyViewControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_auth_user_can_view_daily_handover()
    {
        $user = User::factory()->create();

        $activity = Activity::factory()->create();
        ActivityUpdate::factory()->create([
            'activity_id' => $activity->id,
            'user_id' => $user->id,
            'activity_date' => now()->toDateString(),
        ]);

        $response = $this->actingAs($user)->get(route('daily.index'));

        $response->assertOk();
    }

    public function test_unauthenticated_user_cannot_view_daily_handover()
    {
        $response = $this->get(route('daily.index'));

        $response->assertRedirect(route('login'));
    }
}

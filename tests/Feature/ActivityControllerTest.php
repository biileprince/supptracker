<?php

namespace Tests\Feature;

use App\Models\Activity;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ActivityControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_any_auth_user_can_view_activities_index()
    {
        $user = User::factory()->create();
        Activity::factory(3)->create();

        $response = $this->actingAs($user)->get(route('activities.index'));

        $response->assertOk();
    }

    public function test_admin_can_view_create_page()
    {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)->get(route('activities.create'));

        $response->assertOk();
    }

    public function test_member_cannot_view_create_page()
    {
        $member = User::factory()->create();

        $response = $this->actingAs($member)->get(route('activities.create'));

        $response->assertForbidden();
    }

    public function test_admin_can_store_activity()
    {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)->post(route('activities.store'), [
            'title' => 'New Activity',
            'category' => 'Test',
            'is_recurring' => true,
        ]);

        $response->assertRedirect(route('activities.index'));
        $this->assertDatabaseHas('activities', [
            'title' => 'New Activity',
            'category' => 'Test',
        ]);
    }

    public function test_member_cannot_store_activity()
    {
        $member = User::factory()->create();

        $response = $this->actingAs($member)->post(route('activities.store'), [
            'title' => 'New Activity',
        ]);

        $response->assertForbidden();
    }

    public function test_admin_can_update_activity()
    {
        $admin = User::factory()->admin()->create();
        $activity = Activity::factory()->create(['title' => 'Old Title']);

        $response = $this->actingAs($admin)->put(route('activities.update', $activity), [
            'title' => 'New Title',
            'is_recurring' => false,
        ]);

        $response->assertRedirect(route('activities.index'));
        $this->assertDatabaseHas('activities', [
            'id' => $activity->id,
            'title' => 'New Title',
            'is_recurring' => false,
        ]);
    }

    public function test_admin_can_delete_activity()
    {
        $admin = User::factory()->admin()->create();
        $activity = Activity::factory()->create();

        $response = $this->actingAs($admin)->delete(route('activities.destroy', $activity));

        $response->assertRedirect(route('activities.index'));
        $this->assertDatabaseMissing('activities', [
            'id' => $activity->id,
        ]);
    }
}

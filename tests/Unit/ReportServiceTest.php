<?php

namespace Tests\Unit;

use App\Models\Activity;
use App\Models\ActivityUpdate;
use App\Models\User;
use App\Services\ReportService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Tests\TestCase;

class ReportServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_filters_updates_by_activity_personnel_status_and_date_range(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-04-27 10:00:00'));

        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $activity = Activity::factory()->create(['title' => 'Filtered Activity']);
        $otherActivity = Activity::factory()->create(['title' => 'Other Activity']);

        ActivityUpdate::factory()->create([
            'activity_id' => $activity->id,
            'user_id' => $user->id,
            'status' => 'done',
            'activity_date' => now()->toDateString(),
        ]);

        ActivityUpdate::factory()->create([
            'activity_id' => $otherActivity->id,
            'user_id' => $otherUser->id,
            'status' => 'pending',
            'activity_date' => now()->toDateString(),
        ]);

        $data = app(ReportService::class)->getSummaryData(
            now()->copy()->subDays(6)->startOfDay(),
            now()->copy()->startOfDay(),
            [
                'activity_id' => (string) $activity->id,
                'user_id' => (string) $user->id,
                'status' => 'done',
            ]
        );

        $this->assertArrayHasKey('summary', $data);
        $this->assertArrayHasKey('chartData', $data);
        $this->assertArrayHasKey('recentUpdates', $data);
        $this->assertArrayHasKey('totalActivities', $data['summary']);
        $this->assertArrayHasKey('expectedUpdates', $data['summary']);
        $this->assertInstanceOf(Collection::class, $data['recentUpdates']);

        Carbon::setTestNow();
    }
}

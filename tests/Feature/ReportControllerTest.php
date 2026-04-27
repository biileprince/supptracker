<?php

namespace Tests\Feature;

use App\Models\Activity;
use App\Models\ActivityUpdate;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReportControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    public function test_auth_user_can_view_reports_page(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('reports.index'));

        $response->assertOk();
    }

    public function test_unauthenticated_user_cannot_view_reports(): void
    {
        $response = $this->get(route('reports.index'));

        $response->assertRedirect(route('login'));
    }

    public function test_reports_page_returns_expected_data_structure(): void
    {
        $user = User::factory()->create();
        $activity = Activity::factory()->create();

        ActivityUpdate::factory()->create([
            'activity_id' => $activity->id,
            'user_id' => $user->id,
            'status' => 'done',
            'activity_date' => now()->toDateString(),
        ]);

        $response = $this->actingAs($user)->get(route('reports.index'));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('reports/index')
            ->has('summary')
            ->has('chartData')
            ->has('recentUpdates')
            ->has('dateRange')
        );
    }

    public function test_reports_page_accepts_date_range_filters(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('reports.index', [
            'start_date' => now()->subDays(30)->toDateString(),
            'end_date' => now()->toDateString(),
        ]));

        $response->assertOk();
    }

    public function test_csv_export_returns_csv_file(): void
    {
        $user = User::factory()->create();
        $activity = Activity::factory()->create();

        ActivityUpdate::factory()->create([
            'activity_id' => $activity->id,
            'user_id' => $user->id,
            'activity_date' => now()->toDateString(),
        ]);

        $response = $this->actingAs($user)->get(route('reports.export.csv'));

        $response->assertOk();
        $this->assertStringContainsString('text/csv', $response->headers->get('content-type'));
    }

    public function test_pdf_export_returns_pdf_file(): void
    {
        $user = User::factory()->create();
        $activity = Activity::factory()->create();

        ActivityUpdate::factory()->create([
            'activity_id' => $activity->id,
            'user_id' => $user->id,
            'activity_date' => now()->toDateString(),
        ]);

        $response = $this->actingAs($user)->get(route('reports.export.pdf'));

        $response->assertOk();
        $this->assertStringContainsString('pdf', $response->headers->get('content-type'));
    }

    public function test_unauthenticated_user_cannot_export_csv(): void
    {
        $response = $this->get(route('reports.export.csv'));

        $response->assertRedirect(route('login'));
    }

    public function test_unauthenticated_user_cannot_export_pdf(): void
    {
        $response = $this->get(route('reports.export.pdf'));

        $response->assertRedirect(route('login'));
    }
}

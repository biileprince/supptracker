<?php

namespace Database\Seeders;

use App\Models\Activity;
use App\Models\ActivityUpdate;
use App\Models\User;
use Illuminate\Database\Seeder;

class ActivitySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create an admin user
        $admin = User::factory()->admin()->create([
            'name' => 'Admin User',
            'email' => 'admin@supptracker.com',
            'employee_id' => 'EMP-0001',
            'job_title' => 'Team Lead',
            'department' => 'Application Support',
        ]);

        // Create team members
        $members = User::factory(4)->create();

        // Create realistic activities
        $activities = collect([
            ['title' => 'Daily SMS count vs. log comparison', 'description' => 'Compare the daily SMS count from the platform dashboard against the count from application logs to identify discrepancies.', 'category' => 'SMS'],
            ['title' => 'Server health check', 'description' => 'Verify CPU, memory, disk usage and service status across all production servers.', 'category' => 'Infrastructure'],
            ['title' => 'Ticket queue review', 'description' => 'Review all open support tickets, prioritize critical issues, and assign to team members.', 'category' => 'Support'],
            ['title' => 'Database backup verification', 'description' => 'Ensure all scheduled database backups completed successfully and verify backup integrity.', 'category' => 'Infrastructure'],
            ['title' => 'API response time monitoring', 'description' => 'Check API response times across all endpoints and flag any exceeding SLA thresholds.', 'category' => 'Performance'],
            ['title' => 'Error log review', 'description' => 'Analyze application error logs for new or recurring issues requiring attention.', 'category' => 'Monitoring'],
            ['title' => 'Scheduled job execution verification', 'description' => 'Verify all cron jobs and scheduled tasks executed as expected.', 'category' => 'Automation'],
            ['title' => 'User access audit', 'description' => 'Review user access permissions and deactivate stale accounts.', 'category' => 'Security'],
            ['title' => 'Platform uptime check', 'description' => 'Verify platform availability across all regions and document any downtime incidents.', 'category' => 'Infrastructure'],
            ['title' => 'Client escalation follow-up', 'description' => 'Follow up on all escalated client issues and provide status updates.', 'category' => 'Support'],
        ])->map(fn (array $data) => Activity::create($data));

        // Create activity updates for the past 7 days
        $allUsers = $members->push($admin);

        foreach (range(0, 6) as $daysAgo) {
            $date = now()->subDays($daysAgo)->toDateString();

            foreach ($activities as $activity) {
                // Each activity gets 1-3 updates per day from different team members
                $updaters = $allUsers->random(rand(1, 3));

                foreach ($updaters as $index => $user) {
                    ActivityUpdate::factory()->create([
                        'activity_id' => $activity->id,
                        'user_id' => $user->id,
                        'activity_date' => $date,
                        'status' => $index === $updaters->count() - 1
                            ? fake()->randomElement(['done', 'done', 'done', 'pending', 'in_progress'])
                            : fake()->randomElement(['pending', 'in_progress']),
                        'created_at' => now()->subDays($daysAgo)->addHours(rand(8, 17))->addMinutes(rand(0, 59)),
                    ]);
                }
            }
        }
    }
}

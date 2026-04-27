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
        $admin = User::updateOrCreate(
            ['email' => 'admin@supptracker.com'],
            [
                'name' => 'Amina Bello',
                'password' => 'password',
                'role' => 'admin',
                'employee_id' => 'EMP-0001',
                'job_title' => 'Support Team Lead',
                'department' => 'Application Support',
                'phone' => '+234 801 000 0001',
                'email_verified_at' => now(),
            ],
        );

        $teamMembers = collect([
            ['email' => 'chioma.adebayo@supptracker.com', 'name' => 'Chioma Adebayo', 'employee_id' => 'EMP-0002', 'job_title' => 'Application Support Analyst', 'department' => 'Application Support', 'phone' => '+234 801 000 0002'],
            ['email' => 'daniel.okoro@supptracker.com', 'name' => 'Daniel Okoro', 'employee_id' => 'EMP-0003', 'job_title' => 'Operations Analyst', 'department' => 'Operations', 'phone' => '+234 801 000 0003'],
            ['email' => 'fatima.sule@supptracker.com', 'name' => 'Fatima Sule', 'employee_id' => 'EMP-0004', 'job_title' => 'Infrastructure Engineer', 'department' => 'Infrastructure', 'phone' => '+234 801 000 0004'],
            ['email' => 'emeka.nwosu@supptracker.com', 'name' => 'Emeka Nwosu', 'employee_id' => 'EMP-0005', 'job_title' => 'Service Desk Analyst', 'department' => 'Support', 'phone' => '+234 801 000 0005'],
        ])->map(fn(array $data) => User::updateOrCreate(
            ['email' => $data['email']],
            array_merge($data, [
                'password' => 'password',
                'role' => 'member',
                'email_verified_at' => now(),
            ]),
        ));

        $activities = collect([
            [
                'title' => 'Daily SMS reconciliation',
                'description' => 'Compare the SMS totals from the delivery platform against the application audit log and reconcile any mismatches before 10:00 AM.',
                'category' => 'SMS',
            ],
            [
                'title' => 'Production server health check',
                'description' => 'Review CPU, memory, disk space, and service status across the production fleet and flag abnormal readings immediately.',
                'category' => 'Infrastructure',
            ],
            [
                'title' => 'Open ticket triage',
                'description' => 'Review newly assigned tickets, prioritise critical incidents, and assign follow-up owners for same-day resolution.',
                'category' => 'Support',
            ],
            [
                'title' => 'Database backup verification',
                'description' => 'Confirm scheduled backups completed successfully and sample-restore the latest backup to validate recoverability.',
                'category' => 'Infrastructure',
            ],
            [
                'title' => 'API latency review',
                'description' => 'Check API response times against the SLA baseline and escalate any endpoint that crosses the alert threshold.',
                'category' => 'Performance',
            ],
            [
                'title' => 'Application error log review',
                'description' => 'Scan application logs for repeat exceptions, trace the root cause, and log corrective actions for the next release.',
                'category' => 'Monitoring',
            ],
            [
                'title' => 'Scheduled job execution verification',
                'description' => 'Validate that all scheduled jobs ran successfully and investigate any missed or delayed job execution.',
                'category' => 'Automation',
            ],
            [
                'title' => 'User access audit',
                'description' => 'Review active user access, confirm role assignments, and disable stale accounts that no longer need access.',
                'category' => 'Security',
            ],
            [
                'title' => 'Platform uptime check',
                'description' => 'Verify uptime across the customer-facing portal and note any short outages, restarts, or degraded regions.',
                'category' => 'Infrastructure',
            ],
            [
                'title' => 'Escalation follow-up',
                'description' => 'Follow up on escalated incidents, capture customer updates, and ensure every escalation has a next action owner.',
                'category' => 'Support',
            ],
        ])->map(fn(array $data) => Activity::updateOrCreate(
            ['title' => $data['title']],
            array_merge($data, ['is_recurring' => true]),
        ));

        $allUsers = $teamMembers->prepend($admin)->values();
        $statusCycle = ['done', 'in_progress', 'done', 'done', 'pending', 'in_progress', 'done'];

        foreach (range(0, 6) as $daysAgo) {
            $activityDate = now()->subDays($daysAgo)->toDateString();

            $activities->values()->each(function (Activity $activity, int $activityIndex) use ($allUsers, $statusCycle, $activityDate, $daysAgo): void {
                $user = $allUsers->get(($activityIndex + $daysAgo) % $allUsers->count());
                $status = $statusCycle[($activityIndex + $daysAgo) % count($statusCycle)];
                $remark = match ($status) {
                    'done' => 'Completed and recorded in the daily tracker.',
                    'in_progress' => 'Work is underway and awaiting final validation.',
                    default => 'Queued for today and waiting on a resource owner.',
                };

                ActivityUpdate::updateOrCreate(
                    [
                        'activity_id' => $activity->id,
                        'user_id' => $user->id,
                        'activity_date' => $activityDate,
                    ],
                    [
                        'updater_name' => $user->name,
                        'updater_department' => $user->department,
                        'updater_job_title' => $user->job_title,
                        'status' => $status,
                        'remark' => $remark,
                        'created_at' => now()->subDays($daysAgo)->setTime(9 + (($activityIndex + $daysAgo) % 8), (($activityIndex * 7) + $daysAgo) % 60),
                        'updated_at' => now()->subDays($daysAgo)->setTime(9 + (($activityIndex + $daysAgo) % 8), (($activityIndex * 7) + $daysAgo) % 60),
                    ],
                );
            });
        }
    }
}

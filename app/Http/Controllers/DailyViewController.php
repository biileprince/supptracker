<?php

namespace App\Http\Controllers;

use App\Models\Activity;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DailyViewController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $dateString = $request->query('date') ?? now()->toDateString();

        try {
            $date = Carbon::parse($dateString);
        } catch (\Exception $e) {
            $date = now();
        }

        $activities = Activity::with(['updates' => function ($query) use ($date) {
            $query->whereDate('activity_date', $date->toDateString())
                ->with('user:id,name,avatar')
                ->latest();
        }])
            ->orderBy('category')
            ->orderBy('title')
            ->get();

        return Inertia::render('daily/index', [
            'activities' => $activities,
            'currentDate' => $date->toDateString(),
        ]);
    }
}

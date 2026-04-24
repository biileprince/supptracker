<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreActivityRequest;
use App\Http\Requests\UpdateActivityRequest;
use App\Models\Activity;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class ActivityController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(): Response
    {
        return Inertia::render('activities/index', [
            'activities' => Activity::orderBy('category')->orderBy('title')->get(),
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): Response
    {
        Gate::authorize('create', Activity::class);

        return Inertia::render('activities/create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreActivityRequest $request): RedirectResponse
    {
        Activity::create($request->validated());

        return to_route('activities.index')->with('success', 'Activity created successfully.');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Activity $activity): Response
    {
        Gate::authorize('update', $activity);

        return Inertia::render('activities/edit', [
            'activity' => $activity,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateActivityRequest $request, Activity $activity): RedirectResponse
    {
        $activity->update($request->validated());

        return to_route('activities.index')->with('success', 'Activity updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Activity $activity): RedirectResponse
    {
        Gate::authorize('delete', $activity);

        $activity->delete();

        return to_route('activities.index')->with('success', 'Activity deleted successfully.');
    }
}

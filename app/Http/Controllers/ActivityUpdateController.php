<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreActivityUpdateRequest;
use App\Models\ActivityUpdate;
use Illuminate\Http\RedirectResponse;

class ActivityUpdateController extends Controller
{
    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreActivityUpdateRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $user = $request->user();

        $validated['user_id'] = $user->id;
        $validated['updater_name'] = $user->name;
        $validated['updater_department'] = $user->department;
        $validated['updater_job_title'] = $user->job_title;

        ActivityUpdate::create($validated);

        return back()->with('success', 'Status updated successfully.');
    }
}

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
        $validated['user_id'] = $request->user()->id;

        ActivityUpdate::create($validated);

        return back()->with('success', 'Status updated successfully.');
    }
}

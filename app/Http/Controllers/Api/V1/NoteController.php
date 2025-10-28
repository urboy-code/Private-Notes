<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\StoreNoteRequest;
use App\Http\Requests\Api\V1\UpdateNoteRequest;
use App\Http\Resources\NoteResource;
use App\Models\Note;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class NoteController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        return NoteResource::collection($request->user()->notes()->latest()->paginate());
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreNoteRequest $request)
    {
        $validated = $request->validated();

        if (isset($validated['is_public']) && $validated['is_public']) {
            $validated['slug'] = Str::slug($validated['title']) . '-' . uniqid();
        }

        if (isset($validated['password'])) {
            $validated['password'] = bcrypt($validated['password']);
        }

        $note = $request->user()->notes()->create($validated);
        return new NoteResource($note);
    }

    /**
     * Display the specified resource.
     */
    public function show(Note $note)
    {
        if (request()->user()->id !== $note->user_id) {
            abort(403, 'Unauthorized action.');
        }
        return new NoteResource($note);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateNoteRequest $request, Note $note)
    {
        if (request()->user()->id !== $note->user_id) {
            abort(403, 'Unauthorized action.');
        }

        $validated = $request->validated();

        if (isset($validated['title']) && $note->is_public) {
            $validated['slug'] = Str::slug($validated['title']) . '-' . uniqid();
        }

        if (isset($validated['password'])) {
            $validated['password'] = bcrypt($validated['password']);
        } else {
            if (!isset($validated['is_public']) || !$validated['is_public']) {
                $validated['password'] = null;
            }
        }

        $note->update($validated);
        return new NoteResource($note);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Note $note)
    {
        if (request()->user()->id !== $note->user_id) {
            abort(403, 'Unauthorized action.');
        }
        $note->delete();
        return response()->noContent();
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\Trip;
use App\Models\TripFile;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class TripFileController extends Controller
{
    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'trip_id' => ['required', 'integer', 'exists:trips,id'],
            'file' => ['required', 'file'],
            'name' => ['sometimes', 'string', 'max:255'],
            'is_public' => ['sometimes', 'boolean'],
        ]);

        $trip = Trip::where('id', $validated['trip_id'])
            ->where('user_id', auth()->id())
            ->first();

        if (! $trip) {
            return response()->json([
                'message' => 'Trip not found or not owned by the current user.',
                'error' => true,
            ], 404);
        }

        $file = $request->file('file');
        $storedPath = $file->store('trip_files', 'local');

        $tripFile = TripFile::create([
            'trip_id' => $trip->id,
            'name' => $validated['name'] ?? pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME),
            'extension' => $file->extension(),
            'mime_type' => $file->getClientMimeType(),
            'url' => $storedPath,
            'is_public' => $validated['is_public'] ?? false,
        ]);

        return response()->json([
            'data' => $tripFile,
            'message' => 'File uploaded successfully.',
            'error' => false,
        ], 201);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, TripFile $tripFile): JsonResponse
    {
        if ($tripFile->trip->user_id !== auth()->id()) {
            return response()->json([
                'message' => 'You are not authorized to update this file.',
                'error' => true,
            ], 403);
        }

        $validated = $request->validate([
            'file' => ['sometimes', 'file'],
            'name' => ['sometimes', 'string', 'max:255'],
            'is_public' => ['sometimes', 'boolean'],
        ]);

        if ($request->hasFile('file')) {
            Storage::disk('local')->delete($tripFile->url);

            $file = $request->file('file');
            $storedPath = $file->store('trip_files', 'local');

            $tripFile->url = $storedPath;
            $tripFile->extension = $file->extension();
            $tripFile->mime_type = $file->getClientMimeType();

            if (! isset($validated['name'])) {
                $tripFile->name = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
            }
        }

        if (isset($validated['name'])) {
            $tripFile->name = $validated['name'];
        }

        if (isset($validated['is_public'])) {
            $tripFile->is_public = $validated['is_public'];
        }

        $tripFile->save();

        return response()->json([
            'data' => $tripFile,
            'message' => 'File updated successfully.',
            'error' => false,
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(TripFile $tripFile): JsonResponse
    {
        if ($tripFile->trip->user_id !== auth()->id()) {
            return response()->json([
                'message' => 'You are not authorized to delete this file.',
                'error' => true,
            ], 403);
        }

        Storage::disk('local')->delete($tripFile->url);
        $tripFile->delete();

        return response()->json([
            'message' => 'File deleted successfully.',
            'error' => false,
        ]);
    }
}

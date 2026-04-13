<?php

namespace App\Http\Controllers;

use App\Models\Activity;
use App\Models\Trip;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ActivityController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(): JsonResponse
    {
        try {
            $activities = Activity::whereHas('trip', function ($query) {
                $query->where('user_id', auth()->id());
            })->get();

            return response()->json([
                'data' => $activities,
                'action' => self::class . '@index',
                'error' => false,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'An error occurred while fetching activities.',
                'action' => self::class . '@index',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'trip_id' => ['required', 'integer', 'exists:trips,id'],
            'title' => ['required', 'string', 'max:255'],
            'location_url' => ['nullable', 'string', 'max:2048'],
            'description' => ['nullable', 'string'],
            'day' => ['required', 'integer', 'min:1'],
            'price' => ['required', 'numeric', 'min:0'],
            'time_start' => ['required', 'date_format:Y-m-d H:i:s'],
        ]);

        $trip = Trip::where('id', $validated['trip_id'])
            ->where('user_id', auth()->id())
            ->first();

        if (! $trip) {
            return response()->json([
                'message' => 'Trip not found or not owned by the current user.',
                'action' => self::class . '@store',
                'error' => true,
            ], 404);
        }

        $activity = Activity::create($validated);

        return response()->json([
            'data' => $activity,
            'action' => self::class . '@store',
            'error' => false,
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Activity $activity): JsonResponse
    {
        if ($activity->trip->user_id !== auth()->id()) {
            return response()->json([
                'message' => 'You are not authorized to view this activity.',
                'action' => self::class . '@show',
                'error' => true,
            ], 403);
        }

        return response()->json([
            'data' => $activity,
            'action' => self::class . '@show',
            'error' => false,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Activity $activity): JsonResponse
    {
        if ($activity->trip->user_id !== auth()->id()) {
            return response()->json([
                'message' => 'You are not authorized to update this activity.',
                'action' => self::class . '@update',
                'error' => true,
            ], 403);
        }

        $validated = $request->validate([
            'trip_id' => ['sometimes', 'integer', 'exists:trips,id'],
            'title' => ['sometimes', 'required', 'string', 'max:255'],
            'location_url' => ['sometimes', 'nullable', 'string', 'max:2048'],
            'description' => ['sometimes', 'nullable', 'string'],
            'day' => ['sometimes', 'required', 'integer', 'min:1'],
            'price' => ['sometimes', 'required', 'numeric', 'min:0'],
            'time_start' => ['sometimes', 'required', 'date_format:Y-m-d H:i:s'],
        ]);

        if (isset($validated['trip_id'])) {
            $trip = Trip::where('id', $validated['trip_id'])
                ->where('user_id', auth()->id())
                ->first();

            if (! $trip) {
                return response()->json([
                    'message' => 'Trip not found or not owned by the current user.',
                    'action' => self::class . '@update',
                    'error' => true,
                ], 404);
            }
        }

        $activity->update($validated);

        return response()->json([
            'data' => $activity,
            'action' => self::class . '@update',
            'error' => false,
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Activity $activity): JsonResponse
    {
        if ($activity->trip->user_id !== auth()->id()) {
            return response()->json([
                'message' => 'You are not authorized to delete this activity.',
                'action' => self::class . '@destroy',
                'error' => true,
            ], 403);
        }

        $activity->delete();

        return response()->json([
            'message' => 'Activity deleted successfully.',
            'action' => self::class . '@destroy',
            'error' => false,
        ]);
    }
}

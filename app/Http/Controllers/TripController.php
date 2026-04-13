<?php

namespace App\Http\Controllers;

use App\Models\Trip;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class TripController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(): JsonResponse
    {
        try {
            $trips = Trip::where('user_id', auth()->id())->get();

            return response()->json([
                'data' => $trips,
                'action' => self::class . '@index',
                'error' => false,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'An error occurred while fetching trips.',
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
            'title' => ['required', 'string', 'max:255'],
            'destination' => ['required', 'string', 'max:255'],
            'image' => ['required', 'string', 'max:2048'],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'budget' => ['required', 'numeric', 'min:0'],
        ]);

        $trip = Trip::create(array_merge($validated, [
            'user_id' => auth()->id(),
            'uuid' => Str::uuid()->toString(),
        ]));

        return response()->json([
            'data' => $trip,
            'action' => self::class . '@store',
            'error' => false,
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Trip $trip): JsonResponse
    {
        if ($trip->user_id !== auth()->id()) {
            return response()->json([
                'message' => 'You are not authorized to view this trip.',
                'action' => self::class . '@show',
                'error' => true,
            ], 403);
        }

        return response()->json([
            'data' => $trip,
            'action' => self::class . '@show',
            'error' => false,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Trip $trip): JsonResponse
    {
        if ($trip->user_id !== auth()->id()) {
            return response()->json([
                'message' => 'You are not authorized to update this trip.',
                'action' => self::class . '@update',
                'error' => true,
            ], 403);
        }

        $validated = $request->validate([
            'title' => ['sometimes', 'required', 'string', 'max:255'],
            'destination' => ['sometimes', 'required', 'string', 'max:255'],
            'image' => ['sometimes', 'required', 'string', 'max:2048'],
            'start_date' => ['sometimes', 'required', 'date'],
            'end_date' => ['sometimes', 'required', 'date', 'after_or_equal:start_date'],
            'budget' => ['sometimes', 'required', 'numeric', 'min:0'],
        ]);

        $trip->update($validated);

        return response()->json([
            'data' => $trip,
            'action' => self::class . '@update',
            'error' => false,
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Trip $trip): JsonResponse
    {
        if ($trip->user_id !== auth()->id()) {
            return response()->json([
                'message' => 'You are not authorized to delete this trip.',
                'action' => self::class . '@destroy',
                'error' => true,
            ], 403);
        }

        $trip->delete();

        return response()->json([
            'message' => 'Trip deleted successfully.',
            'action' => self::class . '@destroy',
            'error' => false,
        ]);
    }
}

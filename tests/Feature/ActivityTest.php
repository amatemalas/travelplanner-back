<?php

namespace Tests\Feature;

use App\Models\Activity;
use App\Models\Trip;
use App\Models\User;
use Illuminate\Support\Str;
use Tests\TestCase;

class ActivityTest extends TestCase
{
    private function createTripForUser(User $user): Trip
    {
        return Trip::create([
            'user_id' => $user->id,
            'uuid' => Str::uuid(),
            'title' => 'Test Trip',
            'destination' => 'Paris',
            'image' => '',
            'start_date' => '2025-06-01',
            'end_date' => '2025-06-10',
            'budget' => 1500,
        ]);
    }

    private function validActivityData(Trip $trip): array
    {
        return [
            'trip_id' => $trip->id,
            'title' => 'Visit Eiffel Tower',
            'location_url' => 'https://example.com',
            'description' => 'A great visit',
            'day' => 1,
            'price' => 25.50,
            'time_start' => '2025-06-01 10:00:00',
        ];
    }

    public function test_user_can_list_own_activities(): void
    {
        $user = $this->authenticate();
        $trip = $this->createTripForUser($user);

        Activity::create($this->validActivityData($trip));

        $response = $this->getJson('/api/activities');

        $response->assertStatus(200)
            ->assertJsonPath('error', false)
            ->assertJsonCount(1, 'data');
    }

    public function test_user_only_sees_own_activities(): void
    {
        $user = $this->authenticate();
        $otherUser = User::factory()->create();
        $otherTrip = $this->createTripForUser($otherUser);

        Activity::create($this->validActivityData($otherTrip));

        $response = $this->getJson('/api/activities');

        $response->assertStatus(200)
            ->assertJsonCount(0, 'data');
    }

    public function test_user_can_create_activity(): void
    {
        $user = $this->authenticate();
        $trip = $this->createTripForUser($user);

        $response = $this->postJson('/api/activities', $this->validActivityData($trip));

        $response->assertStatus(201)
            ->assertJsonPath('error', false)
            ->assertJsonPath('data.title', 'Visit Eiffel Tower')
            ->assertJsonPath('data.trip_id', $trip->id);
    }

    public function test_user_cannot_create_activity_for_others_trip(): void
    {
        $this->authenticate();
        $otherUser = User::factory()->create();
        $otherTrip = $this->createTripForUser($otherUser);

        $response = $this->postJson('/api/activities', $this->validActivityData($otherTrip));

        $response->assertStatus(404)
            ->assertJsonPath('error', true);
    }

    public function test_user_can_view_own_activity(): void
    {
        $user = $this->authenticate();
        $trip = $this->createTripForUser($user);
        $activity = Activity::create($this->validActivityData($trip));

        $response = $this->getJson("/api/activities/{$activity->id}");

        $response->assertStatus(200)
            ->assertJsonPath('error', false)
            ->assertJsonPath('data.id', $activity->id);
    }

    public function test_user_cannot_view_others_activity(): void
    {
        $this->authenticate();
        $otherUser = User::factory()->create();
        $otherTrip = $this->createTripForUser($otherUser);
        $activity = Activity::create($this->validActivityData($otherTrip));

        $response = $this->getJson("/api/activities/{$activity->id}");

        $response->assertStatus(403)
            ->assertJsonPath('error', true);
    }

    public function test_user_can_update_own_activity(): void
    {
        $user = $this->authenticate();
        $trip = $this->createTripForUser($user);
        $activity = Activity::create($this->validActivityData($trip));

        $response = $this->putJson("/api/activities/{$activity->id}", [
            'title' => 'Updated Activity',
            'price' => 30,
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('error', false)
            ->assertJsonPath('data.title', 'Updated Activity');

        $this->assertEquals('Updated Activity', $activity->fresh()->title);
    }

    public function test_user_cannot_update_others_activity(): void
    {
        $this->authenticate();
        $otherUser = User::factory()->create();
        $otherTrip = $this->createTripForUser($otherUser);
        $activity = Activity::create($this->validActivityData($otherTrip));

        $response = $this->putJson("/api/activities/{$activity->id}", [
            'title' => 'Hacked',
        ]);

        $response->assertStatus(403)
            ->assertJsonPath('error', true);
    }

    public function test_user_can_delete_own_activity(): void
    {
        $user = $this->authenticate();
        $trip = $this->createTripForUser($user);
        $activity = Activity::create($this->validActivityData($trip));

        $response = $this->deleteJson("/api/activities/{$activity->id}");

        $response->assertStatus(200)
            ->assertJsonPath('error', false);

        $this->assertModelMissing($activity);
    }

    public function test_user_cannot_delete_others_activity(): void
    {
        $this->authenticate();
        $otherUser = User::factory()->create();
        $otherTrip = $this->createTripForUser($otherUser);
        $activity = Activity::create($this->validActivityData($otherTrip));

        $response = $this->deleteJson("/api/activities/{$activity->id}");

        $response->assertStatus(403)
            ->assertJsonPath('error', true);

        $this->assertModelExists($activity);
    }

    public function test_create_activity_validates_required_fields(): void
    {
        $this->authenticate();

        $response = $this->postJson('/api/activities', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['trip_id', 'title', 'day', 'price', 'time_start']);
    }
}

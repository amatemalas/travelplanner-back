<?php

namespace Tests\Feature;

use App\Models\Trip;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

class TripTest extends TestCase
{
    public function test_user_can_list_own_trips(): void
    {
        $user = $this->authenticate();

        Trip::create([
            'user_id' => $user->id,
            'uuid' => Str::uuid(),
            'title' => 'My Trip',
            'destination' => 'Paris',
            'image' => '',
            'start_date' => '2025-06-01',
            'end_date' => '2025-06-10',
            'budget' => 1500,
        ]);

        $response = $this->getJson('/api/trips');

        $response->assertStatus(200)
            ->assertJsonPath('error', false)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.title', 'My Trip');
    }

    public function test_user_only_sees_own_trips(): void
    {
        $user = $this->authenticate();

        $otherUser = User::factory()->create();
        Trip::create([
            'user_id' => $otherUser->id,
            'uuid' => Str::uuid(),
            'title' => 'Other Trip',
            'destination' => 'Berlin',
            'image' => '',
            'start_date' => '2025-06-01',
            'end_date' => '2025-06-10',
            'budget' => 1000,
        ]);

        $response = $this->getJson('/api/trips');

        $response->assertStatus(200)
            ->assertJsonCount(0, 'data');
    }

    public function test_user_can_create_trip(): void
    {
        $this->authenticate();

        Storage::fake('local');

        $response = $this->post('/api/trips', [
            'title' => 'Summer Vacation',
            'destination' => 'Barcelona',
            'image' => UploadedFile::fake()->image('vacation.jpg'),
            'start_date' => '2025-07-01',
            'end_date' => '2025-07-15',
            'budget' => 2000,
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('error', false)
            ->assertJsonStructure([
                'data' => ['id', 'uuid', 'title', 'destination', 'start_date', 'end_date', 'budget'],
                'action',
                'error',
            ])
            ->assertJsonPath('data.title', 'Summer Vacation');
    }

    public function test_user_can_view_own_trip(): void
    {
        $user = $this->authenticate();

        $trip = Trip::create([
            'user_id' => $user->id,
            'uuid' => Str::uuid(),
            'title' => 'My Trip',
            'destination' => 'Rome',
            'image' => '',
            'start_date' => '2025-08-01',
            'end_date' => '2025-08-10',
            'budget' => 1200,
        ]);

        $response = $this->getJson("/api/trips/{$trip->id}");

        $response->assertStatus(200)
            ->assertJsonPath('error', false)
            ->assertJsonPath('data.id', $trip->id)
            ->assertJsonPath('data.title', 'My Trip');
    }

    public function test_user_cannot_view_others_trip(): void
    {
        $this->authenticate();

        $otherUser = User::factory()->create();
        $trip = Trip::create([
            'user_id' => $otherUser->id,
            'uuid' => Str::uuid(),
            'title' => 'Others Trip',
            'destination' => 'Tokyo',
            'image' => '',
            'start_date' => '2025-09-01',
            'end_date' => '2025-09-10',
            'budget' => 3000,
        ]);

        $response = $this->getJson("/api/trips/{$trip->id}");

        $response->assertStatus(403)
            ->assertJsonPath('error', true);
    }

    public function test_user_can_update_own_trip(): void
    {
        $user = $this->authenticate();

        $trip = Trip::create([
            'user_id' => $user->id,
            'uuid' => Str::uuid(),
            'title' => 'Old Title',
            'destination' => 'Paris',
            'image' => '',
            'start_date' => '2025-06-01',
            'end_date' => '2025-06-10',
            'budget' => 1500,
        ]);

        $response = $this->putJson("/api/trips/{$trip->id}", [
            'title' => 'Updated Title',
            'destination' => 'Lyon',
            'start_date' => '2025-06-05',
            'end_date' => '2025-06-15',
            'budget' => 1800,
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('error', false)
            ->assertJsonPath('data.title', 'Updated Title')
            ->assertJsonPath('data.destination', 'Lyon');

        $this->assertEquals('Updated Title', $trip->fresh()->title);
    }

    public function test_user_cannot_update_others_trip(): void
    {
        $this->authenticate();

        $otherUser = User::factory()->create();
        $trip = Trip::create([
            'user_id' => $otherUser->id,
            'uuid' => Str::uuid(),
            'title' => 'Others Trip',
            'destination' => 'Berlin',
            'image' => '',
            'start_date' => '2025-06-01',
            'end_date' => '2025-06-10',
            'budget' => 1000,
        ]);

        $response = $this->putJson("/api/trips/{$trip->id}", [
            'title' => 'Hacked Title',
        ]);

        $response->assertStatus(403)
            ->assertJsonPath('error', true);
    }

    public function test_user_can_delete_own_trip(): void
    {
        $user = $this->authenticate();

        $trip = Trip::create([
            'user_id' => $user->id,
            'uuid' => Str::uuid(),
            'title' => 'To Delete',
            'destination' => 'Madrid',
            'image' => '',
            'start_date' => '2025-10-01',
            'end_date' => '2025-10-05',
            'budget' => 800,
        ]);

        $response = $this->deleteJson("/api/trips/{$trip->id}");

        $response->assertStatus(200)
            ->assertJsonPath('error', false);

        $this->assertModelMissing($trip);
    }

    public function test_user_cannot_delete_others_trip(): void
    {
        $this->authenticate();

        $otherUser = User::factory()->create();
        $trip = Trip::create([
            'user_id' => $otherUser->id,
            'uuid' => Str::uuid(),
            'title' => 'Not Mine',
            'destination' => 'Lisbon',
            'image' => '',
            'start_date' => '2025-11-01',
            'end_date' => '2025-11-05',
            'budget' => 900,
        ]);

        $response = $this->deleteJson("/api/trips/{$trip->id}");

        $response->assertStatus(403)
            ->assertJsonPath('error', true);

        $this->assertModelExists($trip);
    }

    public function test_create_trip_validates_required_fields(): void
    {
        $this->authenticate();

        $response = $this->postJson('/api/trips', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['title', 'destination', 'image', 'start_date', 'end_date', 'budget']);
    }
}

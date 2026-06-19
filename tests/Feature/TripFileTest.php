<?php

namespace Tests\Feature;

use App\Models\Trip;
use App\Models\TripFile;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

class TripFileTest extends TestCase
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

    public function test_user_can_upload_file(): void
    {
        $user = $this->authenticate();
        $trip = $this->createTripForUser($user);

        Storage::fake('local');

        $response = $this->post('/api/trip-files', [
            'trip_id' => $trip->id,
            'file' => UploadedFile::fake()->create('document.pdf', 100),
            'name' => 'My Document',
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('error', false)
            ->assertJsonStructure([
                'data' => ['id', 'trip_id', 'name', 'extension', 'mime_type', 'url', 'is_public'],
            ])
            ->assertJsonPath('data.name', 'My Document')
            ->assertJsonPath('data.extension', 'pdf');
    }

    public function test_user_cannot_upload_file_to_others_trip(): void
    {
        $this->authenticate();
        $otherUser = User::factory()->create();
        $otherTrip = $this->createTripForUser($otherUser);

        $response = $this->post('/api/trip-files', [
            'trip_id' => $otherTrip->id,
            'file' => UploadedFile::fake()->create('doc.pdf', 100),
        ]);

        $response->assertStatus(404)
            ->assertJsonPath('error', true);
    }

    public function test_user_can_update_own_file(): void
    {
        $user = $this->authenticate();
        $trip = $this->createTripForUser($user);

        Storage::fake('local');

        $tripFile = TripFile::create([
            'trip_id' => $trip->id,
            'name' => 'Old Name',
            'extension' => 'txt',
            'mime_type' => 'text/plain',
            'url' => 'trip_files/old.txt',
            'is_public' => false,
        ]);

        $response = $this->putJson("/api/trip-files/{$tripFile->id}", [
            'name' => 'Updated Name',
            'is_public' => true,
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('error', false)
            ->assertJsonPath('data.name', 'Updated Name')
            ->assertJsonPath('data.is_public', true);

        $fresh = $tripFile->fresh();
        $this->assertEquals('Updated Name', $fresh->name);
        $this->assertTrue($fresh->is_public);
    }

    public function test_user_can_replace_file(): void
    {
        $user = $this->authenticate();
        $trip = $this->createTripForUser($user);

        Storage::fake('local');

        $tripFile = TripFile::create([
            'trip_id' => $trip->id,
            'name' => 'Old File',
            'extension' => 'txt',
            'mime_type' => 'text/plain',
            'url' => 'trip_files/old.txt',
            'is_public' => false,
        ]);

        $response = $this->put('/api/trip-files/' . $tripFile->id, [
            'file' => UploadedFile::fake()->create('new.pdf', 200),
            'name' => 'New File',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('error', false)
            ->assertJsonPath('data.name', 'New File')
            ->assertJsonPath('data.extension', 'pdf');
    }

    public function test_user_cannot_update_others_file(): void
    {
        $user = $this->authenticate();
        $otherUser = User::factory()->create();
        $otherTrip = $this->createTripForUser($otherUser);

        $tripFile = TripFile::create([
            'trip_id' => $otherTrip->id,
            'name' => 'Their File',
            'extension' => 'txt',
            'mime_type' => 'text/plain',
            'url' => 'trip_files/their.txt',
            'is_public' => false,
        ]);

        $response = $this->putJson("/api/trip-files/{$tripFile->id}", [
            'name' => 'Hacked',
        ]);

        $response->assertStatus(403)
            ->assertJsonPath('error', true);
    }

    public function test_user_can_delete_own_file(): void
    {
        $user = $this->authenticate();
        $trip = $this->createTripForUser($user);

        Storage::fake('local');

        $tripFile = TripFile::create([
            'trip_id' => $trip->id,
            'name' => 'To Delete',
            'extension' => 'txt',
            'mime_type' => 'text/plain',
            'url' => 'trip_files/to-delete.txt',
            'is_public' => false,
        ]);

        $response = $this->deleteJson("/api/trip-files/{$tripFile->id}");

        $response->assertStatus(200)
            ->assertJsonPath('error', false);

        $this->assertModelMissing($tripFile);
    }

    public function test_user_cannot_delete_others_file(): void
    {
        $user = $this->authenticate();
        $otherUser = User::factory()->create();
        $otherTrip = $this->createTripForUser($otherUser);

        $tripFile = TripFile::create([
            'trip_id' => $otherTrip->id,
            'name' => 'Not Mine',
            'extension' => 'txt',
            'mime_type' => 'text/plain',
            'url' => 'trip_files/not-mine.txt',
            'is_public' => false,
        ]);

        $response = $this->deleteJson("/api/trip-files/{$tripFile->id}");

        $response->assertStatus(403)
            ->assertJsonPath('error', true);

        $this->assertModelExists($tripFile);
    }

    public function test_upload_validates_required_fields(): void
    {
        $this->authenticate();

        $response = $this->postJson('/api/trip-files', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['trip_id', 'file']);
    }
}

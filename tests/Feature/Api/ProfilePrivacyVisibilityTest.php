<?php

namespace Tests\Feature\Api;

use App\Enums\ConnectionRequestStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Models\ConnectionRequest;
use App\Models\Payment;
use App\Models\ProfilePhoto;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\Feature\Api\Concerns\CreatesMatchmakingData;
use Tests\TestCase;

class ProfilePrivacyVisibilityTest extends TestCase
{
    use CreatesMatchmakingData;
    use RefreshDatabase;

    public function test_browse_masks_sensitive_fields_before_connection_is_confirmed(): void
    {
        $location = $this->seedLocationData();

        $viewer = User::factory()->create();
        Sanctum::actingAs($viewer);

        $owner = User::factory()->create([
            'phone' => '0712004000',
            'email' => 'hidden-owner@example.com',
        ]);

        $profile = $this->createProfileFor(
            $owner,
            $location['tz'],
            $location['region'],
            $location['district'],
            [
                'display_name' => 'Visible Name',
                'about_me' => 'This is a long profile bio that should still be present in short form before unlock.',
            ]
        );

        ProfilePhoto::query()->create([
            'user_id' => $owner->id,
            'profile_id' => $profile->id,
            'path' => "profile-photos/{$owner->id}/photo-1.jpg",
            'is_primary' => true,
            'sort_order' => 0,
        ]);

        $response = $this->getJson('/api/browse/'.$profile->id)
            ->assertOk()
            ->assertJsonPath('data.is_sensitive_unlocked', false)
            ->assertJsonPath('data.display_name', 'Private Member')
            ->assertJsonPath('data.sensitive_lock_message', 'Photos, name, and contact details unlock after both matched users complete and confirm payment.')
            ->assertJsonPath('data.photos', [])
            ->assertJsonPath('data.primary_photo', null)
            ->assertJsonPath('data.user.phone', null)
            ->assertJsonPath('data.user.email', null);

        $this->assertSame('christian', $response->json('data.religion'));
        $this->assertSame('single', $response->json('data.marital_status'));
        $this->assertNotEmpty($response->json('data.about_me'));
    }

    public function test_browse_reveals_sensitive_fields_after_confirmed_connection(): void
    {
        $location = $this->seedLocationData();

        $viewer = User::factory()->create();
        Sanctum::actingAs($viewer);

        $owner = User::factory()->create([
            'phone' => '0712005000',
            'email' => 'revealed-owner@example.com',
        ]);

        $profile = $this->createProfileFor(
            $owner,
            $location['tz'],
            $location['region'],
            $location['district'],
            [
                'display_name' => 'Revealed Name',
                'about_me' => 'Unlocked bio',
            ]
        );

        $photo = ProfilePhoto::query()->create([
            'user_id' => $owner->id,
            'profile_id' => $profile->id,
            'path' => "profile-photos/{$owner->id}/photo-1.jpg",
            'is_primary' => true,
            'sort_order' => 0,
        ]);

        $connectionRequest = ConnectionRequest::query()->create([
            'sender_id' => $viewer->id,
            'receiver_id' => $owner->id,
            'status' => ConnectionRequestStatus::Connected->value,
            'connected_at' => Carbon::now(),
        ]);

        Payment::query()->create([
            'connection_request_id' => $connectionRequest->id,
            'payer_id' => $viewer->id,
            'amount' => 20,
            'method' => PaymentMethod::MobileMoney->value,
            'reference' => 'TEST-REF-10001',
            'status' => PaymentStatus::Confirmed->value,
            'confirmed_by' => null,
            'confirmed_at' => Carbon::now(),
        ]);

        Payment::query()->create([
            'connection_request_id' => $connectionRequest->id,
            'payer_id' => $owner->id,
            'amount' => 20,
            'method' => PaymentMethod::MobileMoney->value,
            'reference' => 'TEST-REF-10002',
            'status' => PaymentStatus::Confirmed->value,
            'confirmed_by' => null,
            'confirmed_at' => Carbon::now(),
        ]);

        $this->getJson('/api/browse/'.$profile->id)
            ->assertOk()
            ->assertJsonPath('data.is_sensitive_unlocked', true)
            ->assertJsonPath('data.display_name', 'Revealed Name')
            ->assertJsonPath('data.primary_photo.path', '/api/profile-photos/'.$photo->id)
            ->assertJsonPath('data.user.phone', '0712005000')
            ->assertJsonPath('data.user.email', 'revealed-owner@example.com');
    }

    public function test_profile_photo_endpoint_blocks_unmatched_users_and_allows_confirmed_matches(): void
    {
        Storage::fake('local');

        $location = $this->seedLocationData();

        $viewer = User::factory()->create();
        Sanctum::actingAs($viewer);

        $owner = User::factory()->create();
        $profile = $this->createProfileFor($owner, $location['tz'], $location['region'], $location['district']);

        $path = "profile-photos/{$owner->id}/secured-photo.jpg";
        Storage::disk('local')->put($path, 'fake-image-data');

        $photo = ProfilePhoto::query()->create([
            'user_id' => $owner->id,
            'profile_id' => $profile->id,
            'path' => $path,
            'is_primary' => true,
            'sort_order' => 0,
        ]);

        $this->get('/api/profile-photos/'.$photo->id)
            ->assertStatus(403);

        $connectionRequest = ConnectionRequest::query()->create([
            'sender_id' => $viewer->id,
            'receiver_id' => $owner->id,
            'status' => ConnectionRequestStatus::Connected->value,
            'connected_at' => Carbon::now(),
        ]);

        Payment::query()->create([
            'connection_request_id' => $connectionRequest->id,
            'payer_id' => $viewer->id,
            'amount' => 20,
            'method' => PaymentMethod::MobileMoney->value,
            'reference' => 'TEST-REF-10003',
            'status' => PaymentStatus::Confirmed->value,
            'confirmed_by' => null,
            'confirmed_at' => Carbon::now(),
        ]);

        Payment::query()->create([
            'connection_request_id' => $connectionRequest->id,
            'payer_id' => $owner->id,
            'amount' => 20,
            'method' => PaymentMethod::MobileMoney->value,
            'reference' => 'TEST-REF-10004',
            'status' => PaymentStatus::Confirmed->value,
            'confirmed_by' => null,
            'confirmed_at' => Carbon::now(),
        ]);

        $this->get('/api/profile-photos/'.$photo->id)
            ->assertOk();
    }

    public function test_browse_detail_includes_connection_context_for_active_request(): void
    {
        $location = $this->seedLocationData();

        $viewer = User::factory()->create();
        Sanctum::actingAs($viewer);

        $owner = User::factory()->create();
        $profile = $this->createProfileFor($owner, $location['tz'], $location['region'], $location['district']);

        $connection = ConnectionRequest::query()->create([
            'sender_id' => $viewer->id,
            'receiver_id' => $owner->id,
            'status' => ConnectionRequestStatus::Pending->value,
            'message' => 'Hello there',
        ]);

        $this->getJson('/api/browse/'.$profile->id)
            ->assertOk()
            ->assertJsonPath('data.connection.can_send_request', false)
            ->assertJsonPath('data.connection.has_active_request', true)
            ->assertJsonPath('data.connection.request.id', $connection->id)
            ->assertJsonPath('data.connection.request.status', ConnectionRequestStatus::Pending->value)
            ->assertJsonPath('data.connection.request.can_cancel', true)
            ->assertJsonPath('data.connection.request.can_pay', false);
    }
}

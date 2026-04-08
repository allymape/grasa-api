<?php

namespace Tests\Feature\Api;

use App\Enums\ConnectionRequestStatus;
use App\Enums\ProfileApprovalStatus;
use App\Models\ConnectionRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\Feature\Api\Concerns\CreatesMatchmakingData;
use Tests\TestCase;

class ConnectionRequestFlowTest extends TestCase
{
    use CreatesMatchmakingData;
    use RefreshDatabase;

    public function test_user_cannot_send_request_to_self_and_duplicate_active_is_blocked(): void
    {
        $location = $this->seedLocationData();

        $sender = User::factory()->create();
        $receiver = User::factory()->create();

        $senderProfile = $this->createProfileFor(
            $sender,
            $location['tz'],
            $location['region'],
            $location['district'],
            ['approval_status' => ProfileApprovalStatus::Approved->value, 'is_visible' => true]
        );
        $receiverProfile = $this->createProfileFor(
            $receiver,
            $location['tz'],
            $location['region'],
            $location['district'],
            ['approval_status' => ProfileApprovalStatus::Approved->value, 'is_visible' => true]
        );

        Sanctum::actingAs($sender);

        $this->postJson('/api/connection-requests', [
            'receiver_profile_id' => $senderProfile->id,
        ])->assertStatus(422);

        $create = $this->postJson('/api/connection-requests', [
            'receiver_profile_id' => $receiverProfile->id,
            'message' => 'Interested in connecting',
        ])->assertCreated();

        $requestId = (int) $create->json('data.id');
        $this->assertGreaterThan(0, $requestId);

        $this->assertDatabaseHas('connection_requests', [
            'id' => $requestId,
            'sender_id' => $sender->id,
            'receiver_id' => $receiver->id,
            'status' => ConnectionRequestStatus::Pending->value,
        ]);

        $this->postJson('/api/connection-requests', [
            'receiver_profile_id' => $receiverProfile->id,
        ])->assertStatus(422);
    }

    public function test_only_intended_receiver_can_accept_or_reject(): void
    {
        $location = $this->seedLocationData();

        $sender = User::factory()->create();
        $receiver = User::factory()->create();
        $otherUser = User::factory()->create();

        $receiverProfile = $this->createProfileFor(
            $receiver,
            $location['tz'],
            $location['region'],
            $location['district'],
            ['approval_status' => ProfileApprovalStatus::Approved->value, 'is_visible' => true]
        );

        $this->createProfileFor(
            $sender,
            $location['tz'],
            $location['region'],
            $location['district'],
            ['approval_status' => ProfileApprovalStatus::Approved->value, 'is_visible' => true]
        );

        Sanctum::actingAs($sender);
        $requestId = (int) $this->postJson('/api/connection-requests', [
            'receiver_profile_id' => $receiverProfile->id,
        ])->json('data.id');

        Sanctum::actingAs($otherUser);
        $this->patchJson('/api/connection-requests/'.$requestId.'/accept')
            ->assertStatus(403);

        Sanctum::actingAs($receiver);
        $this->patchJson('/api/connection-requests/'.$requestId.'/accept')
            ->assertOk()
            ->assertJsonPath('data.status', ConnectionRequestStatus::PaymentPending->value);

        $this->patchJson('/api/connection-requests/'.$requestId.'/reject')
            ->assertStatus(422);

        $this->assertDatabaseHas('connection_requests', [
            'id' => $requestId,
            'status' => ConnectionRequestStatus::PaymentPending->value,
        ]);

        $this->assertNotNull(ConnectionRequest::query()->find($requestId));
    }

    public function test_sender_can_cancel_pending_request_but_not_non_pending_or_other_users_request(): void
    {
        $location = $this->seedLocationData();

        $sender = User::factory()->create();
        $receiver = User::factory()->create();
        $otherUser = User::factory()->create();

        $receiverProfile = $this->createProfileFor(
            $receiver,
            $location['tz'],
            $location['region'],
            $location['district'],
            ['approval_status' => ProfileApprovalStatus::Approved->value, 'is_visible' => true]
        );

        $this->createProfileFor(
            $sender,
            $location['tz'],
            $location['region'],
            $location['district'],
            ['approval_status' => ProfileApprovalStatus::Approved->value, 'is_visible' => true]
        );

        Sanctum::actingAs($sender);
        $requestId = (int) $this->postJson('/api/connection-requests', [
            'receiver_profile_id' => $receiverProfile->id,
        ])->json('data.id');

        Sanctum::actingAs($otherUser);
        $this->patchJson('/api/connection-requests/'.$requestId.'/cancel')
            ->assertStatus(403);

        Sanctum::actingAs($sender);
        $this->patchJson('/api/connection-requests/'.$requestId.'/cancel')
            ->assertOk()
            ->assertJsonPath('data.status', ConnectionRequestStatus::Cancelled->value)
            ->assertJsonPath('data.can_cancel', false);

        $this->assertDatabaseHas('connection_requests', [
            'id' => $requestId,
            'status' => ConnectionRequestStatus::Cancelled->value,
        ]);

        $secondRequestId = (int) $this->postJson('/api/connection-requests', [
            'receiver_profile_id' => $receiverProfile->id,
        ])->json('data.id');

        Sanctum::actingAs($receiver);
        $this->patchJson('/api/connection-requests/'.$secondRequestId.'/accept')
            ->assertOk()
            ->assertJsonPath('data.status', ConnectionRequestStatus::PaymentPending->value);

        Sanctum::actingAs($sender);
        $this->patchJson('/api/connection-requests/'.$secondRequestId.'/cancel')
            ->assertStatus(422);
    }
}

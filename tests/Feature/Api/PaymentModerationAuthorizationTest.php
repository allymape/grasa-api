<?php

namespace Tests\Feature\Api;

use App\Enums\ConnectionRequestStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Enums\ProfileApprovalStatus;
use App\Models\ConnectionRequest;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\Feature\Api\Concerns\CreatesMatchmakingData;
use Tests\TestCase;

class PaymentModerationAuthorizationTest extends TestCase
{
    use CreatesMatchmakingData;
    use RefreshDatabase;

    public function test_only_correct_payer_can_submit_payment_and_only_admin_can_confirm(): void
    {
        $location = $this->seedLocationData();

        $sender = User::factory()->create();
        $receiver = User::factory()->create([
            'phone' => '0713000100',
            'email' => 'receiver-contact@example.com',
        ]);
        $otherUser = User::factory()->create();
        $admin = User::factory()->create(['is_admin' => true]);

        $this->createProfileFor(
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

        $connectionRequest = ConnectionRequest::query()->create([
            'sender_id' => $sender->id,
            'receiver_id' => $receiver->id,
            'status' => ConnectionRequestStatus::PaymentPending->value,
        ]);

        Sanctum::actingAs($sender);
        $this->getJson('/api/browse/'.$receiverProfile->id)
            ->assertOk()
            ->assertJsonPath('data.user.phone', null)
            ->assertJsonPath('data.user.email', null);

        Sanctum::actingAs($otherUser);
        $this->postJson('/api/connection-requests/'.$connectionRequest->id.'/payments', [
            'method' => PaymentMethod::MobileMoney->value,
            'reference' => 'MM-OTHER-001',
        ])->assertStatus(403);

        Sanctum::actingAs($sender);
        $paymentResponse = $this->postJson('/api/connection-requests/'.$connectionRequest->id.'/payments', [
            'method' => PaymentMethod::MobileMoney->value,
            'reference' => 'MM-SENDER-001',
        ])->assertCreated()
            ->assertJsonPath('data.status', PaymentStatus::Pending->value);

        $paymentId = (int) $paymentResponse->json('data.id');
        $this->assertGreaterThan(0, $paymentId);

        $this->patchJson('/api/admin/payments/'.$paymentId.'/confirm')
            ->assertStatus(403)
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'Admins only.');

        Sanctum::actingAs($admin);
        $this->patchJson('/api/admin/payments/'.$paymentId.'/confirm')
            ->assertOk()
            ->assertJsonPath('data.status', PaymentStatus::Confirmed->value);

        $this->assertDatabaseHas('payments', [
            'id' => $paymentId,
            'status' => PaymentStatus::Confirmed->value,
            'confirmed_by' => $admin->id,
        ]);

        $this->assertDatabaseHas('connection_requests', [
            'id' => $connectionRequest->id,
            'status' => ConnectionRequestStatus::PartiallyPaid->value,
        ]);

        Sanctum::actingAs($sender);
        $this->getJson('/api/browse/'.$receiverProfile->id)
            ->assertOk()
            ->assertJsonPath('data.user.phone', null)
            ->assertJsonPath('data.user.email', null);

        Sanctum::actingAs($receiver);
        $counterpartPaymentResponse = $this->postJson('/api/connection-requests/'.$connectionRequest->id.'/payments', [
            'method' => PaymentMethod::BankTransfer->value,
            'reference' => 'BK-RECEIVER-001',
        ])->assertCreated()
            ->assertJsonPath('data.status', PaymentStatus::Pending->value);

        $counterpartPaymentId = (int) $counterpartPaymentResponse->json('data.id');
        $this->assertGreaterThan(0, $counterpartPaymentId);

        Sanctum::actingAs($admin);
        $this->patchJson('/api/admin/payments/'.$counterpartPaymentId.'/confirm')
            ->assertOk()
            ->assertJsonPath('data.status', PaymentStatus::Confirmed->value);

        $this->assertDatabaseHas('connection_requests', [
            'id' => $connectionRequest->id,
            'status' => ConnectionRequestStatus::Connected->value,
        ]);

        Sanctum::actingAs($sender);
        $this->getJson('/api/browse/'.$receiverProfile->id)
            ->assertOk()
            ->assertJsonPath('data.user.phone', $receiver->phone)
            ->assertJsonPath('data.user.email', $receiver->email);
    }

    public function test_only_admin_can_reject_payments(): void
    {
        $location = $this->seedLocationData();

        $sender = User::factory()->create();
        $receiver = User::factory()->create();
        $nonAdmin = User::factory()->create(['is_admin' => false]);
        $admin = User::factory()->create(['is_admin' => true]);

        $this->createProfileFor(
            $sender,
            $location['tz'],
            $location['region'],
            $location['district'],
            ['approval_status' => ProfileApprovalStatus::Approved->value, 'is_visible' => true]
        );
        $this->createProfileFor(
            $receiver,
            $location['tz'],
            $location['region'],
            $location['district'],
            ['approval_status' => ProfileApprovalStatus::Approved->value, 'is_visible' => true]
        );

        $connectionRequest = ConnectionRequest::query()->create([
            'sender_id' => $sender->id,
            'receiver_id' => $receiver->id,
            'status' => ConnectionRequestStatus::PaymentPending->value,
        ]);

        $payment = Payment::query()->create([
            'connection_request_id' => $connectionRequest->id,
            'payer_id' => $sender->id,
            'amount' => 18000,
            'method' => PaymentMethod::Cash->value,
            'reference' => 'CASH-REJECT-001',
            'status' => PaymentStatus::Pending->value,
        ]);

        Sanctum::actingAs($nonAdmin);
        $this->patchJson('/api/admin/payments/'.$payment->id.'/reject')
            ->assertStatus(403)
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'Admins only.');

        Sanctum::actingAs($admin);
        $this->patchJson('/api/admin/payments/'.$payment->id.'/reject')
            ->assertOk()
            ->assertJsonPath('data.status', PaymentStatus::Failed->value);

        $this->assertDatabaseHas('payments', [
            'id' => $payment->id,
            'status' => PaymentStatus::Failed->value,
            'confirmed_by' => $admin->id,
        ]);
    }
}

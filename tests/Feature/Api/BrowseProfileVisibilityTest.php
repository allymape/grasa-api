<?php

namespace Tests\Feature\Api;

use App\Enums\ProfileApprovalStatus;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\Feature\Api\Concerns\CreatesMatchmakingData;
use Tests\TestCase;

class BrowseProfileVisibilityTest extends TestCase
{
    use CreatesMatchmakingData;
    use RefreshDatabase;

    public function test_browse_only_lists_approved_and_visible_profiles(): void
    {
        $location = $this->seedLocationData();

        $viewer = User::factory()->create();
        Sanctum::actingAs($viewer);

        $approvedVisibleOwner = User::factory()->create([
            'phone' => '0712001001',
            'email' => 'approved@example.com',
        ]);
        $approvedHiddenOwner = User::factory()->create([
            'phone' => '0712001002',
            'email' => 'hidden@example.com',
        ]);
        $pendingVisibleOwner = User::factory()->create([
            'phone' => '0712001003',
            'email' => 'pending@example.com',
        ]);

        $approvedVisible = $this->createProfileFor(
            $approvedVisibleOwner,
            $location['tz'],
            $location['region'],
            $location['district'],
            [
                'display_name' => 'Approved Visible',
                'approval_status' => ProfileApprovalStatus::Approved->value,
                'is_visible' => true,
            ]
        );

        $approvedHidden = $this->createProfileFor(
            $approvedHiddenOwner,
            $location['tz'],
            $location['region'],
            $location['district'],
            [
                'display_name' => 'Approved Hidden',
                'approval_status' => ProfileApprovalStatus::Approved->value,
                'is_visible' => false,
            ]
        );

        $pendingVisible = $this->createProfileFor(
            $pendingVisibleOwner,
            $location['tz'],
            $location['region'],
            $location['district'],
            [
                'display_name' => 'Pending Visible',
                'approval_status' => ProfileApprovalStatus::Pending->value,
                'is_visible' => true,
            ]
        );

        $response = $this->getJson('/api/browse')
            ->assertOk()
            ->assertJsonPath('success', true);

        $ids = collect($response->json('data.items'))->pluck('id')->all();
        $this->assertContains($approvedVisible->id, $ids);
        $this->assertNotContains($approvedHidden->id, $ids);
        $this->assertNotContains($pendingVisible->id, $ids);

        $visibleItem = collect($response->json('data.items'))
            ->firstWhere('id', $approvedVisible->id);

        $this->assertNotNull($visibleItem);
        $this->assertNull($visibleItem['user']['phone']);
        $this->assertNull($visibleItem['user']['email']);
    }

    public function test_browse_detail_blocks_hidden_or_unapproved_profiles(): void
    {
        $location = $this->seedLocationData();

        $viewer = User::factory()->create();
        Sanctum::actingAs($viewer);

        $owner = User::factory()->create();
        $hiddenProfile = $this->createProfileFor(
            $owner,
            $location['tz'],
            $location['region'],
            $location['district'],
            [
                'approval_status' => ProfileApprovalStatus::Approved->value,
                'is_visible' => false,
            ]
        );

        $pendingProfile = $this->createProfileFor(
            User::factory()->create(),
            $location['tz'],
            $location['region'],
            $location['district'],
            [
                'approval_status' => ProfileApprovalStatus::Pending->value,
                'is_visible' => true,
            ]
        );

        $this->getJson('/api/browse/'.$hiddenProfile->id)
            ->assertStatus(404);

        $this->getJson('/api/browse/'.$pendingProfile->id)
            ->assertStatus(404);
    }
}

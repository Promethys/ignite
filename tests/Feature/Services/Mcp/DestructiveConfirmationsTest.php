<?php

namespace Tests\Feature\Services\Mcp;

use App\Models\User;
use App\Services\Mcp\DestructiveConfirmations;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DestructiveConfirmationsTest extends TestCase
{
    use RefreshDatabase;

    private DestructiveConfirmations $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = app(DestructiveConfirmations::class);
    }

    public function test_a_freshly_issued_token_is_accepted(): void
    {
        $actor = User::factory()->create();

        $token = $this->service->issue($actor, 'delete_goal', [44]);

        $this->assertTrue($this->service->consume($actor, $token, 'delete_goal', [44]));
    }

    public function test_a_token_can_only_be_consumed_once(): void
    {
        $actor = User::factory()->create();

        $token = $this->service->issue($actor, 'delete_goal', [44]);

        $this->assertTrue($this->service->consume($actor, $token, 'delete_goal', [44]));
        $this->assertFalse($this->service->consume($actor, $token, 'delete_goal', [44]));
    }

    public function test_another_users_token_is_rejected(): void
    {
        $owner = User::factory()->create();
        $intruder = User::factory()->create();

        $token = $this->service->issue($owner, 'delete_goal', [44]);

        $this->assertFalse($this->service->consume($intruder, $token, 'delete_goal', [44]));
    }

    public function test_a_token_issued_for_one_target_cannot_confirm_another(): void
    {
        $actor = User::factory()->create();

        $token = $this->service->issue($actor, 'delete_goal', [44]);

        $this->assertFalse($this->service->consume($actor, $token, 'delete_goal', [99]));
    }

    public function test_a_token_cannot_be_replayed_against_a_superset_of_targets(): void
    {
        $actor = User::factory()->create();

        $token = $this->service->issue($actor, 'delete_goal', [44]);

        $this->assertFalse($this->service->consume($actor, $token, 'delete_goal', [44, 99]));
    }

    public function test_a_token_issued_for_one_operation_cannot_confirm_another(): void
    {
        $actor = User::factory()->create();

        $token = $this->service->issue($actor, 'delete_goal', [44]);

        $this->assertFalse($this->service->consume($actor, $token, 'delete_entry', [44]));
    }

    public function test_an_expired_token_is_rejected(): void
    {
        $actor = User::factory()->create();

        $token = $this->service->issue($actor, 'delete_goal', [44]);

        $this->travel(3)->minutes();

        $this->assertFalse($this->service->consume($actor, $token, 'delete_goal', [44]));
    }

    public function test_a_token_is_still_valid_just_before_it_expires(): void
    {
        $actor = User::factory()->create();

        $token = $this->service->issue($actor, 'delete_goal', [44]);

        $this->travel(90)->seconds();

        $this->assertTrue($this->service->consume($actor, $token, 'delete_goal', [44]));
    }

    public function test_an_unknown_token_is_rejected(): void
    {
        $actor = User::factory()->create();

        $this->assertFalse($this->service->consume($actor, 'not-a-real-token', 'delete_goal', [44]));
    }

    public function test_a_rejected_attempt_still_burns_the_token(): void
    {
        $actor = User::factory()->create();

        $token = $this->service->issue($actor, 'delete_goal', [44]);

        $this->assertFalse($this->service->consume($actor, $token, 'delete_goal', [99]));
        $this->assertFalse($this->service->consume($actor, $token, 'delete_goal', [44]));
    }
}

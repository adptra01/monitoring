<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\UserTourProgress;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TourProgressTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_complete_a_tour(): void
    {
        $user = User::factory()->create();

        UserTourProgress::create([
            'user_id' => $user->id,
            'tour_id' => 'onboarding',
            'completed_at' => now(),
        ]);

        $this->assertDatabaseHas('user_tour_progress', [
            'user_id' => $user->id,
            'tour_id' => 'onboarding',
        ]);

        $progress = UserTourProgress::where('user_id', $user->id)
            ->where('tour_id', 'onboarding')
            ->first();

        $this->assertTrue($progress->isCompleted());
        $this->assertFalse($progress->isSkipped());
    }

    public function test_user_can_skip_a_tour(): void
    {
        $user = User::factory()->create();

        UserTourProgress::create([
            'user_id' => $user->id,
            'tour_id' => 'license-management',
            'skipped_at' => now(),
        ]);

        $progress = UserTourProgress::where('user_id', $user->id)
            ->where('tour_id', 'license-management')
            ->first();

        $this->assertTrue($progress->isSkipped());
        $this->assertFalse($progress->isCompleted());
    }

    public function test_tour_progress_unique_per_user_and_tour(): void
    {
        $user = User::factory()->create();

        UserTourProgress::create([
            'user_id' => $user->id,
            'tour_id' => 'onboarding',
            'completed_at' => now(),
        ]);

        $this->expectException(QueryException::class);

        UserTourProgress::create([
            'user_id' => $user->id,
            'tour_id' => 'onboarding',
            'completed_at' => now(),
        ]);
    }

    public function test_config_returns_tour_definitions(): void
    {
        $tours = config('tours');

        $this->assertIsArray($tours);
        $this->assertArrayHasKey('onboarding', $tours);
        $this->assertArrayHasKey('license-management', $tours);
        $this->assertArrayHasKey('contextual-products', $tours);

        $this->assertEquals('onboarding', $tours['onboarding']['type']);
        $this->assertCount(4, $tours['onboarding']['steps']);
    }
}

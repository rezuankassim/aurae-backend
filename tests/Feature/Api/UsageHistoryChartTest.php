<?php

namespace Tests\Feature\Api;

use App\Models\UsageHistory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class UsageHistoryChartTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_excludes_records_without_started_at_from_chart_calculations(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        $this->createUsageHistory($user, [
            'duration' => 60,
            'force_stopped' => true,
            'started_at' => null,
            'ended_at' => '11062026 17:37:50',
        ], '2026-06-11 17:37:50');

        $this->createUsageHistory($user, [
            'duration' => 60,
            'force_stopped' => false,
            'started_at' => '11062026 17:55:47',
            'ended_at' => '11062026 18:54:49',
        ], '2026-06-11 18:54:49');

        $this->createUsageHistory($user, [
            'duration' => 30,
            'force_stopped' => false,
            'started_at' => '12062026 08:00:00',
            'ended_at' => '12062026 08:30:00',
        ], '2026-06-12 08:30:00');

        $this->createUsageHistory($otherUser, [
            'duration' => 999,
            'force_stopped' => false,
            'started_at' => '11062026 10:00:00',
            'ended_at' => '11062026 10:39:00',
        ], '2026-06-11 10:39:00');

        $response = $this->actingAs($user, 'sanctum')
            ->withHeaders($this->deviceHeaders())
            ->getJson('/api/usage-histories/chart');

        $response->assertStatus(200);
        $response->assertJson([
            'status' => 200,
            'message' => 'Chart data retrieved successfully.',
        ]);

        $dailyUsage = $response->json('data.daily_usage');
        $dailyAverage = $response->json('data.daily_average');

        $this->assertCount(2, $dailyUsage);

        $this->assertSame('2026-06-11', $dailyUsage[0]['date']);
        $this->assertSame(1, $dailyUsage[0]['sessions']);
        $this->assertEquals(60.0, $dailyUsage[0]['total_duration']);

        $this->assertSame('2026-06-12', $dailyUsage[1]['date']);
        $this->assertSame(1, $dailyUsage[1]['sessions']);
        $this->assertEquals(30.0, $dailyUsage[1]['total_duration']);

        $this->assertSame(2, $dailyAverage['total_sessions']);
        $this->assertEquals(90.0, $dailyAverage['total_duration']);
        $this->assertSame(2, $dailyAverage['days_with_usage']);
        $this->assertEquals(1.0, $dailyAverage['average_sessions']);
        $this->assertEquals(45.0, $dailyAverage['average_duration']);
    }

    #[Test]
    public function it_applies_date_range_filters_on_the_started_records_dataset(): void
    {
        $user = User::factory()->create();

        $this->createUsageHistory($user, [
            'duration' => 15,
            'force_stopped' => false,
            'started_at' => '10062026 09:00:00',
            'ended_at' => '10062026 09:15:00',
        ], '2026-06-10 09:15:00');

        $this->createUsageHistory($user, [
            'duration' => 90,
            'force_stopped' => true,
            'started_at' => null,
            'ended_at' => '11062026 11:30:00',
        ], '2026-06-11 11:30:00');

        $this->createUsageHistory($user, [
            'duration' => 45,
            'force_stopped' => false,
            'started_at' => '11062026 12:00:00',
            'ended_at' => '11062026 12:45:00',
        ], '2026-06-11 12:45:00');

        $response = $this->actingAs($user, 'sanctum')
            ->withHeaders($this->deviceHeaders())
            ->getJson('/api/usage-histories/chart?from=2026-06-11&to=2026-06-11');

        $response->assertStatus(200);

        $dailyUsage = $response->json('data.daily_usage');
        $dailyAverage = $response->json('data.daily_average');

        $this->assertCount(1, $dailyUsage);
        $this->assertSame('2026-06-11', $dailyUsage[0]['date']);
        $this->assertSame(1, $dailyUsage[0]['sessions']);
        $this->assertEquals(45.0, $dailyUsage[0]['total_duration']);

        $this->assertSame(1, $dailyAverage['total_sessions']);
        $this->assertEquals(45.0, $dailyAverage['total_duration']);
        $this->assertSame(1, $dailyAverage['days_with_usage']);
        $this->assertEquals(1.0, $dailyAverage['average_sessions']);
        $this->assertEquals(45.0, $dailyAverage['average_duration']);
    }

    private function createUsageHistory(User $user, array $content, string $createdAt): void
    {
        $usageHistory = UsageHistory::create([
            'user_id' => $user->id,
            'therapy_id' => null,
            'content' => $content,
        ]);

        DB::table('usage_histories')
            ->where('id', $usageHistory->id)
            ->update([
                'created_at' => $createdAt,
                'updated_at' => $createdAt,
            ]);
    }

    private function deviceHeaders(): array
    {
        return [
            'X-Device-Udid' => 'TEST-DEVICE-UDID-USAGE-HISTORY-CHART',
            'X-Device-OS' => 'Android',
            'X-Device-OS-Version' => '13',
            'X-Device-Manufacturer' => 'Samsung',
            'X-Device-Model' => 'Galaxy S21',
            'X-Device-App-Version' => '1.0.0',
        ];
    }
}

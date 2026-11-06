<?php

namespace App\Services;

use App\Enums\BackupStatus;
use App\Models\DemoBackup;
use Illuminate\Support\Facades\Cache;

/**
 * Computes the project's continuity indicators from the recorded backup executions.
 */
class ContinuityMetrics
{
    /**
     * Recovery time objective defined by the project (15 minutes).
     */
    public const int RTO_TARGET_MS = 15 * 60 * 1000;

    /**
     * @var array{
     *     total: int,
     *     successful: int,
     *     failed: int,
     *     successRate: float|null,
     *     restored: int,
     *     latestBackup: DemoBackup|null,
     *     latestRestore: DemoBackup|null,
     *     latestRestoreMs: int|null,
     *     meetsRto: bool|null,
     * }|null
     */
    private ?array $memorySummary = null;

    /**
     * @return array{
     *     total: int,
     *     successful: int,
     *     failed: int,
     *     successRate: float|null,
     *     restored: int,
     *     latestBackup: DemoBackup|null,
     *     latestRestore: DemoBackup|null,
     *     latestRestoreMs: int|null,
     *     meetsRto: bool|null,
     * }
     */
    public function summary(bool $forceRefresh = false): array
    {
        if (! $forceRefresh && $this->memorySummary !== null) {
            return $this->memorySummary;
        }

        if (! $forceRefresh) {
            $cached = Cache::get('continuity_metrics_summary');
            if (is_array($cached)) {
                return $this->memorySummary = $cached;
            }
        }

        /** @var array<string, int> $countsByStatus */
        $countsByStatus = DemoBackup::query()
            ->toBase()
            ->selectRaw('status, count(*) as aggregate')
            ->groupBy('status')
            ->pluck('aggregate', 'status')
            ->map(fn (mixed $count): int => (int) $count)
            ->all();

        $successful = $countsByStatus[BackupStatus::Success->value] ?? 0;
        $failed = $countsByStatus[BackupStatus::Failed->value] ?? 0;
        $finished = $successful + $failed;

        $latestRestore = DemoBackup::query()
            ->whereNotNull('restored_path')
            ->latest('updated_at')
            ->first();

        $result = [
            'total' => array_sum($countsByStatus),
            'successful' => $successful,
            'failed' => $failed,
            'successRate' => $finished > 0 ? round($successful / $finished * 100, 1) : null,
            'restored' => DemoBackup::query()->whereNotNull('restored_path')->count(),
            'latestBackup' => DemoBackup::query()->latest('id')->first(),
            'latestRestore' => $latestRestore,
            'latestRestoreMs' => $latestRestore?->restore_duration_ms,
            'meetsRto' => $latestRestore
                ? $latestRestore->restore_duration_ms < self::RTO_TARGET_MS
                : null,
        ];

        Cache::put('continuity_metrics_summary', $result, now()->addMinutes(2));

        return $this->memorySummary = $result;
    }

    public function clearCache(): void
    {
        $this->memorySummary = null;
        Cache::forget('continuity_metrics_summary');
    }
}

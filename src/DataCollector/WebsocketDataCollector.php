<?php declare(strict_types=1);

namespace Gos\Bundle\WebSocketBundle\DataCollector;

use Symfony\Component\Stopwatch\StopwatchEvent;

trigger_deprecation('gos/web-socket-bundle', '3.1', 'The "%s" class is deprecated and will be removed in 4.0.', WebsocketDataCollector::class);

/**
 * @deprecated to be removed in 4.0
 */
final class WebsocketDataCollector extends WebsocketCompatibilityDataCollector
{
    private array $rawData = [];

    public function lateCollect(): void
    {
        $pusherCount = [];
        $totalPush = 0;
        $pusherDuration = [];
        $durationTotal = 0.0;

        foreach ($this->rawData as $pusherName => $durations) {
            if (!isset($pusherCount[$pusherName])) {
                $pusherCount[$pusherName] = 0;
            }

            $pusherDurationTotal = array_sum($durations);
            $pusherCount[$pusherName] += $count = \count($durations);
            $totalPush += $count;
            $pusherDuration[$pusherName] = $pusherDurationTotal;
            $durationTotal += $pusherDurationTotal;
        }

        $this->data = [
            'pusher_counts' => $pusherCount,
            'push_total' => $totalPush,
            'durations' => $pusherDuration,
            'duration_total' => $durationTotal,
        ];
    }

    /**
     * @return int[]
     */
    public function getPusherCounts(): array
    {
        return $this->data['pusher_counts'];
    }

    public function getTotalDuration(): float
    {
        return $this->data['duration_total'];
    }

    /**
     * @return float[]
     */
    public function getDurations(): array
    {
        return $this->data['durations'];
    }

    public function getPushTotal(): int
    {
        return $this->data['push_total'];
    }

    public function collectData(StopwatchEvent $event, string $pusherName): void
    {
        if (!isset($this->rawData[$pusherName])) {
            $this->rawData[$pusherName] = [];
        }

        $this->rawData[$pusherName][] = $event->getDuration();
    }

    public function reset(): void
    {
        $this->rawData = [];

        $this->data = [
            'pusher_counts' => [],
            'push_total' => 0,
            'durations' => [],
            'duration_total' => 0,
        ];
    }

    public function getName(): string
    {
        return 'websocket';
    }
}

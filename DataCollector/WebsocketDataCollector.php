<?php

namespace Gos\Bundle\WebSocketBundle\DataCollector;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\DataCollector\DataCollector;
use Symfony\Component\Stopwatch\StopwatchEvent;

class WebsocketDataCollector extends DataCollector
{
    /**
     * @var array
     */
    protected $rawData = [];

    /**
     * Collects data for the given Request and Response.
     */
    public function collect(Request $request, Response $response, \Exception $exception = null)
    {
        $pusherCount = [];
        $totalPush = 0;
        $pusherDuration = [];
        $durationTotal = 0;

        foreach ($this->rawData as $pusherName => $durations) {
            if (!isset($pusherCount[$pusherName])) {
                $pusherCount[$pusherName] = 0;
            }

            $pusherDurationTotal = array_sum($durations);
            $pusherCount[$pusherName] += $count = count($durations);
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
    public function getPusherCounts()
    {
        return $this->data['pusher_counts'];
    }

    /**
     * @return float
     */
    public function getTotalDuration()
    {
        return $this->data['duration_total'];
    }

    /**
     * @return float[]
     */
    public function getDurations()
    {
        return $this->data['durations'];
    }

    /**
     * @return int
     */
    public function getPushTotal()
    {
        return $this->data['push_total'];
    }

    /**
     * @param StopwatchEvent $event
     * @param string         $pusherName
     */
    public function collectData(StopwatchEvent $event, $pusherName)
    {
        if (!isset($this->rawData[$pusherName])) {
            $this->rawData[$pusherName] = [];
        }

        $this->rawData[$pusherName][] = $event->getDuration();
    }

    /**
     * {@inheritdoc}
     */
    public function reset()
    {
        $this->rawData = [];

        $this->data = [
            'pusher_counts' => [],
            'push_total' => 0,
            'durations' => [],
            'duration_total' => 0,
        ];
    }

    /**
     * Returns the name of the collector.
     */
    public function getName()
    {
        return 'websocket';
    }
}

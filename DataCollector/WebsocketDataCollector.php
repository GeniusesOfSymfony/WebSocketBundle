<?php

namespace Gos\Bundle\WebSocketBundle\DataCollector;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\DataCollector\DataCollector;
use Symfony\Component\Stopwatch\StopwatchEvent;

class WebsocketDataCollector extends DataCollector
{
    /** @var array */
    protected $durations;

    /** @var  [] */
    protected $count;

    /** @var  [] */
    protected $rawData;

    public function __construct()
    {
        $this->rawData = [];
    }

    /**
     * Collects data for the given Request and Response.
     *
     * @param Request $request A Request instance
     * @param Response $response A Response instance
     * @param \Exception $exception An Exception instance
     *
     * @api
     */
    public function collect(Request $request, Response $response, \Exception $exception = null)
    {
        $pusherDuration = [];
        $pusherCount = [];
        $durationTotal = 0;
        $totalPush = 0;

        foreach($this->rawData as $pusherName => $durations) {
            if(!isset($pusherCount[$pusherName])){
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
            'duration_total' => $durationTotal
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
     * @param $pusherName
     */
    public function collectData(StopwatchEvent $event, $pusherName)
    {
        if(!isset($this->rawData[$pusherName])){
            $this->rawData[$pusherName] = [];
        }

        $this->rawData[$pusherName][] = $event->getDuration();
    }

    /**
     * Returns the name of the collector.
     *
     * @return string The collector name
     *
     * @api
     */
    public function getName()
    {
        return 'websocket';
    }
}

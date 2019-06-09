<?php

namespace Gos\Bundle\WebSocketBundle\DataCollector;

use Gos\Bundle\WebSocketBundle\Pusher\PusherInterface;
use Symfony\Component\Stopwatch\Stopwatch;

/**
 * Pusher decorating another Pusher to collect data
 */
final class PusherDecorator implements PusherInterface
{
    /**
     * @var PusherInterface
     */
    private $pusher;

    /**
     * @var Stopwatch
     */
    private $stopwatch;

    /**
     * @var WebsocketDataCollector
     */
    private $dataCollector;

    public function __construct(PusherInterface $pusher, Stopwatch $stopwatch, WebsocketDataCollector $dataCollector)
    {
        $this->pusher = $pusher;
        $this->stopwatch = $stopwatch;
        $this->dataCollector = $dataCollector;
    }

    /**
     * @param string|array $data
     */
    public function push($data, string $routeName, array $routeParameters = [], array $context = []): void
    {
        $eventName = 'push.'.$this->getName();

        $this->stopwatch->start($eventName, 'websocket');
        $this->pusher->push($data, $routeName, $routeParameters, $context);
        $this->stopwatch->stop($eventName);

        $this->dataCollector->collectData($this->stopwatch->getEvent($eventName), $this->getName());
    }

    public function close(): void
    {
        $this->pusher->close();
    }

    public function setName(string $name): void
    {
        $this->pusher->setName($name);
    }

    public function getName(): string
    {
        return $this->pusher->getName();
    }
}

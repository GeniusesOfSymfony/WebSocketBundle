<?php declare(strict_types=1);

namespace Gos\Bundle\WebSocketBundle\Pusher;

use Gos\Bundle\WebSocketBundle\DataCollector\WebsocketDataCollector;
use Symfony\Component\Stopwatch\Stopwatch;

trigger_deprecation('gos/web-socket-bundle', '3.1', 'The "%s" class is deprecated and will be removed in 4.0, use the symfony/messenger component instead.', DataCollectingPusherDecorator::class);

/**
 * @deprecated to be removed in 4.0, use the symfony/messenger component instead
 */
final class DataCollectingPusherDecorator implements PusherInterface
{
    private PusherInterface $pusher;
    private Stopwatch $stopwatch;
    private WebsocketDataCollector $dataCollector;

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

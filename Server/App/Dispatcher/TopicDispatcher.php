<?php

namespace Gos\Bundle\WebSocketBundle\Server\App\Dispatcher;

use Gos\Bundle\WebSocketBundle\Router\WampRequest;
use Gos\Bundle\WebSocketBundle\Router\WampRouter;
use Gos\Bundle\WebSocketBundle\Server\App\Registry\TopicRegistry;
use Ratchet\ConnectionInterface;
use Ratchet\Wamp\Topic;
use Symfony\Component\HttpKernel\Log\LoggerInterface;

/**
 * @author Johann Saunier <johann_27@hotmail.fr>
 */
class TopicDispatcher implements TopicDispatcherInterface
{
    /**
     * @var TopicRegistry
     */
    protected $topicRegistry;

    /**
     * @var WampRouter
     */
    protected $router;

    /**
     * @var LoggerInterface|null
     */
    protected $logger;

    const SUBSCRIPTION = 'onSubscribe';

    const UNSUBSCRIPTION = 'onUnSubscribe';

    const PUBLISH = 'onPublish';

    /**
     * @param TopicRegistry   $topicRegistry
     * @param WampRouter      $router
     * @param LoggerInterface $logger
     */
    public function __construct(TopicRegistry $topicRegistry, WampRouter $router, LoggerInterface $logger = null)
    {
        $this->topicRegistry = $topicRegistry;
        $this->router = $router;
        $this->logger = $logger;
    }

    /**
     * @param ConnectionInterface $conn
     * @param Topic               $topic
     */
    public function onSubscribe(ConnectionInterface $conn, Topic $topic, WampRequest $request)
    {
        //if topic service exists, notify it
        $this->dispatch(self::SUBSCRIPTION, $conn, $topic, $request);
    }

    /**
     * @param ConnectionInterface $conn
     * @param Topic               $topic
     */
    public function onUnSubscribe(ConnectionInterface $conn, Topic $topic, WampRequest $request)
    {
        //if topic service exists, notify it
        $this->dispatch(self::UNSUBSCRIPTION, $conn, $topic, $request);
    }

    /**
     * @param ConnectionInterface $conn
     * @param Topic               $topic
     * @param string              $event
     * @param array               $exclude
     * @param array               $eligible
     */
    public function onPublish(ConnectionInterface $conn, Topic $topic, WampRequest $request, $event, array $exclude, array $eligible)
    {
        if (!$this->dispatch(self::PUBLISH, $conn, $topic, $request, $event, $exclude, $eligible)) {
            //default behaviour is to broadcast to all.
            $topic->broadcast($event);

            return;
        }
    }

    /**
     * @param string              $calledMethod
     * @param ConnectionInterface $conn
     * @param Topic               $topic
     * @param null                $payload
     * @param null                $exclude
     * @param null                $eligible
     *
     * @return bool
     */
    public function dispatch($calledMethod, ConnectionInterface $conn, Topic $topic, WampRequest $request, $payload = null, $exclude = null, $eligible = null)
    {
        $dispatched = false;

        foreach ($request->getRoute()->getCallback() as $callback) {
            $appTopic = $this->topicRegistry->getTopic($callback);
            if ($topic) {
                if ($payload) { //its a publish call.
                    $appTopic->{$calledMethod}($conn, $topic, $request, $payload, $exclude, $eligible);
                } else {
                    $appTopic->{$calledMethod}($conn, $topic, $request);
                }

                $dispatched = true;
            }
        }

        return $dispatched;
    }
}

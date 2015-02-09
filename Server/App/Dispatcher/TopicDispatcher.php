<?php

namespace Gos\Bundle\WebSocketBundle\Server\App\Dispatcher;

use Gos\Bundle\WebSocketBundle\Server\App\Registry\TopicRegistry;
use Ratchet\ConnectionInterface;
use Ratchet\Wamp\Topic;

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
     * @param TopicRegistry $topicRegistry
     */
    public function __construct(TopicRegistry $topicRegistry)
    {
        $this->topicRegistry = $topicRegistry;
    }

    /**
     * @param ConnectionInterface $conn
     * @param Topic               $topic
     */
    public function onSubscribe(ConnectionInterface $conn, Topic $topic)
    {
        //if topic service exists, notify it
        $this->dispatch(__METHOD__, $conn, $topic);
    }

    /**
     * @param ConnectionInterface $conn
     * @param Topic               $topic
     */
    public function onUnSubscribe(ConnectionInterface $conn, Topic $topic)
    {
        //if topic service exists, notify it
        $this->dispatch(__METHOD__, $conn, $topic);
    }

    /**
     * @param ConnectionInterface $conn
     * @param Topic               $topic
     * @param string              $event
     * @param array               $exclude
     * @param array               $eligible
     */
    public function onPublish(ConnectionInterface $conn, Topic $topic, $event, array $exclude, array $eligible)
    {
        if (!$this->dispatch(__METHOD__, $conn, $topic, $event, $exclude, $eligible)) {
            //default behaviour is to broadcast to all.
            $topic->broadcast($event);

            return;
        }
    }

    /**
     * @param string              $event
     * @param ConnectionInterface $conn
     * @param Topic               $topic
     * @param null                $payload
     * @param null                $exclude
     * @param null                $eligible
     *
     * @return bool
     */
    public function dispatch($event, ConnectionInterface $conn, Topic $topic, $payload = null, $exclude = null, $eligible = null)
    {
        $event = explode(":", $event);
        if (count($event) <= 0) {
            return false;
        }
        $event = $event[count($event)-1];
        //if topic service exists, notify it

        $topic = $this->topicRegistry->getTopic($topic->getId());

        if ($topic) {
            if ($payload) { //its a publish call.
                call_user_func([$topic, $event], $conn, $topic, $payload, $exclude, $eligible);
            } else {
                call_user_func([$topic, $event], $conn, $topic);
            }

            return true;
        }

        return false;
    }
}

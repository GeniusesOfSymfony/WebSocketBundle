<?php declare(strict_types=1);

namespace Gos\Bundle\WebSocketBundle\Topic;

use Ratchet\ConnectionInterface;
use Ratchet\Wamp\Topic;
use Ratchet\Wamp\WampServerInterface;
use Ratchet\WebSocket\WsServerInterface;

/**
 * @author Edu Salguero <edusalguero@gmail.com>
 */
class TopicManager implements WsServerInterface, WampServerInterface
{
    protected ?WampServerInterface $app = null;

    /**
     * @var array<string, Topic>
     */
    protected array $topicLookup = [];

    public function setWampApplication(WampServerInterface $app): void
    {
        $this->app = $app;
    }

    public function onOpen(ConnectionInterface $conn): void
    {
        $conn->WAMP->subscriptions = new \SplObjectStorage();
        $this->app->onOpen($conn);
    }

    /**
     * @param string       $id The unique ID of the RPC, required to respond to
     * @param string|Topic $topic
     */
    public function onCall(ConnectionInterface $conn, $id, $topic, array $params): void
    {
        $this->app->onCall($conn, $id, $this->getTopic($topic), $params);
    }

    /**
     * @param string|Topic $topic
     */
    public function onSubscribe(ConnectionInterface $conn, $topic): void
    {
        $topicObj = $this->getTopic($topic);

        if ($conn->WAMP->subscriptions->contains($topicObj)) {
            return;
        }

        $this->topicLookup[(string) $topic]->add($conn);
        $conn->WAMP->subscriptions->attach($topicObj);
        $this->app->onSubscribe($conn, $topicObj);
    }

    /**
     * @param string|Topic $topic
     */
    public function onUnsubscribe(ConnectionInterface $conn, $topic): void
    {
        $topicObj = $this->getTopic($topic);

        if (!$conn->WAMP->subscriptions->contains($topicObj)) {
            return;
        }

        $this->cleanTopic($topicObj, $conn);

        $this->app->onUnsubscribe($conn, $topicObj);
    }

    /**
     * @param string|Topic $topic
     * @param string       $event
     */
    public function onPublish(ConnectionInterface $conn, $topic, $event, array $exclude, array $eligible): void
    {
        $this->app->onPublish($conn, $this->getTopic($topic), $event, $exclude, $eligible);
    }

    public function onClose(ConnectionInterface $conn): void
    {
        $this->app->onClose($conn);

        foreach ($this->topicLookup as $topic) {
            $this->cleanTopic($topic, $conn);
        }
    }

    public function onError(ConnectionInterface $conn, \Exception $e): void
    {
        $this->app->onError($conn, $e);
    }

    public function getSubProtocols(): array
    {
        if ($this->app instanceof WsServerInterface) {
            return $this->app->getSubProtocols();
        }

        return [];
    }

    /**
     * @param string|Topic $topic
     */
    public function getTopic($topic): Topic
    {
        if (!\array_key_exists((string) $topic, $this->topicLookup)) {
            if ($topic instanceof Topic) {
                $this->topicLookup[(string) $topic] = $topic;
            } else {
                $this->topicLookup[$topic] = new Topic($topic);
            }
        }

        return $this->topicLookup[(string) $topic];
    }

    protected function cleanTopic(Topic $topic, ConnectionInterface $conn): void
    {
        if ($conn->WAMP->subscriptions->contains($topic)) {
            $conn->WAMP->subscriptions->detach($topic);
        }

        $this->topicLookup[$topic->getId()]->remove($conn);

        if (0 === $topic->count()) {
            unset($this->topicLookup[$topic->getId()]);
        }
    }
}

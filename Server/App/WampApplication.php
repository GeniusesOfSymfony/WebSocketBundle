<?php declare(strict_types=1);

namespace Gos\Bundle\WebSocketBundle\Server\App;

use Gos\Bundle\WebSocketBundle\Client\ClientStorageInterface;
use Gos\Bundle\WebSocketBundle\Event\ClientErrorEvent;
use Gos\Bundle\WebSocketBundle\Event\ClientEvent;
use Gos\Bundle\WebSocketBundle\Event\Events;
use Gos\Bundle\WebSocketBundle\Router\WampRequest;
use Gos\Bundle\WebSocketBundle\Router\WampRouter;
use Gos\Bundle\WebSocketBundle\Server\App\Dispatcher\RpcDispatcherInterface;
use Gos\Bundle\WebSocketBundle\Server\App\Dispatcher\TopicDispatcherInterface;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Ratchet\ConnectionInterface;
use Ratchet\Wamp\Topic;
use Ratchet\Wamp\WampServerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * @author Johann Saunier <johann_27@hotmail.fr>
 */
class WampApplication implements WampServerInterface, LoggerAwareInterface
{
    use LoggerAwareTrait;

    /**
     * @var TopicDispatcherInterface
     */
    protected $topicDispatcher;

    /**
     * @var RpcDispatcherInterface
     */
    protected $rpcDispatcher;

    /**
     * @var EventDispatcherInterface
     */
    protected $eventDispatcher;

    /**
     * @var ClientStorageInterface
     */
    protected $clientStorage;

    /**
     * @var WampRouter
     */
    protected $wampRouter;

    public function __construct(
        RpcDispatcherInterface $rpcDispatcher,
        TopicDispatcherInterface $topicDispatcher,
        EventDispatcherInterface $eventDispatcher,
        ClientStorageInterface $clientStorage,
        WampRouter $wampRouter
    ) {
        $this->rpcDispatcher = $rpcDispatcher;
        $this->topicDispatcher = $topicDispatcher;
        $this->eventDispatcher = $eventDispatcher;
        $this->clientStorage = $clientStorage;
        $this->wampRouter = $wampRouter;
    }

    /**
     * @param ConnectionInterface $conn
     * @param Topic|string        $topic
     * @param string              $event
     * @param array               $exclude
     * @param array               $eligible
     */
    public function onPublish(ConnectionInterface $conn, $topic, $event, array $exclude, array $eligible)
    {
        if ($this->logger) {
            if ($this->clientStorage->hasClient($this->clientStorage->getStorageId($conn))) {
                $token = $this->clientStorage->getClient($this->clientStorage->getStorageId($conn));
                $username = $token->getUsername();

                $this->logger->debug(
                    sprintf(
                        'User %s published to %s',
                        $username,
                        $topic->getId()
                    )
                );
            }
        }

        $request = $this->wampRouter->match($topic);

        $this->topicDispatcher->onPublish($conn, $topic, $request, $event, $exclude, $eligible);
    }

    /**
     * @param WampRequest $request
     * @param string      $data
     * @param string      $provider
     */
    public function onPush(WampRequest $request, $data, $provider)
    {
        if ($this->logger) {
            $this->logger->info(
                sprintf('Pusher %s has pushed', $provider),
                [
                    'provider' => $provider,
                    'topic' => $request->getMatched(),
                ]
            );
        }

        $this->topicDispatcher->onPush($request, $data, $provider);
    }

    /**
     * @param ConnectionInterface $conn
     * @param string              $id
     * @param Topic               $topic
     * @param array               $params
     */
    public function onCall(ConnectionInterface $conn, $id, $topic, array $params)
    {
        $request = $this->wampRouter->match($topic);
        $this->rpcDispatcher->dispatch($conn, $id, $topic, $request, $params);
    }

    /**
     * @param ConnectionInterface $conn
     * @param Topic|string        $topic
     */
    public function onSubscribe(ConnectionInterface $conn, $topic)
    {
        if ($this->logger) {
            if ($this->clientStorage->hasClient($this->clientStorage->getStorageId($conn))) {
                $token = $this->clientStorage->getClient($this->clientStorage->getStorageId($conn));
                $username = $token->getUsername();

                $this->logger->info(
                    sprintf(
                        'User %s subscribed to %s',
                        $username,
                        $topic->getId()
                    )
                );
            }
        }

        $wampRequest = $this->wampRouter->match($topic);

        $this->topicDispatcher->onSubscribe($conn, $topic, $wampRequest);
    }

    /**
     * @param ConnectionInterface $conn
     * @param Topic|string        $topic
     */
    public function onUnSubscribe(ConnectionInterface $conn, $topic)
    {
        if ($this->logger) {
            if ($this->clientStorage->hasClient($this->clientStorage->getStorageId($conn))) {
                $token = $this->clientStorage->getClient($this->clientStorage->getStorageId($conn));
                $username = $token->getUsername();

                $this->logger->info(
                    sprintf(
                        'User %s unsubscribed from %s',
                        $username,
                        $topic->getId()
                    )
                );
            }
        }

        $wampRequest = $this->wampRouter->match($topic);

        $this->topicDispatcher->onUnSubscribe($conn, $topic, $wampRequest);
    }

    /**
     * @param ConnectionInterface $conn
     */
    public function onOpen(ConnectionInterface $conn)
    {
        $event = new ClientEvent($conn, ClientEvent::CONNECTED);
        $this->eventDispatcher->dispatch(Events::CLIENT_CONNECTED, $event);
    }

    /**
     * @param ConnectionInterface $conn
     */
    public function onClose(ConnectionInterface $conn)
    {
        foreach ($conn->WAMP->subscriptions as $topic) {
            $wampRequest = $this->wampRouter->match($topic);
            $this->topicDispatcher->onUnSubscribe($conn, $topic, $wampRequest);
        }

        $event = new ClientEvent($conn, ClientEvent::DISCONNECTED);
        $this->eventDispatcher->dispatch(Events::CLIENT_DISCONNECTED, $event);
    }

    /**
     * @param ConnectionInterface $conn
     * @param \Exception          $e
     */
    public function onError(ConnectionInterface $conn, \Exception $e)
    {
        $event = new ClientErrorEvent($conn, ClientEvent::ERROR);
        $event->setException($e);
        $this->eventDispatcher->dispatch(Events::CLIENT_ERROR, $event);
    }
}

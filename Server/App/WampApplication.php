<?php declare(strict_types=1);

namespace Gos\Bundle\WebSocketBundle\Server\App;

use Gos\Bundle\WebSocketBundle\Client\ClientStorageInterface;
use Gos\Bundle\WebSocketBundle\Event\ClientErrorEvent;
use Gos\Bundle\WebSocketBundle\Event\ClientEvent;
use Gos\Bundle\WebSocketBundle\GosWebSocketEvents;
use Gos\Bundle\WebSocketBundle\Router\WampRequest;
use Gos\Bundle\WebSocketBundle\Router\WampRouter;
use Gos\Bundle\WebSocketBundle\Server\App\Dispatcher\RpcDispatcherInterface;
use Gos\Bundle\WebSocketBundle\Server\App\Dispatcher\TopicDispatcherInterface;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Ratchet\ConnectionInterface;
use Ratchet\Wamp\Topic;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * @author Johann Saunier <johann_27@hotmail.fr>
 */
class WampApplication implements PushableWampServerInterface, LoggerAwareInterface
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
     * @param Topic|string $topic
     * @param string       $event
     */
    public function onPublish(ConnectionInterface $conn, $topic, $event, array $exclude, array $eligible): void
    {
        if (null !== $this->logger) {
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
     * @param string|array $data
     * @param string       $provider
     */
    public function onPush(WampRequest $request, $data, $provider): void
    {
        if (null !== $this->logger) {
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
     * @param string $id
     * @param Topic  $topic
     */
    public function onCall(ConnectionInterface $conn, $id, $topic, array $params): void
    {
        $request = $this->wampRouter->match($topic);
        $this->rpcDispatcher->dispatch($conn, $id, $topic, $request, $params);
    }

    /**
     * @param Topic|string $topic
     */
    public function onSubscribe(ConnectionInterface $conn, $topic): void
    {
        if (null !== $this->logger) {
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
     * @param Topic|string $topic
     */
    public function onUnSubscribe(ConnectionInterface $conn, $topic): void
    {
        if (null !== $this->logger) {
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

    public function onOpen(ConnectionInterface $conn): void
    {
        $event = new ClientEvent($conn, ClientEvent::CONNECTED);
        $this->eventDispatcher->dispatch(GosWebSocketEvents::CLIENT_CONNECTED, $event);
    }

    public function onClose(ConnectionInterface $conn): void
    {
        foreach ($conn->WAMP->subscriptions as $topic) {
            $wampRequest = $this->wampRouter->match($topic);
            $this->topicDispatcher->onUnSubscribe($conn, $topic, $wampRequest);
        }

        $event = new ClientEvent($conn, ClientEvent::DISCONNECTED);
        $this->eventDispatcher->dispatch(GosWebSocketEvents::CLIENT_DISCONNECTED, $event);
    }

    public function onError(ConnectionInterface $conn, \Exception $e): void
    {
        $event = new ClientErrorEvent($conn, ClientEvent::ERROR);
        $event->setException($e);
        $this->eventDispatcher->dispatch(GosWebSocketEvents::CLIENT_ERROR, $event);
    }
}

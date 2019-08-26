<?php declare(strict_types=1);

namespace Gos\Bundle\WebSocketBundle\Pusher\Zmq;

use Gos\Bundle\WebSocketBundle\Event\Events;
use Gos\Bundle\WebSocketBundle\Event\PushHandlerEvent;
use Gos\Bundle\WebSocketBundle\Pusher\AbstractServerPushHandler;
use Gos\Bundle\WebSocketBundle\Pusher\Message;
use Gos\Bundle\WebSocketBundle\Router\WampRouter;
use Gos\Bundle\WebSocketBundle\Server\App\PushableWampServerInterface;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Ratchet\Wamp\Topic;
use React\EventLoop\LoopInterface;
use React\ZMQ\SocketWrapper;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Serializer\SerializerInterface;

@trigger_error(sprintf('The %s class is deprecated will be removed in 2.0.', ZmqServerPushHandler::class), E_USER_DEPRECATED);

/**
 * @deprecated to be removed in 2.0
 */
final class ZmqServerPushHandler extends AbstractServerPushHandler implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    /**
     * @var WampRouter
     */
    private $router;

    /**
     * @var SerializerInterface
     */
    private $serializer;

    /**
     * @var SocketWrapper
     */
    private $consumer;

    /**
     * @var EventDispatcherInterface
     */
    private $eventDispatcher;

    /**
     * @var ZmqConnectionFactoryInterface
     */
    private $connectionFactory;

    public function __construct(
        WampRouter $router,
        SerializerInterface $serializer,
        EventDispatcherInterface $eventDispatcher,
        ZmqConnectionFactoryInterface $connectionFactory
    ) {
        $this->router = $router;
        $this->serializer = $serializer;
        $this->eventDispatcher = $eventDispatcher;
        $this->connectionFactory = $connectionFactory;
    }

    public function handle(LoopInterface $loop, PushableWampServerInterface $app): void
    {
        $this->consumer = $this->connectionFactory->createWrappedConnection($loop, \ZMQ::SOCKET_PULL);

        if ($this->logger) {
            $this->logger->info(
                sprintf(
                    'ZMQ transport listening on %s',
                    $this->connectionFactory->buildConnectionDsn()
                )
            );
        }

        $this->consumer->bind($this->connectionFactory->buildConnectionDsn());

        $this->consumer->on(
            'message',
            function ($data) use ($app) {
                try {
                    /** @var Message $message */
                    $message = $this->serializer->deserialize($data, Message::class, 'json');
                    $request = $this->router->match(new Topic($message->getTopic()));
                    $app->onPush($request, $message->getData(), $this->getName());

                    $this->eventDispatcher->dispatch(Events::PUSHER_SUCCESS, new PushHandlerEvent($data, $this));
                } catch (\Exception $e) {
                    if ($this->logger) {
                        $this->logger->error(
                            'ZMQ handler failed to ack message',
                            [
                                'exception' => $e,
                                'message' => $data,
                            ]
                        );
                    }

                    $this->eventDispatcher->dispatch(Events::PUSHER_FAIL, new PushHandlerEvent($data, $this));
                }
            }
        );
    }

    public function close(): void
    {
        if (null !== $this->consumer) {
            $this->consumer->close();
        }
    }
}

<?php declare(strict_types=1);

namespace Gos\Bundle\WebSocketBundle\Pusher\Amqp;

use Gos\Bundle\WebSocketBundle\Event\PushHandlerEvent;
use Gos\Bundle\WebSocketBundle\GosWebSocketEvents;
use Gos\Bundle\WebSocketBundle\Pusher\AbstractServerPushHandler;
use Gos\Bundle\WebSocketBundle\Pusher\Message;
use Gos\Bundle\WebSocketBundle\Router\WampRouter;
use Gos\Bundle\WebSocketBundle\Server\App\PushableWampServerInterface;
use Gos\Component\ReactAMQP\Consumer;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Ratchet\Wamp\Topic;
use React\EventLoop\LoopInterface;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

final class AmqpServerPushHandler extends AbstractServerPushHandler implements LoggerAwareInterface
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
     * @var Consumer
     */
    private $consumer;

    /**
     * @var EventDispatcherInterface
     */
    private $eventDispatcher;

    /**
     * @var AmqpConnectionFactoryInterface
     */
    private $connectionFactory;

    /**
     * @var \AMQPConnection
     */
    private $connection;

    public function __construct(
        WampRouter $router,
        SerializerInterface $serializer,
        EventDispatcherInterface $eventDispatcher,
        AmqpConnectionFactoryInterface $connectionFactory
    ) {
        $this->router = $router;
        $this->serializer = $serializer;
        $this->eventDispatcher = $eventDispatcher;
        $this->connectionFactory = $connectionFactory;
    }

    public function handle(LoopInterface $loop, PushableWampServerInterface $app): void
    {
        $this->connection = $this->connectionFactory->createConnection();
        $this->connection->connect();

        $this->consumer = new Consumer($this->connectionFactory->createQueue($this->connection), $loop, 0.1, 10);
        $this->consumer->on(
            'consume',
            function (\AMQPEnvelope $envelope, \AMQPQueue $queue) use ($app): void {
                try {
                    /** @var Message $message */
                    $message = $this->serializer->deserialize($envelope->getBody(), Message::class, 'json');
                    $request = $this->router->match(new Topic($message->getTopic()));
                    $app->onPush($request, $message->getData(), $this->getName());
                    $queue->ack($envelope->getDeliveryTag());
                    $this->eventDispatcher->dispatch(GosWebSocketEvents::PUSHER_SUCCESS, new PushHandlerEvent($envelope->getBody(), $this));
                } catch (\Exception $e) {
                    if (null !== $this->logger) {
                        $this->logger->error(
                            'AMQP handler failed to ack message',
                            [
                                'exception' => $e,
                                'message' => $envelope->getBody(),
                            ]
                        );
                    }

                    $queue->reject($envelope->getDeliveryTag());
                    $this->eventDispatcher->dispatch(
                        GosWebSocketEvents::PUSHER_FAIL,
                        new PushHandlerEvent($envelope->getBody(), $this)
                    );
                }

                if (null !== $this->logger) {
                    $this->logger->info(
                        sprintf(
                            'AMQP transport listening on %s:%s',
                            $this->connection->getHost(),
                            $this->connection->getPort()
                        )
                    );
                }
            }
        );
    }

    public function close(): void
    {
        if (null !== $this->consumer) {
            $this->consumer->emit('close_amqp_consumer');
        }

        if (null !== $this->connection) {
            $this->connection->disconnect();
        }
    }
}

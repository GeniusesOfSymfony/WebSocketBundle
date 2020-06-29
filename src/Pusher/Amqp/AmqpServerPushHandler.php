<?php declare(strict_types=1);

namespace Gos\Bundle\WebSocketBundle\Pusher\Amqp;

use Gos\Bundle\WebSocketBundle\Event\PushHandlerFailEvent;
use Gos\Bundle\WebSocketBundle\Event\PushHandlerSuccessEvent;
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

trigger_deprecation('gos/web-socket-bundle', '3.1', 'The "%s" class is deprecated and will be removed in 4.0, use the symfony/messenger component instead.', AmqpServerPushHandler::class);

/**
 * @deprecated to be removed in 4.0, use the symfony/messenger component instead
 */
final class AmqpServerPushHandler extends AbstractServerPushHandler implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    private WampRouter $router;
    private SerializerInterface $serializer;
    private Consumer $consumer;
    private EventDispatcherInterface $eventDispatcher;
    private AmqpConnectionFactoryInterface $connectionFactory;
    private \AMQPConnection $connection;

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
                    $this->eventDispatcher->dispatch(new PushHandlerSuccessEvent($envelope->getBody(), $this), GosWebSocketEvents::PUSHER_SUCCESS);
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
                    $this->eventDispatcher->dispatch(new PushHandlerFailEvent($envelope->getBody(), $this), GosWebSocketEvents::PUSHER_FAIL);
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

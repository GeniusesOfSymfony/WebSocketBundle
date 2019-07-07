<?php declare(strict_types=1);

namespace Gos\Bundle\WebSocketBundle\Pusher\Amqp;

use Gos\Bundle\WebSocketBundle\Event\Events;
use Gos\Bundle\WebSocketBundle\Event\PushHandlerEvent;
use Gos\Bundle\WebSocketBundle\Pusher\AbstractServerPushHandler;
use Gos\Bundle\WebSocketBundle\Pusher\Message;
use Gos\Bundle\WebSocketBundle\Router\WampRouter;
use Gos\Component\ReactAMQP\Consumer;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Ratchet\Wamp\Topic;
use Ratchet\Wamp\WampServerInterface;
use React\EventLoop\LoopInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Serializer\SerializerInterface;

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

    public function handle(LoopInterface $loop, WampServerInterface $app): void
    {
        $connection = $this->connectionFactory->createConnection();

        $connection->connect();

        $this->consumer = new Consumer($this->connectionFactory->createQueue($connection), $loop, 0.1, 10);
        $this->consumer->on(
            'consume',
            function (\AMQPEnvelope $envelop, \AMQPQueue $queue) use ($app, $connection) {
                try {
                    /** @var Message $message */
                    $message = $this->serializer->deserialize($envelop->getBody(), Message::class, 'json');
                    $request = $this->router->match(new Topic($message->getTopic()));
                    $app->onPush($request, $message->getData(), $this->getName());
                    $queue->ack($envelop->getDeliveryTag());
                    $this->eventDispatcher->dispatch(Events::PUSHER_SUCCESS, new PushHandlerEvent($envelop->getBody(), $this));
                } catch (\Exception $e) {
                    if ($this->logger) {
                        $this->logger->error(
                            'AMQP handler failed to ack message',
                            [
                                'exception' => $e,
                                'message' => $envelop->getBody(),
                            ]
                        );
                    }

                    $queue->reject($envelop->getDeliveryTag());
                    $this->eventDispatcher->dispatch(
                        Events::PUSHER_FAIL,
                        new PushHandlerEvent($envelop->getBody(), $this)
                    );
                }

                if ($this->logger) {
                    $this->logger->info(
                        sprintf(
                            'AMQP transport listening on %s:%s',
                            $connection->getHost(),
                            $connection->getPort()
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
    }
}

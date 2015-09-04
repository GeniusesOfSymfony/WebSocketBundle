<?php

namespace Gos\Bundle\WebSocketBundle\Pusher\Amqp;

use Gos\Bundle\WebSocketBundle\Event\Events;
use Gos\Bundle\WebSocketBundle\Event\PushHandlerEvent;
use Gos\Bundle\WebSocketBundle\Pusher\AbstractServerPushHandler;
use Gos\Bundle\WebSocketBundle\Pusher\MessageInterface;
use Gos\Bundle\WebSocketBundle\Pusher\PusherInterface;
use Gos\Bundle\WebSocketBundle\Pusher\Serializer\MessageSerializer;
use Gos\Bundle\WebSocketBundle\Router\WampRouter;
use Gos\Component\ReactAMQP\Consumer;
use Psr\Log\LoggerInterface;
use Ratchet\Wamp\Topic;
use Ratchet\Wamp\WampServerInterface;
use React\EventLoop\LoopInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpKernel\Log\NullLogger;

class AmqpServerPushHandler extends AbstractServerPushHandler
{
    /** @var PusherInterface  */
    protected $pusher;

    /** @var  LoggerInterface */
    protected $logger;

    /** @var  WampRouter */
    protected $router;

    /** @var  MessageSerializer */
    protected $serializer;

    /** @var  Consumer */
    protected $consumer;

    /** @var  EventDispatcherInterface */
    protected $eventDispatcher;

    /**
     * @param AmqpPusher               $pusher
     * @param WampRouter               $router
     * @param MessageSerializer        $serializer
     * @param EventDispatcherInterface $eventDispatcher
     * @param LoggerInterface|null     $logger
     */
    public function __construct(
        PusherInterface $pusher,
        WampRouter $router,
        MessageSerializer $serializer,
        EventDispatcherInterface $eventDispatcher,
        LoggerInterface $logger = null
    ) {
        $this->pusher = $pusher;
        $this->router = $router;
        $this->serializer = $serializer;
        $this->eventDispatcher = $eventDispatcher;
        $this->logger = $logger === null ? new NullLogger() : $logger;
    }

    /**
     * @param LoopInterface       $loop
     * @param WampServerInterface $app
     */
    public function handle(LoopInterface $loop, WampServerInterface $app)
    {
        $config = $this->pusher->getConfig();

        $connection = new \AMQPConnection($config);
        $connection->connect();

        list(, , $queue) = Utils::setupConnection($connection, $config);

        $this->consumer = new Consumer($queue, $loop, 0.1, 10);
        $this->consumer->on('consume', function (\AMQPEnvelope $envelop, \AMQPQueue $queue) use ($app, $config) {

            try {
                /** @var MessageInterface $message */
                $message = $this->serializer->deserialize($envelop->getBody());
                $request = $this->router->match(new Topic($message->getTopic()));
                $app->onPush($request, $message->getData(), $this->getName());
                $queue->ack($envelop->getDeliveryTag());
                $this->eventDispatcher->dispatch(Events::PUSHER_SUCCESS, new PushHandlerEvent($message, $this));
            } catch (\Exception $e) {
                $this->logger->error(
                    'AMQP handler failed to ack message', [
                        'exception_message' => $e->getMessage(),
                        'file' => $e->getFile(),
                        'line' => $e->getLine(),
                        'message' => $envelop->getBody(),
                    ]
                );

                $queue->reject($envelop->getDeliveryTag());
                $this->eventDispatcher->dispatch(Events::PUSHER_FAIL, new PushHandlerEvent($envelop->getBody(), $this));
            }

            $this->logger->info(sprintf(
                'AMQP transport listening on %s:%s',
                $config['host'],
                $config['port']
            ));
        });
    }

    public function close()
    {
        if (null !== $this->consumer) {
            $this->consumer->emit('close_amqp_consumer');
        }
    }
}

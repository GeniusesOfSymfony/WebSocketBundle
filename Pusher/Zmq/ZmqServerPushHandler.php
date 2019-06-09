<?php

namespace Gos\Bundle\WebSocketBundle\Pusher\Zmq;

use Gos\Bundle\WebSocketBundle\Event\Events;
use Gos\Bundle\WebSocketBundle\Event\PushHandlerEvent;
use Gos\Bundle\WebSocketBundle\Pusher\AbstractServerPushHandler;
use Gos\Bundle\WebSocketBundle\Pusher\MessageInterface;
use Gos\Bundle\WebSocketBundle\Pusher\Serializer\MessageSerializer;
use Gos\Bundle\WebSocketBundle\Router\WampRouter;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Ratchet\Wamp\Topic;
use Ratchet\Wamp\WampServerInterface;
use React\EventLoop\LoopInterface;
use React\ZMQ\SocketWrapper;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class ZmqServerPushHandler extends AbstractServerPushHandler implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    /**
     * @var WampRouter
     */
    protected $router;

    /**
     * @var MessageSerializer
     */
    protected $serializer;

    /**
     * @var SocketWrapper
     */
    protected $consumer;

    /**
     * @var EventDispatcherInterface
     */
    protected $eventDispatcher;

    /**
     * @var ZmqConnectionFactory
     */
    protected $connectionFactory;

    /**
     * @param WampRouter               $router
     * @param MessageSerializer        $serializer
     * @param EventDispatcherInterface $eventDispatcher
     * @param ZmqConnectionFactory     $connectionFactory
     */
    public function __construct(
        WampRouter $router,
        MessageSerializer $serializer,
        EventDispatcherInterface $eventDispatcher,
        ZmqConnectionFactory $connectionFactory
    ) {
        $this->router = $router;
        $this->eventDispatcher = $eventDispatcher;
        $this->serializer = $serializer;
        $this->connectionFactory = $connectionFactory;
    }

    /**
     * @param LoopInterface       $loop
     * @param WampServerInterface $app
     */
    public function handle(LoopInterface $loop, WampServerInterface $app)
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
                    /** @var MessageInterface $message */
                    $message = $this->serializer->deserialize($data);
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

    public function close()
    {
        $this->consumer->close();
    }
}

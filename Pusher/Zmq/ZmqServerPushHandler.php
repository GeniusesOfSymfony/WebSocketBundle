<?php

namespace Gos\Bundle\WebSocketBundle\Pusher\Zmq;

use Gos\Bundle\WebSocketBundle\Event\Events;
use Gos\Bundle\WebSocketBundle\Event\PushHandlerEvent;
use Gos\Bundle\WebSocketBundle\Pusher\AbstractServerPushHandler;
use Gos\Bundle\WebSocketBundle\Pusher\MessageInterface;
use Gos\Bundle\WebSocketBundle\Pusher\PusherInterface;
use Gos\Bundle\WebSocketBundle\Pusher\Serializer\MessageSerializer;
use Gos\Bundle\WebSocketBundle\Router\WampRouter;
use Psr\Log\LoggerInterface;
use Ratchet\Wamp\Topic;
use Ratchet\Wamp\WampServerInterface;
use React\EventLoop\LoopInterface;
use React\ZMQ\SocketWrapper;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpKernel\Log\NullLogger;

class ZmqServerPushHandler extends AbstractServerPushHandler
{
    /** @var PusherInterface  */
    protected $pusher;

    /** @var LoggerInterface */
    protected $logger;

    /** @var WampRouter */
    protected $router;

    /** @var MessageSerializer */
    protected $serializer;

    /** @var SocketWrapper */
    protected $consumer;

    /** @var EventDispatcherInterface */
    protected $eventDispatcher;

    /** @var ZmqConnectionFactory */
    protected $connectionFactory;

    /**
     * @param PusherInterface          $pusher
     * @param WampRouter               $router
     * @param MessageSerializer        $serializer
     * @param EventDispatcherInterface $eventDispatcher
     * @param ZmqConnectionFactory     $connectionFactory
     * @param LoggerInterface|null     $logger
     */
    public function __construct(
        PusherInterface $pusher,
        WampRouter $router,
        MessageSerializer $serializer,
        EventDispatcherInterface $eventDispatcher,
        ZmqConnectionFactory $connectionFactory,
        LoggerInterface $logger = null
    ) {
        $this->pusher = $pusher;
        $this->router = $router;
        $this->eventDispatcher = $eventDispatcher;
        $this->serializer = $serializer;
        $this->connectionFactory = $connectionFactory;
        $this->logger = $logger === null ? new NullLogger() : $logger;
    }

    /**
     * @param LoopInterface       $loop
     * @param WampServerInterface $app
     */
    public function handle(LoopInterface $loop, WampServerInterface $app)
    {
        $this->consumer = $this->connectionFactory->createWrappedConnection($loop, \ZMQ::SOCKET_PULL);

        $this->logger->info(sprintf(
            'ZMQ transport listening on %s',
            $this->connectionFactory->buildConnectionDsn()
        ));

        $this->consumer->bind($this->connectionFactory->buildConnectionDsn());

        $this->consumer->on('message', function ($data) use ($app) {
            try {
                /** @var MessageInterface $message */
                $message = $this->serializer->deserialize($data);
                $request = $this->router->match(new Topic($message->getTopic()));
                $app->onPush($request, $message->getData(), $this->getName());

                $this->eventDispatcher->dispatch(Events::PUSHER_SUCCESS, new PushHandlerEvent($data, $this));
            } catch (\Exception $e) {
                $this->logger->error(
                    'ZMQ handler failed to ack message', [
                        'exception' => $e,
                        'message' => $data,
                    ]
                );

                $this->eventDispatcher->dispatch(Events::PUSHER_FAIL, new PushHandlerEvent($data, $this));
            }
        });
    }

    public function close()
    {
        $this->consumer->close();
    }
}

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
use React\ZMQ\Context;
use React\ZMQ\SocketWrapper;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpKernel\Log\NullLogger;

class ZmqServerPushHandler extends AbstractServerPushHandler
{
    /** @var PusherInterface  */
    protected $pusher;

    /** @var  LoggerInterface */
    protected $logger;

    /** @var  WampRouter */
    protected $router;

    /** @var  MessageSerializer */
    protected $serializer;

    /** @var  Context */
    protected $consumer;

    /** @var  EventDispatcherInterface */
    protected $eventDispatcher;

    /**
     * @param PusherInterface                $pusher
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
        $this->eventDispatcher = $eventDispatcher;
        $this->serializer = $serializer;
        $this->logger = $logger === null ? new NullLogger() : $logger;
    }

    /**
     * @param LoopInterface       $loop
     * @param WampServerInterface $app
     */
    public function handle(LoopInterface $loop, WampServerInterface $app)
    {
        $config = $this->getConfig();

        $context = new Context($loop);

        /* @var SocketWrapper $pull */
        $this->consumer = $context->getSocket(\ZMQ::SOCKET_PULL);

        $this->logger->info(sprintf(
            'ZMQ transport listening on %s:%s',
            $config['host'],
            $config['port']
        ));

        $this->consumer->bind('tcp://' . $config['host'] . ':' . $config['port']);

        $this->consumer->on('message', function ($data) use ($app, $config) {

            try {
                /** @var MessageInterface $message */
                $message = $this->serializer->deserialize($data);
                $request = $this->router->match(new Topic($message->getTopic()));
                $app->onPush($request, $message->getData(), $this->getName());

                $this->eventDispatcher->dispatch(Events::PUSHER_SUCCESS, new PushHandlerEvent($data, $this));
            } catch (\Exception $e) {
                $this->logger->error(
                    'AMQP handler failed to ack message', [
                        'exception_message' => $e->getMessage(),
                        'file' => $e->getFile(),
                        'line' => $e->getLine(),
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

<?php

namespace Gos\Bundle\WebSocketBundle\Pusher\Zmq;

use Gos\Bundle\WebSocketBundle\Pusher\MessageInterface;
use Gos\Bundle\WebSocketBundle\Pusher\PusherInterface;
use Gos\Bundle\WebSocketBundle\Pusher\Serializer\MessageSerializer;
use Gos\Bundle\WebSocketBundle\Pusher\ServerPushHandlerInterface;
use Gos\Bundle\WebSocketBundle\Router\WampRouter;
use Psr\Log\LoggerInterface;
use Ratchet\Wamp\Topic;
use Ratchet\Wamp\WampServerInterface;
use React\EventLoop\LoopInterface;
use React\ZMQ\Context;
use React\ZMQ\SocketWrapper;
use Symfony\Component\HttpKernel\Log\NullLogger;

class ZmqServerPushHandler implements ServerPushHandlerInterface
{
    /** @var PusherInterface  */
    protected $pusher;

    /** @var  LoggerInterface */
    protected $logger;

    /** @var  WampRouter */
    protected $router;

    /** @var  MessageSerializer */
    protected $serializer;

    /**
     * @param PusherInterface      $pusher
     * @param WampRouter           $router
     * @param MessageSerializer    $serializer
     * @param LoggerInterface|null $logger
     */
    public function __construct(
        ZmqPusher $pusher,
        WampRouter $router,
        MessageSerializer $serializer,
        LoggerInterface $logger = null
    ) {
        $this->pusher = $pusher;
        $this->router = $router;
        $this->serializer = $serializer;
        $this->logger = $logger === null ? new NullLogger() : $logger;
    }

    /**
     * @param LoopInterface       $loop
     * @param WampServerInterface $app
     */
    public function handle(LoopInterface $loop, WampServerInterface $app)
    {
        $config = $this->pusher->getConfig();

        $context = new Context($loop);

        /** @var SocketWrapper $pull */
        $pull = $context->getSocket(\ZMQ::SOCKET_PULL);

        $this->logger->info(sprintf(
            'ZMQ transport listening on %s:%s',
            $config['host'],
            $config['port']
        ));

        $pull->bind('tcp://'.$config['host'].':'.$config['port']);

        $pull->on('message', function($data) use ($app, $config) {
            /** @var MessageInterface $message */
            $message = $this->serializer->deserialize($data);
            $request = $this->router->match(new Topic($message->getTopic()));
            $app->onPush($request, $message->getData(), $config['type']);
        });
    }
}

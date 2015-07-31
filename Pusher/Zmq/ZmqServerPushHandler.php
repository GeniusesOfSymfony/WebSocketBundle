<?php

namespace Gos\Bundle\WebSocketBundle\Pusher\Zmq;

use Gos\Bundle\WebSocketBundle\Pusher\PusherInterface;
use Gos\Bundle\WebSocketBundle\Pusher\ServerPushHandlerInterface;
use Psr\Log\LoggerInterface;
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

    /**
     * @param PusherInterface $pusher
     * @param LoggerInterface $logger
     */
    public function __construct(PusherInterface $pusher, LoggerInterface $logger = null)
    {
        $this->pusher = $pusher;
        $this->logger = $logger === null ? new NullLogger() : $logger;
    }

    /**
     * @param LoopInterface       $loop
     * @param WampServerInterface $app
     */
    public function handle(LoopInterface $loop, WampServerInterface $app)
    {
        $pusherConfig = $this->pusher->getConfig();

        $context = new Context($loop);

        /** @var SocketWrapper $pull */
        $pull = $context->getSocket(\ZMQ::SOCKET_PULL);

        $this->logger->info(sprintf(
            'ZMQ transport listening on %s:%s',
            $pusherConfig['host'],
            $pusherConfig['port']
        ));

        $pull->bind('tcp://'.$pusherConfig['host'].':'.$pusherConfig['port']);
        $pull->on('message', array($app, 'onPush'));
    }
}

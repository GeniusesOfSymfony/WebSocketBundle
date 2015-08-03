<?php

namespace Gos\Bundle\WebSocketBundle\Pusher\Amqp;

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

    /**
     * @param PusherInterface      $pusher
     * @param WampRouter           $router
     * @param MessageSerializer    $serializer
     * @param LoggerInterface|null $logger
     */
    public function __construct(
        AmqpPusher $pusher,
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
            } catch (\Exception $e) {
                $this->logger->error(
                    'AMQP handler failed to ack message', [
                        'exception_message' => $e->getMessage(),
                        'file' => $e->getFile(),
                        'line' => $e->getLine(),
                        'message' => $envelop->getBody(),
                    ]
                );

                //Maybe send an event to capture failed message in order to store them ???
                $queue->reject($envelop->getDeliveryTag());
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

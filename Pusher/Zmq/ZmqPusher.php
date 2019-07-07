<?php declare(strict_types=1);

namespace Gos\Bundle\WebSocketBundle\Pusher\Zmq;

use Gos\Bundle\WebSocketBundle\Pusher\AbstractPusher;
use Gos\Bundle\WebSocketBundle\Pusher\Serializer\MessageSerializer;
use Gos\Bundle\WebSocketBundle\Router\WampRouter;

final class ZmqPusher extends AbstractPusher
{
    /**
     * @var \ZMQSocket
     */
    private $connection;

    /**
     * @var ZmqConnectionFactoryInterface
     */
    private $connectionFactory;

    public function __construct(
        WampRouter $router,
        MessageSerializer $serializer,
        ZmqConnectionFactoryInterface $connectionFactory
    ) {
        parent::__construct($router, $serializer);

        $this->connectionFactory = $connectionFactory;
    }

    /**
     * @param string|array $data
     */
    protected function doPush($data, array $context): void
    {
        if (false === $this->isConnected()) {
            $this->connection = $this->connectionFactory->createConnection();
            $this->connection->connect($this->connectionFactory->buildConnectionDsn());
            $this->setConnected();
        }

        $this->connection->send($data);
    }

    public function close(): void
    {
        if (false === $this->isConnected()) {
            return;
        }

        $this->connection->disconnect($this->connectionFactory->buildConnectionDsn());
    }
}

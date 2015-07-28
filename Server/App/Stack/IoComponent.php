<?php

namespace Gos\Bundle\WebSocketBundle\Server\App\Stack;

use Gos\Bundle\WebSocketBundle\Server\App\Application\ApplicationInterface;
use Gos\Bundle\WebSocketBundle\Server\App\Connection\Connection;
use Gos\Bundle\WebSocketBundle\Server\App\Connection\SocketConnectionInterface;
use React\EventLoop\LoopInterface;
use React\Socket\ConnectionInterface;
use React\Socket\ServerInterface;

class IoComponent
{
    /**
     * @var LoopInterface
     */
    protected $loop;

    /**
     * @var ServerInterface
     */
    protected $socket;

    /**
     * @var ApplicationInterface
     */
    protected $application;

    /**
     * @param ApplicationInterface $application
     * @param ServerInterface      $socket
     * @param LoopInterface        $loop
     */
    public function __construct(ApplicationInterface $application, ServerInterface $socket, LoopInterface $loop)
    {
        $this->loop = $loop;
        $this->socket = $socket;
        $this->application = $application;

        $this->initialize();
    }

    protected function initialize()
    {
        if (false === strpos(PHP_VERSION, 'hiphop')) {
            gc_enable();
        }

        set_time_limit(0);
        ob_implicit_flush();

        $this->socket->on('connection', function (ConnectionInterface $connection) {

            //Decorate the connection
            $connection = new Connection($connection);
            $connection->on('data', [$this, 'handleData']);
            $connection->on('disconnect', [$this, 'handleDisconnect']);
            $connection->on('error', [$this, 'handleError']);

            //Forward to our application
            $this->application->emit('connection', $connection);
        });
    }

    /**
     * @param string                    $data
     * @param SocketConnectionInterface $connection
     */
    public function handleData($data, SocketConnectionInterface $connection)
    {
        try {
            $this->application->emit('data', [$connection, $data]);
        } catch (\Exception $e) {
            $this->handleError($connection, $e);
        }
    }

    /**
     * @param SocketConnectionInterface $connection
     */
    public function handleDisconnect(SocketConnectionInterface $connection)
    {
        try {
            $this->application->emit('disconnect', [$connection]);
        } catch (\Exception $e) {
            $this->handleError($connection, $e);
        }

        unset($connection);
    }

    /**
     * @param SocketConnectionInterface $connection
     * @param \Exception                $e
     */
    public function handleError(SocketConnectionInterface $connection, \Exception $e)
    {
        $this->application->emit('error', [$connection, $e]);
    }

    public function run()
    {
        $this->loop->run();
    }
}

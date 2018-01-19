<?php

namespace Gos\Bundle\WebSocketBundle\Server\App\Stack;

use Gos\Bundle\WebSocketBundle\Event\ClientRejectedEvent;
use Gos\Bundle\WebSocketBundle\Event\Events;
use GuzzleHttp\Psr7 as gPsr;
use GuzzleHttp\Psr7\Response;
use Psr\Http\Message\RequestInterface;
use Ratchet\ConnectionInterface;
use Ratchet\Http\OriginCheck as BaseOriginCheck;
use Ratchet\MessageComponentInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * @author Johann Saunier <johann_27@hotmail.fr>
 */
class OriginCheck extends BaseOriginCheck
{
    /**
     * @var EventDispatcherInterface
     */
    protected $eventDispatcher;
    /**
     * @var bool
     */
    private $check;

    /**
     * @param MessageComponentInterface $component
     * @param bool                      $check
     * @param string[]                  $allowed
     * @param EventDispatcherInterface  $eventDispatcher
     */
    public function __construct(
        MessageComponentInterface $component,
        $check = true,
        array $allowed = [],
        EventDispatcherInterface $eventDispatcher
    )
    {
        $this->eventDispatcher = $eventDispatcher;
        parent::__construct($component, $allowed);
        $this->check = $check;
    }

    /**
     * {@inheritdoc}
     */
    public function onOpen(ConnectionInterface $conn, RequestInterface $request = null)
    {
        if (!$this->check) {
            return $this->_component->onOpen($conn, $request);
        }

        $header = (string)$request->getHeaderLine('Origin');

        $origin = parse_url($header, PHP_URL_HOST) ?: $header;

        if (!in_array($origin, $this->allowedOrigins)) {
            $this->eventDispatcher->dispatch(
                Events::CLIENT_REJECTED,
                new ClientRejectedEvent($origin, $request)
            );

            return $this->close($conn, 403);
        }

        return $this->_component->onOpen($conn, $request);
    }

    /**
     * Close a connection with an HTTP response
     *
     * @param \Ratchet\ConnectionInterface $conn
     * @param int                          $code HTTP status code
     */
    protected function close(ConnectionInterface $conn, $code = 400, array $additional_headers = [])
    {
        $response = new Response($code, array_merge([
            'X-Powered-By' => \Ratchet\VERSION,
        ], $additional_headers));

        $conn->send(gPsr\str($response));
        $conn->close();
    }
}

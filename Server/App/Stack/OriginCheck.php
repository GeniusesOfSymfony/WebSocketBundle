<?php

namespace Gos\Bundle\WebSocketBundle\Server\App\Stack;

use Gos\Bundle\WebSocketBundle\Event\ClientRejectedEvent;
use Gos\Bundle\WebSocketBundle\Event\Events;
use Guzzle\Http\Message\RequestInterface;
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
     * @param MessageComponentInterface $component
     * @param string[]                  $allowed
     * @param EventDispatcherInterface  $eventDispatcher
     */
    public function __construct(
        MessageComponentInterface $component,
        array $allowed = array(),
        EventDispatcherInterface $eventDispatcher
    ) {
        $this->eventDispatcher = $eventDispatcher;
        parent::__construct($component, $allowed);
    }

    /**
     * {@inheritdoc}
     */
    public function onOpen(ConnectionInterface $conn, RequestInterface $request = null)
    {
        $header = (string) $request->getHeader('Origin');
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
}

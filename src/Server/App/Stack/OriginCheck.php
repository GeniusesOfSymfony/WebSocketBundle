<?php declare(strict_types=1);

namespace Gos\Bundle\WebSocketBundle\Server\App\Stack;

use Gos\Bundle\WebSocketBundle\Event\ClientRejectedEvent;
use Gos\Bundle\WebSocketBundle\Event\ConnectionRejectedEvent;
use Gos\Bundle\WebSocketBundle\GosWebSocketEvents;
use Psr\Http\Message\RequestInterface;
use Ratchet\ConnectionInterface;
use Ratchet\Http\CloseResponseTrait;
use Ratchet\Http\OriginCheck as BaseOriginCheck;
use Ratchet\MessageComponentInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

/**
 * @author Johann Saunier <johann_27@hotmail.fr>
 */
class OriginCheck extends BaseOriginCheck
{
    use CloseResponseTrait;

    protected EventDispatcherInterface $eventDispatcher;

    /**
     * @param string[] $allowed
     */
    public function __construct(
        EventDispatcherInterface $eventDispatcher,
        MessageComponentInterface $component,
        array $allowed = []
    ) {
        $this->eventDispatcher = $eventDispatcher;

        parent::__construct($component, $allowed);
    }

    public function onOpen(ConnectionInterface $conn, RequestInterface $request = null)
    {
        if ($request) {
            $header = (string) $request->getHeaderLine('Origin');
            $origin = parse_url($header, \PHP_URL_HOST) ?: $header;

            if (!\in_array($origin, $this->allowedOrigins)) {
                // Dispatch deprecated event, then new event
                $this->eventDispatcher->dispatch(new ClientRejectedEvent($origin, $request), GosWebSocketEvents::CLIENT_REJECTED);
                $this->eventDispatcher->dispatch(new ConnectionRejectedEvent($conn, $request), GosWebSocketEvents::CONNECTION_REJECTED);

                return $this->close($conn, 403);
            }
        }

        return $this->_component->onOpen($conn, $request);
    }
}

<?php declare(strict_types=1);

namespace Gos\Bundle\WebSocketBundle\Server\App\Stack;

use Gos\Bundle\WebSocketBundle\Event\ClientRejectedEvent;
use Gos\Bundle\WebSocketBundle\GosWebSocketEvents as Events;
use Psr\Http\Message\RequestInterface;
use Ratchet\ConnectionInterface;
use Ratchet\Http\CloseResponseTrait;
use Ratchet\Http\OriginCheck as BaseOriginCheck;
use Ratchet\MessageComponentInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * @author Johann Saunier <johann_27@hotmail.fr>
 */
class OriginCheck extends BaseOriginCheck
{
    use CloseResponseTrait;

    /**
     * @var EventDispatcherInterface
     */
    protected $eventDispatcher;

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

    /**
     * {@inheritdoc}
     */
    public function onOpen(ConnectionInterface $conn, RequestInterface $request = null)
    {
        if ($request) {
            $header = (string) $request->getHeaderLine('Origin');
            $origin = parse_url($header, PHP_URL_HOST) ?: $header;

            if (!\in_array($origin, $this->allowedOrigins)) {
                $this->eventDispatcher->dispatch(
                    new ClientRejectedEvent($origin, $request),
                    Events::CLIENT_REJECTED
                );

                return $this->close($conn, 403);
            }
        }

        return $this->_component->onOpen($conn, $request);
    }
}

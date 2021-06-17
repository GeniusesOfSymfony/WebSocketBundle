<?php declare(strict_types=1);

namespace Gos\Bundle\WebSocketBundle\Event;

use Psr\Http\Message\RequestInterface;
use Symfony\Contracts\EventDispatcher\Event;

trigger_deprecation('gos/web-socket-bundle', '3.8', 'The "%s" class is deprecated and will be removed in 4.0, subscribe to the "%s" event instead.', ClientRejectedEvent::class, ConnectionRejectedEvent::class);

/**
 * @author Johann Saunier <johann_27@hotmail.fr>
 * @deprecated to be removed in 4.0, subscribe to the `Gos\Bundle\WebSocketBundle\Event\ConnectionRejectedEvent` event instead
 */
final class ClientRejectedEvent extends Event
{
    private string $origin;
    private ?RequestInterface $request;

    public function __construct(string $origin, ?RequestInterface $request = null)
    {
        $this->origin = $origin;
        $this->request = $request;
    }

    public function getOrigin(): string
    {
        return $this->origin;
    }

    public function getRequest(): ?RequestInterface
    {
        return $this->request;
    }

    public function hasRequest(): bool
    {
        return null !== $this->request;
    }
}

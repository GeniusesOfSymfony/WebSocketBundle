<?php declare(strict_types=1);

namespace Gos\Bundle\WebSocketBundle\Event;

use Psr\Http\Message\RequestInterface;

/**
 * @author Johann Saunier <johann_27@hotmail.fr>
 */
class ClientRejectedEvent extends CompatibilityEvent
{
    /**
     * @var string
     */
    protected $origin;

    /**
     * @var RequestInterface|null
     */
    protected $request;

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

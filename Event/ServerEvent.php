<?php declare(strict_types=1);

namespace Gos\Bundle\WebSocketBundle\Event;

use React\EventLoop\LoopInterface;
use React\Socket\ServerInterface;
use Symfony\Component\EventDispatcher\Event;

@trigger_error(sprintf('The %s class is deprecated will be removed in 3.0. Use the %s class instead.', ServerEvent::class, ServerLaunchedEvent::class), E_USER_DEPRECATED);

/**
 * @deprecated to be removed in 3.0. Use the Gos\Bundle\WebSocketBundle\Event\ServerLaunchedEvent class instead.
 */
class ServerEvent extends Event
{
    /**
     * @var LoopInterface
     */
    protected $loop;

    /**
     * @var ServerInterface
     */
    protected $server;

    /**
     * @var bool
     */
    protected $profile;

    public function __construct(LoopInterface $loop, ServerInterface $server, bool $profile)
    {
        $this->loop = $loop;
        $this->server = $server;
        $this->profile = $profile;
    }

    public function getEventLoop(): LoopInterface
    {
        return $this->loop;
    }

    public function getServer(): ServerInterface
    {
        return $this->server;
    }

    public function isProfiling(): bool
    {
        return $this->profile;
    }
}

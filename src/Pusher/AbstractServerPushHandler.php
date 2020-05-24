<?php declare(strict_types=1);

namespace Gos\Bundle\WebSocketBundle\Pusher;

trigger_deprecation('gos/web-socket-bundle', '3.1', 'The "%s" class is deprecated and will be removed in 4.0, use the symfony/messenger component instead.', AbstractServerPushHandler::class);

/**
 * @deprecated to be removed in 4.0, use the symfony/messenger component instead
 */
abstract class AbstractServerPushHandler implements ServerPushHandlerInterface
{
    private string $name = '';

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function getName(): string
    {
        return $this->name;
    }
}

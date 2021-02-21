<?php declare(strict_types=1);

namespace Gos\Bundle\WebSocketBundle\Server;

trigger_deprecation('gos/web-socket-bundle', '3.7', 'The "%s" class is deprecated and will be removed in 4.0, use the "%s" class instead.', EntryPoint::class, ServerLauncher::class);

/**
 * @deprecated to be removed in 4.0, use the `Gos\Bundle\WebSocketBundle\Server\ServerLauncher` class instead
 */
final class EntryPoint extends ServerLauncher
{
}

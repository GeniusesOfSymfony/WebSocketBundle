<?php declare(strict_types=1);

namespace Gos\Bundle\WebSocketBundle\Pusher\Exception;

trigger_deprecation('gos/web-socket-bundle', '3.1', 'The "%s" class is deprecated and will be removed in 4.0.', PusherUnsupportedException::class);

/**
 * @deprecated to be removed in 4.0, use the symfony/messenger component instead
 */
final class PusherUnsupportedException extends \RuntimeException
{
}

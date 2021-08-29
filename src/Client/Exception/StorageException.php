<?php declare(strict_types=1);

namespace Gos\Bundle\WebSocketBundle\Client\Exception;

trigger_deprecation('gos/web-socket-bundle', '3.11', 'The "%s" class is deprecated and will be removed in 4.0, use the new websocket authentication API instead.', StorageException::class);

/**
 * @deprecated to be removed in 4.0, use the new websocket authentication API instead
 */
class StorageException extends \Exception
{
}

<?php declare(strict_types=1);

namespace Gos\Bundle\WebSocketBundle\Authentication\Storage\Exception;

use Gos\Bundle\WebSocketBundle\Exception\WebsocketException;

/**
 * General exception for token storage errors.
 */
class StorageException extends \RuntimeException implements WebsocketException
{
}

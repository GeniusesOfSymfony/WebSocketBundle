<?php declare(strict_types=1);

namespace Gos\Bundle\WebSocketBundle\Authentication\Storage\Exception;

use Gos\Bundle\WebSocketBundle\Exception\WebsocketException;

/**
 * Exception thrown when a token cannot be found in storage.
 */
class TokenNotFoundException extends \InvalidArgumentException implements WebsocketException
{
}

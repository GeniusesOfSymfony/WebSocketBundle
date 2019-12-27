<?php declare(strict_types=1);

namespace Gos\Bundle\WebSocketBundle\Client;

use Ratchet\ConnectionInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

final class ClientConnection implements \ArrayAccess
{
    public TokenInterface $client;
    public ConnectionInterface $connection;

    public function __construct(TokenInterface $client, ConnectionInterface $connection)
    {
        $this->client = $client;
        $this->connection = $connection;
    }

    public function offsetExists($offset): bool
    {
        @trigger_error(
            sprintf(
                'Accessing properties from %s as an array is deprecated, access the class properties directly.',
                self::class
            ),
            E_USER_DEPRECATED
        );

        return \in_array($offset, ['client', 'connection'], true);
    }

    public function offsetGet($offset)
    {
        @trigger_error(
            sprintf(
                'Accessing properties from %s as an array is deprecated, access the class properties directly.',
                self::class
            ),
            E_USER_DEPRECATED
        );

        switch ($offset) {
            case 'client':
                return $this->client;

            case 'connection':
                return $this->connection;

            default:
                $trace = debug_backtrace();

                trigger_error(
                    sprintf(
                        'Undefined property: %s in %s on line %s',
                        $offset,
                        $trace[0]['file'],
                        $trace[0]['line']
                    ),
                    E_USER_NOTICE
                );
        }
    }

    public function offsetSet($offset, $value): void
    {
        throw new \BadMethodCallException(sprintf('Properties of %s cannot be overwritten.', self::class));
    }

    public function offsetUnset($offset): void
    {
        throw new \BadMethodCallException(sprintf('Properties of %s cannot be unset.', self::class));
    }
}

<?php declare(strict_types=1);

namespace Gos\Bundle\WebSocketBundle\Client;

use Ratchet\ConnectionInterface;
use ReturnTypeWillChange;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

trigger_deprecation('gos/web-socket-bundle', '3.11', 'The "%s" class is deprecated and will be removed in 4.0, use the new websocket authentication API instead.', ClientConnection::class);

/**
 * @deprecated to be removed in 4.0, use the new websocket authentication API instead
 */
final class ClientConnection implements \ArrayAccess
{
    private TokenInterface $client;
    private ConnectionInterface $connection;

    public function __construct(TokenInterface $client, ConnectionInterface $connection)
    {
        $this->client = $client;
        $this->connection = $connection;
    }

    public function getClient(): TokenInterface
    {
        return $this->client;
    }

    public function getConnection(): ConnectionInterface
    {
        return $this->connection;
    }

    /**
     * @param int|string $offset
     */
    public function offsetExists($offset): bool
    {
        trigger_deprecation('gos/web-socket-bundle', '3.0', 'Accessing properties from %s as an array is deprecated and will be removed in 4.0, use the getters to access the properties.', self::class);

        return \in_array($offset, ['client', 'connection'], true);
    }

    /**
     * @param int|string $offset
     *
     * @return mixed
     */
    #[ReturnTypeWillChange]
    public function offsetGet($offset)
    {
        trigger_deprecation('gos/web-socket-bundle', '3.0', 'Accessing properties from %s as an array is deprecated and will be removed in 4.0, use the getters to access the properties.', self::class);

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
                    \E_USER_NOTICE
                );
        }
    }

    /**
     * @param int|string $offset
     * @param mixed $value
     *
     * @throws \BadMethodCallException as the object is immutable
     */
    public function offsetSet($offset, $value): void
    {
        throw new \BadMethodCallException(sprintf('Properties of %s cannot be overwritten.', self::class));
    }

    /**
     * @param int|string $offset
     *
     * @throws \BadMethodCallException as the object is immutable
     */
    public function offsetUnset($offset): void
    {
        throw new \BadMethodCallException(sprintf('Properties of %s cannot be unset.', self::class));
    }
}

<?php declare(strict_types=1);

namespace Gos\Bundle\WebSocketBundle\Client\Driver;

use Predis\ClientInterface;

@trigger_error(sprintf('The %s class is deprecated will be removed in 3.0. Predis will no longer be supported, use either doctrine/cache or symfony/cache and PHP\'s Redis extension instead.', PredisDriver::class), E_USER_DEPRECATED);

/**
 * @author Johann Saunier <johann_27@hotmail.fr>
 *
 * @deprecated to be removed in 3.0. Predis will no longer be supported.
 */
final class PredisDriver implements DriverInterface
{
    /**
     * @var ClientInterface
     */
    private $client;

    /**
     * @var string
     */
    private $prefix;

    public function __construct(ClientInterface $client, string $prefix = '')
    {
        $this->client = $client;
        $this->prefix = '' !== $prefix ? $prefix.':' : '';
    }

    /**
     * @return mixed
     */
    public function fetch(string $id)
    {
        $result = $this->client->get($this->prefix.$id);

        if (null === $result) {
            return false;
        }

        return $result;
    }

    public function contains(string $id): bool
    {
        return (bool) $this->client->exists($this->prefix.$id);
    }

    /**
     * @param mixed $data
     */
    public function save(string $id, $data, int $lifeTime = 0): bool
    {
        if ($lifeTime > 0) {
            $response = $this->client->setex($this->prefix.$id, $lifeTime, $data);
        } else {
            $response = $this->client->set($this->prefix.$id, $data);
        }

        return true === $response || 'OK' === $response;
    }

    public function delete(string $id): bool
    {
        return $this->client->del([$this->prefix.$id]) > 0;
    }
}

<?php

namespace Gos\Bundle\WebSocketBundle\Client\Driver;

use Predis\ClientInterface;

/**
 * @author Johann Saunier <johann_27@hotmail.fr>
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
        $this->prefix = $prefix !== '' ? $prefix . ':' : '';
    }

    /**
     * @return mixed
     */
    public function fetch(string $id)
    {
        $result = $this->client->get($this->prefix . $id);

        if (null === $result) {
            return false;
        }

        return $result;
    }

    public function contains(string $id): bool
    {
        return $this->client->exists($this->prefix . $id);
    }

    /**
     * @param mixed $data
     */
    public function save(string $id, $data, int $lifeTime = 0): bool
    {
        if ($lifeTime > 0) {
            $response = $this->client->setex($this->prefix . $id, $lifeTime, $data);
        } else {
            $response = $this->client->set($this->prefix . $id, $data);
        }

        return $response === true || $response === 'OK';
    }

    public function delete(string $id): bool
    {
        return $this->client->del($this->prefix . $id) > 0;
    }
}

<?php

namespace Gos\Bundle\WebSocketBundle\Client\Driver;

use Predis\Client;

/**
 * @author Johann Saunier <johann_27@hotmail.fr>
 */
class PredisDriver implements DriverInterface
{
    /**
     * @var Client
     */
    protected $client;

    /**
     * @param Client $client
     */
    public function __construct(Client $client)
    {
        $this->client = $client;
    }

    /**
     * {@inheritdoc}
     */
    public function fetch($id)
    {
        $result = $this->client->get($id);
        if (null === $result) {
            return false;
        }

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function contains($id)
    {
        return $this->client->exists($id);
    }

    /**
     * {@inheritdoc}
     */
    public function save($id, $data, $lifeTime = 0)
    {
        if ($lifeTime > 0) {
            $response = $this->client->setex($id, $lifeTime, $data);
        } else {
            $response = $this->client->set($id, $data);
        }

        return $response === true || $response == 'OK';
    }

    /**
     * {@inheritdoc}
     */
    public function delete($id)
    {
        return $this->client->del($id) > 0;
    }
}

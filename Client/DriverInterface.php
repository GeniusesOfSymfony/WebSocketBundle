<?php

namespace Gos\Bundle\WebSocketBundle\Client;

interface DriverInterface
{
    /**
     * @param string $id
     *
     * @return mixed
     */
    public function fetch($id);

    /**
     * @param $id
     *
     * @return bool
     */
    public function contains($id);

    /**
     * @param string $id
     * @param mixed  $data
     * @param int    $lifeTime
     *
     * @return mixed
     */
    public function save($id, $data, $lifeTime = 0);

    /**
     * Deletes a cache entry.
     *
     * @param string $id The cache id.
     *
     * @return bool TRUE if the cache entry was successfully deleted, FALSE otherwise.
     */
    public function delete($id);
}

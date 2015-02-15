<?php
/**
 * This file is part of the notification.
 * (c) johann (johann_27@hotmail.fr)
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 **/
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
     * @return boolean TRUE if the cache entry was successfully deleted, FALSE otherwise.
     */
    public function delete($id);
}

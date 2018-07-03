<?php

namespace Gos\Bundle\WebSocketBundle\Client;

use Gos\Bundle\WebSocketBundle\Client\Driver\DoctrineCacheDriverDecorator as BaseDoctrineCacheDriverDecorator;

@trigger_error('The DoctrineCacheDriverDecorator class is deprecated will be removed in 2.0. Use the parent DoctrineCacheDriverDecorator instead.', E_USER_DEPRECATED);

/**
 * @author Johann Saunier <johann_27@hotmail.fr>
 *
 * @deprecated to be removed in 2.0. Use the parent DoctrineCacheDriverDecorator instead.
 */
class DoctrineCacheDriverDecorator extends BaseDoctrineCacheDriverDecorator implements DriverInterface
{
}

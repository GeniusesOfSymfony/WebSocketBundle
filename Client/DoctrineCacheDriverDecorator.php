<?php

namespace Gos\Bundle\WebSocketBundle\Client;

use Gos\Bundle\WebSocketBundle\Client\Driver\DoctrineCacheDriverDecorator as BaseDoctrineCacheDriverDecorator;

trigger_deprecation('gos/web-socket-bundle', '1.9', 'The %s class is deprecated will be removed in 2.0. Use the %s class instead.', DoctrineCacheDriverDecorator::class, BaseDoctrineCacheDriverDecorator::class);

/**
 * @author Johann Saunier <johann_27@hotmail.fr>
 *
 * @deprecated to be removed in 2.0. Use the parent DoctrineCacheDriverDecorator instead.
 */
class DoctrineCacheDriverDecorator extends BaseDoctrineCacheDriverDecorator implements DriverInterface
{
}

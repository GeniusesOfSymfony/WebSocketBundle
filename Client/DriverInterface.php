<?php

namespace Gos\Bundle\WebSocketBundle\Client;

use Gos\Bundle\WebSocketBundle\Client\Driver\DriverInterface as BaseDriverInterface;

trigger_deprecation('gos/web-socket-bundle', '1.9', 'The %s interface is deprecated will be removed in 2.0. Use the %s class instead.', DriverInterface::class, BaseDriverInterface::class);

/**
 * @deprecated to be removed in 2.0. Use the parent DriverInterface instead.
 */
interface DriverInterface extends BaseDriverInterface
{
}

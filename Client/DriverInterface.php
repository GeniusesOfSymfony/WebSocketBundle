<?php

namespace Gos\Bundle\WebSocketBundle\Client;

use Gos\Bundle\WebSocketBundle\Client\Driver\DriverInterface as BaseDriverInterface;

@trigger_error('The DriverInterface interface is deprecated will be removed in 2.0. Use the parent DriverInterface instead.', E_USER_DEPRECATED);

/**
 * @deprecated to be removed in 2.0. Use the parent DriverInterface instead.
 */
interface DriverInterface extends BaseDriverInterface
{
}

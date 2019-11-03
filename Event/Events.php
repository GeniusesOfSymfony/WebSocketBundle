<?php declare(strict_types=1);

namespace Gos\Bundle\WebSocketBundle\Event;

use Gos\Bundle\WebSocketBundle\GosWebSocketEvents;

@trigger_error(sprintf('The %s class is deprecated will be removed in 3.0. Use the %s class instead.', Events::class, GosWebSocketEvents::class), E_USER_DEPRECATED);

/**
 * @author Johann Saunier <johann_27@hotmail.fr>
 *
 * @deprecated to be removed in 3.0. Use the GosWebSocketEvents class instead.
 */
final class Events extends GosWebSocketEvents
{
}

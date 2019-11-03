<?php declare(strict_types=1);

namespace Gos\Bundle\WebSocketBundle\Event;

use Symfony\Component\EventDispatcher\Event as ComponentEvent;
use Symfony\Contracts\EventDispatcher\Event as ContractEvent;

if (class_exists(ContractEvent::class)) {
    @trigger_error(sprintf('The %s class is deprecated will be removed in 3.0. Event classes should directly extend from %s instead.', CompatibilityEvent::class, ContractEvent::class), E_USER_DEPRECATED);

    /**
     * @internal
     * @deprecated to be removed in 3.0
     */
    class CompatibilityEvent extends ContractEvent {}
} else {
    @trigger_error(sprintf('The %s class is deprecated will be removed in 3.0. Event classes should directly extend from %s instead.', CompatibilityEvent::class, ContractEvent::class), E_USER_DEPRECATED);

    /**
     * @internal
     * @deprecated to be removed in 3.0
     */
    class CompatibilityEvent extends ComponentEvent {}
}

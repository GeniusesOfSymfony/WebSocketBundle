<?php declare(strict_types=1);

namespace Gos\Bundle\WebSocketBundle\Event;

final class ClientErrorEvent extends ClientEvent
{
    private \Throwable $throwable;

    /**
     * @deprecated to be removed in 4.0, the Throwable will be a required constructor argument
     */
    public function setException(\Throwable $exception): void
    {
        trigger_deprecation(
            'gos/web-socket-bundle',
            '3.3',
            '%s() is deprecated and will be removed in 4.0, the Throwable will be a required constructor argument.',
            __METHOD__
        );

        $this->throwable = $exception;
    }

    /**
     * @return \Throwable
     *
     * @deprecated to be removed in 4.0, use `getThrowable()` instead
     */
    public function getException()
    {
        trigger_deprecation(
            'gos/web-socket-bundle',
            '3.3',
            '%s() is deprecated and will be removed in 4.0, use %s::getThrowable() instead.',
            __METHOD__,
            self::class
        );

        return $this->getThrowable();
    }

    public function getThrowable(): \Throwable
    {
        return $this->throwable;
    }
}

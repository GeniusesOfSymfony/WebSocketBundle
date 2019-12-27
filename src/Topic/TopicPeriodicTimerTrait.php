<?php declare(strict_types=1);

namespace Gos\Bundle\WebSocketBundle\Topic;

trait TopicPeriodicTimerTrait
{
    protected ?TopicPeriodicTimer $periodicTimer = null;

    public function setPeriodicTimer(TopicPeriodicTimer $periodicTimer): void
    {
        $this->periodicTimer = $periodicTimer;
    }
}

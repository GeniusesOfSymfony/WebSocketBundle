<?php

namespace Gos\Bundle\WebSocketBundle\Topic;

trait TopicPeriodicTimerTrait
{
    /**
     * @var TopicPeriodicTimer
     */
    protected $periodicTimer;

    public function setPeriodicTimer(TopicPeriodicTimer $periodicTimer): void
    {
        $this->periodicTimer = $periodicTimer;
    }
}

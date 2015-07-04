<?php

namespace Gos\Bundle\WebSocketBundle\Topic;

use Ratchet\Wamp\Topic;

interface TopicPeriodicTimerInterface
{
    /**
     * @param Topic $topic
     *
     * @return mixed
     */
    public function registerPeriodicTimer(Topic $topic);

    /**
     * @param TopicPeriodicTimer $periodicTimer
     */
    public function setPeriodicTimer(TopicPeriodicTimer $periodicTimer);
}

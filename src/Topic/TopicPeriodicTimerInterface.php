<?php declare(strict_types=1);

namespace Gos\Bundle\WebSocketBundle\Topic;

use Ratchet\Wamp\Topic;

interface TopicPeriodicTimerInterface
{
    public function registerPeriodicTimer(Topic $topic): void;

    public function setPeriodicTimer(TopicPeriodicTimer $periodicTimer): void;
}

<?php declare(strict_types=1);

namespace Gos\Bundle\WebSocketBundle\Topic;

use Gos\Bundle\WebSocketBundle\Router\WampRequest;
use Ratchet\Wamp\Topic;

interface TopicPeriodicTimerInterface
{
    public function registerPeriodicTimer(Topic $topic, WampRequest $request): void;

    public function setPeriodicTimer(TopicPeriodicTimer $periodicTimer): void;
}

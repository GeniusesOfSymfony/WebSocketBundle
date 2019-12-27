<?php declare(strict_types=1);

namespace Gos\Bundle\WebSocketBundle\Server\Exception;

use Gos\Bundle\WebSocketBundle\Topic\TopicInterface;

class PushUnsupportedException extends \RuntimeException
{
    private TopicInterface $topic;

    public function __construct(TopicInterface $topic)
    {
        parent::__construct(sprintf('The "%s" topic does not support push notifications', $topic->getName()));

        $this->topic = $topic;
    }

    public function getTopic(): TopicInterface
    {
        return $this->topic;
    }
}

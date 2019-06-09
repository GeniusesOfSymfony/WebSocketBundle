<?php

namespace Gos\Bundle\WebSocketBundle\Server\App\Registry;

use Gos\Bundle\WebSocketBundle\Topic\TopicInterface;

/**
 * @author Johann Saunier <johann_27@hotmail.fr>
 */
final class TopicRegistry
{
    /**
     * @var TopicInterface[]
     */
    private $topics = [];

    public function addTopic(TopicInterface $topic): void
    {
        $this->topics[$topic->getName()] = $topic;
    }

    /**
     * @throws \InvalidArgumentException
     */
    public function getTopic(string $topicName): TopicInterface
    {
        if (!$this->hasTopic($topicName)) {
            throw new \InvalidArgumentException(sprintf('A topic named "%s" has not been registered.', $topicName));
        }

        return $this->topics[$topicName];
    }

    public function hasTopic(string $topicName): bool
    {
        return isset($this->topics[$topicName]);
    }
}

<?php

namespace Gos\Bundle\WebSocketBundle\Server\App\Registry;

use Gos\Bundle\WebSocketBundle\Topic\TopicInterface;

/**
 * @author Johann Saunier <johann_27@hotmail.fr>
 */
class TopicRegistry
{
    /**
     * @var TopicInterface[]
     */
    protected $topics = [];

    /**
     * @param TopicInterface $topic
     */
    public function addTopic(TopicInterface $topic)
    {
        $this->topics[$topic->getName()] = $topic;
    }

    /**
     * @param string $topicName
     *
     * @return TopicInterface
     *
     * @throws \InvalidArgumentException
     */
    public function getTopic($topicName)
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

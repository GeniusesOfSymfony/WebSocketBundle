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
    protected $topics;

    public function __construct()
    {
        $this->topics = [];
    }

    /**
     * @param TopicInterface $topic
     *
     * @throws \Exception
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
     * @throws \Exception
     */
    public function getTopic($topicName)
    {
        if (!isset($this->topics[$topicName])) {
            throw new \Exception(sprintf(
                'Topic %s does\'nt exist in [ %s ]',
                $topicName,
                implode(', ', array_keys($this->topics))
            ));
        }

        return $this->topics[$topicName];
    }
}

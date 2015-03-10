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
        $this->topics = array();
    }

    /**
     * @param TopicInterface $topic
     *
     * @throws \Exception
     */
    public function addTopic(TopicInterface $topic)
    {
        $this->topics[$topic->getPrefix()] = $topic;
    }

    /**
     * @param $topicId
     *
     * @return TopicInterface
     *
     * @throws \Exception
     */
    public function getTopic($topicId)
    {
        $parts = explode('/', $topicId);

        if ($parts <= 0) {
            throw new \Exception('Incorrectly formatted Topic name');
        }

        $domain = $parts[0];

        if (isset($this->topics[$domain])) {
            return $this->topics[$domain];
        }

        throw new \Exception(sprintf('Domain %s not exists, only [ %s ] are available',
            $domain,
            implode(', ', array_keys($this->topics))
        ));
    }
}

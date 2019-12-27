<?php declare(strict_types=1);

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
    private array $topics = [];

    public function addTopic(TopicInterface $topic): void
    {
        $this->topics[$topic->getName()] = $topic;
    }

    /**
     * @throws \InvalidArgumentException if the requested topic was not registered
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

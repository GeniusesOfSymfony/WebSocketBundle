<?php declare(strict_types=1);

namespace Gos\Bundle\WebSocketBundle\Server\App\Dispatcher;

use Gos\Bundle\WebSocketBundle\Router\WampRequest;
use Gos\Bundle\WebSocketBundle\Server\App\Registry\TopicRegistry;
use Gos\Bundle\WebSocketBundle\Server\Exception\FirewallRejectionException;
use Gos\Bundle\WebSocketBundle\Topic\SecuredTopicInterface;
use Gos\Bundle\WebSocketBundle\Topic\TopicPeriodicTimer;
use Gos\Bundle\WebSocketBundle\Topic\TopicPeriodicTimerInterface;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Ratchet\ConnectionInterface;
use Ratchet\Wamp\Topic;
use Ratchet\Wamp\WampConnection;

/**
 * @author Johann Saunier <johann_27@hotmail.fr>
 */
final class TopicDispatcher implements TopicDispatcherInterface, LoggerAwareInterface
{
    use LoggerAwareTrait;

    private const SUBSCRIBE = 'subscribe';
    private const UNSUBSCRIBE = 'unsubscribe';
    private const PUBLISH = 'publish';

    public function __construct(
        private TopicRegistry $topicRegistry,
        private TopicPeriodicTimer $topicPeriodicTimer,
    ) {
    }

    public function onSubscribe(ConnectionInterface $conn, Topic $topic, WampRequest $request): void
    {
        $this->dispatch(self::SUBSCRIBE, $conn, $topic, $request);
    }

    public function onUnSubscribe(ConnectionInterface $conn, Topic $topic, WampRequest $request): void
    {
        $this->dispatch(self::UNSUBSCRIBE, $conn, $topic, $request);
    }

    public function onPublish(
        ConnectionInterface $conn,
        Topic $topic,
        WampRequest $request,
        string|array $event,
        array $exclude,
        array $eligible
    ): void {
        $this->dispatch(self::PUBLISH, $conn, $topic, $request, $event, $exclude, $eligible);
    }

    /**
     * @throws \InvalidArgumentException if an unsupported request type is given
     * @throws \RuntimeException         if the connection is missing for a method which requires it or if there is no payload for a push request
     */
    private function dispatch(
        string $calledMethod,
        ?ConnectionInterface $conn,
        Topic $topic,
        WampRequest $request,
        string|array|null $payload = null,
        ?array $exclude = null,
        ?array $eligible = null,
        ?string $provider = null
    ): bool {
        $callback = $request->getRoute()->getCallback();

        if (!\is_string($callback)) {
            throw new \InvalidArgumentException(sprintf('The callback for route "%s" must be a string, a callable was given.', $request->getRouteName()));
        }

        if (!$this->topicRegistry->hasTopic($callback)) {
            $this->logger?->error(sprintf('Could not find topic dispatcher in registry for callback "%s".', $callback));

            return false;
        }

        $appTopic = $this->topicRegistry->getTopic($callback);

        if ($appTopic instanceof SecuredTopicInterface) {
            try {
                $appTopic->secure($conn, $topic, $request, $payload, $exclude, $eligible, $provider);
            } catch (FirewallRejectionException $e) {
                $this->logger?->error(
                    sprintf('Topic "%s" rejected the connection: %s', $appTopic->getName(), $e->getMessage()),
                    ['exception' => $e]
                );

                if ($conn && $conn instanceof WampConnection) {
                    $conn->callError(
                        $topic->getId(),
                        $topic,
                        sprintf('You are not authorized to perform this action: %s', $e->getMessage()),
                        [
                            'code' => 401,
                            'topic' => $topic,
                            'request' => $request,
                            'event' => $calledMethod,
                        ]
                    );

                    $conn->close();
                }

                return false;
            } catch (\Throwable $e) {
                $this->logger?->error(
                    sprintf('An error occurred while attempting to secure topic "%s", the connection was rejected: %s', $appTopic->getName(), $e->getMessage()),
                    ['exception' => $e]
                );

                if ($conn && $conn instanceof WampConnection) {
                    $conn->callError(
                        $topic->getId(),
                        $topic,
                        sprintf('Could not secure topic, connection rejected: %s', $e->getMessage()),
                        [
                            'code' => 500,
                            'topic' => $topic,
                            'request' => $request,
                            'event' => $calledMethod,
                        ]
                    );

                    $conn->close();
                }

                return false;
            }
        }

        if ($appTopic instanceof TopicPeriodicTimerInterface) {
            $appTopic->setPeriodicTimer($this->topicPeriodicTimer);

            if (!$this->topicPeriodicTimer->isRegistered($appTopic) && 0 !== \count($topic)) {
                try {
                    $appTopic->registerPeriodicTimer($topic);
                } catch (\Throwable $e) {
                    $this->logger?->error(
                        sprintf(
                            'Error registering periodic timer for topic "%s"',
                            $appTopic->getName()
                        ),
                        ['exception' => $e]
                    );
                }
            }
        }

        try {
            switch ($calledMethod) {
                case self::PUBLISH:
                    if (null === $conn) {
                        throw new \RuntimeException(sprintf('No connection was provided, cannot handle "%s" for "%s".', $calledMethod, \get_class($appTopic)));
                    }

                    $appTopic->onPublish($conn, $topic, $request, $payload, $exclude, $eligible);

                    break;

                case self::SUBSCRIBE:
                    if (null === $conn) {
                        throw new \RuntimeException(sprintf('No connection was provided, cannot handle "%s" for "%s".', $calledMethod, \get_class($appTopic)));
                    }

                    $appTopic->onSubscribe($conn, $topic, $request);

                    break;

                case self::UNSUBSCRIBE:
                    if (null === $conn) {
                        throw new \RuntimeException(sprintf('No connection was provided, cannot handle "%s" for "%s".', $calledMethod, \get_class($appTopic)));
                    }

                    $appTopic->onUnSubscribe($conn, $topic, $request);

                    if (0 === \count($topic)) {
                        $this->topicPeriodicTimer->clearPeriodicTimer($appTopic);
                    }

                    break;

                default:
                    throw new \InvalidArgumentException('The "'.$calledMethod.'" method is not supported.');
            }

            return true;
        } catch (\Throwable $e) {
            $this->logger?->error(
                'Websocket error processing topic callback function.',
                [
                    'exception' => $e,
                    'topic' => $topic,
                ]
            );

            if ($conn && $conn instanceof WampConnection) {
                $conn->callError(
                    $topic->getId(),
                    $topic,
                    $e->getMessage(),
                    [
                        'code' => 500,
                        'topic' => $topic,
                        'request' => $request,
                        'event' => $calledMethod,
                    ]
                );
            }

            return false;
        }
    }
}

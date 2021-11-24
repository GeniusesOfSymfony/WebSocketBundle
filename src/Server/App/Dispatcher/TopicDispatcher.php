<?php declare(strict_types=1);

namespace Gos\Bundle\WebSocketBundle\Server\App\Dispatcher;

use Gos\Bundle\WebSocketBundle\Router\WampRequest;
use Gos\Bundle\WebSocketBundle\Router\WampRouter;
use Gos\Bundle\WebSocketBundle\Server\App\Registry\TopicRegistry;
use Gos\Bundle\WebSocketBundle\Server\Exception\FirewallRejectionException;
use Gos\Bundle\WebSocketBundle\Server\Exception\PushUnsupportedException;
use Gos\Bundle\WebSocketBundle\Topic\PushableTopicInterface;
use Gos\Bundle\WebSocketBundle\Topic\SecuredTopicInterface;
use Gos\Bundle\WebSocketBundle\Topic\TopicManager;
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

    public const SUBSCRIPTION = 'onSubscribe';
    public const UNSUBSCRIPTION = 'onUnSubscribe';
    public const PUBLISH = 'onPublish';
    public const PUSH = 'onPush';

    private TopicRegistry $topicRegistry;
    private TopicPeriodicTimer $topicPeriodicTimer;
    private TopicManager $topicManager;

    /**
     * @param WampRouter|TopicPeriodicTimer   $router
     * @param TopicPeriodicTimer|TopicManager $topicPeriodicTimer
     */
    public function __construct(
        TopicRegistry $topicRegistry,
        object $router,
        object $topicPeriodicTimer,
        ?TopicManager $topicManager = null
    ) {
        $this->topicRegistry = $topicRegistry;

        if ($router instanceof WampRouter) {
            trigger_deprecation('gos/web-socket-bundle', '3.13', 'Passing a "%s" instance as the second argument of the "%s" class constructor is deprecated and will not be supported in 4.0.', WampRouter::class, self::class);

            if (!$topicPeriodicTimer instanceof TopicPeriodicTimer) {
                throw new \InvalidArgumentException(sprintf('Argument 3 of the %s constructor must be an instance of %s, "%s" given.', self::class, TopicPeriodicTimer::class, get_debug_type($topicPeriodicTimer)));
            }

            $this->topicPeriodicTimer = $topicPeriodicTimer;

            if (!$topicManager instanceof TopicManager) {
                throw new \InvalidArgumentException(sprintf('Argument 4 of the %s constructor must be an instance of %s, "%s" given.', self::class, TopicManager::class, get_debug_type($topicManager)));
            }

            $this->topicManager = $topicManager;
        } elseif ($router instanceof TopicPeriodicTimer) {
            $this->topicPeriodicTimer = $router;

            if (!$topicPeriodicTimer instanceof TopicManager) {
                throw new \InvalidArgumentException(sprintf('Argument 3 of the %s constructor must be an instance of %s, "%s" given.', self::class, TopicManager::class, get_debug_type($topicPeriodicTimer)));
            }

            $this->topicManager = $topicPeriodicTimer;
        } else {
            throw new \InvalidArgumentException(sprintf('Argument 2 of the %s constructor must be an instance of %s or %s, "%s" given.', self::class, WampRouter::class, TopicPeriodicTimer::class, get_debug_type($router)));
        }
    }

    public function onSubscribe(ConnectionInterface $conn, Topic $topic, WampRequest $request): void
    {
        $this->dispatch(self::SUBSCRIPTION, $conn, $topic, $request);
    }

    /**
     * @param string|array $data
     *
     * @deprecated to be removed in 4.0, use the symfony/messenger component instead
     */
    public function onPush(WampRequest $request, $data, string $provider): void
    {
        trigger_deprecation('gos/web-socket-bundle', '3.7', '%s() is deprecated and will be removed in 4.0, use the symfony/messenger component instead.', __METHOD__);

        $topic = $this->topicManager->getTopic($request->getMatched());
        $this->dispatch(self::PUSH, null, $topic, $request, $data, null, null, $provider);
    }

    public function onUnSubscribe(ConnectionInterface $conn, Topic $topic, WampRequest $request): void
    {
        $this->dispatch(self::UNSUBSCRIPTION, $conn, $topic, $request);
    }

    /**
     * @param string|array $event
     */
    public function onPublish(
        ConnectionInterface $conn,
        Topic $topic,
        WampRequest $request,
        $event,
        array $exclude,
        array $eligible
    ): void {
        $this->dispatch(self::PUBLISH, $conn, $topic, $request, $event, $exclude, $eligible);
    }

    /**
     * @param string|array $payload
     *
     * @throws PushUnsupportedException  if the topic does not support push requests
     * @throws \InvalidArgumentException if an unsupported request type is given
     * @throws \RuntimeException         if the connection is missing for a method which requires it or if there is no payload for a push request
     */
    public function dispatch(
        string $calledMethod,
        ?ConnectionInterface $conn,
        Topic $topic,
        WampRequest $request,
        $payload = null,
        ?array $exclude = null,
        ?array $eligible = null,
        ?string $provider = null
    ): bool {
        $callback = $request->getRoute()->getCallback();

        if (!\is_string($callback)) {
            throw new \InvalidArgumentException(sprintf('The callback for route "%s" must be a string, a callable was given.', $request->getRouteName()));
        }

        if (!$this->topicRegistry->hasTopic($callback)) {
            if (null !== $this->logger) {
                $this->logger->error(sprintf('Could not find topic dispatcher in registry for callback "%s".', $callback));
            }

            return false;
        }

        $appTopic = $this->topicRegistry->getTopic($callback);

        if ($appTopic instanceof SecuredTopicInterface) {
            try {
                $appTopic->secure($conn, $topic, $request, $payload, $exclude, $eligible, $provider);
            } catch (FirewallRejectionException $e) {
                if (null !== $this->logger) {
                    $this->logger->error(
                        sprintf('Topic "%s" rejected the connection: %s', $appTopic->getName(), $e->getMessage()),
                        ['exception' => $e]
                    );
                }

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
                if (null !== $this->logger) {
                    $this->logger->error(
                        sprintf('An error occurred while attempting to secure topic "%s", the connection was rejected: %s', $appTopic->getName(), $e->getMessage()),
                        ['exception' => $e]
                    );
                }

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
                    if (null !== $this->logger) {
                        $this->logger->error(
                            sprintf(
                                'Error registering periodic timer for topic "%s"',
                                $appTopic->getName()
                            ),
                            ['exception' => $e]
                        );
                    }
                }
            }
        }

        try {
            switch ($calledMethod) {
                case self::PUSH:
                    if (!$appTopic instanceof PushableTopicInterface) {
                        throw new PushUnsupportedException($appTopic);
                    }

                    if (null === $payload) {
                        throw new \RuntimeException(sprintf('Missing payload data, cannot handle "%s" for "%s".', $calledMethod, \get_class($appTopic)));
                    }

                    $appTopic->onPush($topic, $request, $payload, $provider);

                    break;

                case self::PUBLISH:
                    if (null === $conn) {
                        throw new \RuntimeException(sprintf('No connection was provided, cannot handle "%s" for "%s".', $calledMethod, \get_class($appTopic)));
                    }

                    $appTopic->onPublish($conn, $topic, $request, $payload, $exclude, $eligible);

                    break;

                case self::SUBSCRIPTION:
                    if (null === $conn) {
                        throw new \RuntimeException(sprintf('No connection was provided, cannot handle "%s" for "%s".', $calledMethod, \get_class($appTopic)));
                    }

                    $appTopic->onSubscribe($conn, $topic, $request);

                    break;

                case self::UNSUBSCRIPTION:
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
        } catch (PushUnsupportedException $e) {
            if (null !== $this->logger) {
                $this->logger->error(
                    $e->getMessage(),
                    [
                        'exception' => $e,
                    ]
                );
            }

            throw $e;
        } catch (\Throwable $e) {
            if (null !== $this->logger) {
                $this->logger->error(
                    'Websocket error processing topic callback function.',
                    [
                        'exception' => $e,
                        'topic' => $topic,
                    ]
                );
            }

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

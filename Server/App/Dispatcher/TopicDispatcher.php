<?php

namespace Gos\Bundle\WebSocketBundle\Server\App\Dispatcher;

use Gos\Bundle\WebSocketBundle\Router\WampRequest;
use Gos\Bundle\WebSocketBundle\Router\WampRouter;
use Gos\Bundle\WebSocketBundle\Server\App\Registry\TopicRegistry;
use Gos\Bundle\WebSocketBundle\Server\Exception\FirewallRejectionException;
use Gos\Bundle\WebSocketBundle\Topic\SecuredTopicInterface;
use Gos\Bundle\WebSocketBundle\Topic\PushableTopicInterface;
use Gos\Bundle\WebSocketBundle\Topic\TopicManager;
use Gos\Bundle\WebSocketBundle\Topic\TopicPeriodicTimer;
use Gos\Bundle\WebSocketBundle\Topic\TopicPeriodicTimerInterface;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Ratchet\ConnectionInterface;
use Ratchet\Wamp\Topic;

/**
 * @author Johann Saunier <johann_27@hotmail.fr>
 */
class TopicDispatcher implements TopicDispatcherInterface, LoggerAwareInterface
{
    use LoggerAwareTrait;

    public const SUBSCRIPTION = 'onSubscribe';
    public const UNSUBSCRIPTION = 'onUnSubscribe';
    public const PUBLISH = 'onPublish';
    public const PUSH = 'onPush';

    /**
     * @var TopicRegistry
     */
    protected $topicRegistry;

    /**
     * @var WampRouter
     */
    protected $router;

    /**
     * @var TopicPeriodicTimer
     */
    protected $topicPeriodicTimer;

    /**
     * @var TopicManager
     */
    protected $topicManager;

    /**
     * @param TopicRegistry        $topicRegistry
     * @param WampRouter           $router
     * @param TopicPeriodicTimer   $topicPeriodicTimer
     * @param TopicManager         $topicManager
     */
    public function __construct(
        TopicRegistry $topicRegistry,
        WampRouter $router,
        TopicPeriodicTimer $topicPeriodicTimer,
        TopicManager $topicManager
    ) {
        $this->topicRegistry = $topicRegistry;
        $this->router = $router;
        $this->topicPeriodicTimer = $topicPeriodicTimer;
        $this->topicManager = $topicManager;
    }

    /**
     * @param ConnectionInterface $conn
     * @param Topic               $topic
     */
    public function onSubscribe(ConnectionInterface $conn, Topic $topic, WampRequest $request)
    {
        $this->dispatch(self::SUBSCRIPTION, $conn, $topic, $request);
    }

    /**
     * @param WampRequest  $request
     * @param array|string $data
     * @param string       $provider
     */
    public function onPush(WampRequest $request, $data, $provider)
    {
        $topic = $this->topicManager->getTopic($request->getMatched());
        $this->dispatch(self::PUSH, null, $topic, $request, $data, null, null, $provider);
    }

    /**
     * @param ConnectionInterface $conn
     * @param Topic               $topic
     */
    public function onUnSubscribe(ConnectionInterface $conn, Topic $topic, WampRequest $request)
    {
        $this->dispatch(self::UNSUBSCRIPTION, $conn, $topic, $request);
    }

    /**
     * @param ConnectionInterface $conn
     * @param Topic               $topic
     * @param string              $event
     * @param array               $exclude
     * @param array               $eligible
     */
    public function onPublish(ConnectionInterface $conn, Topic $topic, WampRequest $request, $event, array $exclude, array $eligible)
    {
        if (!$this->dispatch(self::PUBLISH, $conn, $topic, $request, $event, $exclude, $eligible)) {
            $topic->broadcast($event);
        }
    }

    /**
     * @param string                   $calledMethod
     * @param null|ConnectionInterface $conn
     * @param Topic                    $topic
     * @param WampRequest              $request
     * @param null                     $payload
     * @param null                     $exclude
     * @param null                     $eligible
     * @param null                     $provider
     *
     * @return bool
     * @throws \Exception
     */
    public function dispatch($calledMethod, ?ConnectionInterface $conn, Topic $topic, WampRequest $request, $payload = null, $exclude = null, $eligible = null, $provider = null)
    {
        $dispatched = false;

        if (!$topic) {
            return false;
        }

        foreach ((array) $request->getRoute()->getCallback() as $callback) {
            if (!$this->topicRegistry->hasTopic($callback)) {
                if ($this->logger) {
                    $this->logger->error(
                        sprintf('Could not find topic dispatcher in registry for callback "%s".', $callback)
                    );
                }

                continue;
            }

            $appTopic = $this->topicRegistry->getTopic($callback);

            if ($appTopic instanceof SecuredTopicInterface) {
                try {
                    $appTopic->secure($conn, $topic, $request, $payload, $exclude, $eligible, $provider);
                } catch (FirewallRejectionException $e) {
                    if ($this->logger) {
                        $this->logger->error($e->getMessage(), ['exception' => $e]);
                    }

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

                    return false;
                }
            }

            if ($appTopic instanceof TopicPeriodicTimerInterface) {
                $appTopic->setPeriodicTimer($this->topicPeriodicTimer);

                if (!$this->topicPeriodicTimer->isRegistered($appTopic) && count($topic) !== 0) {
                    $appTopic->registerPeriodicTimer($topic);
                }
            }

            if ($calledMethod === static::UNSUBSCRIPTION && 0 === count($topic)) {
                $this->topicPeriodicTimer->clearPeriodicTimer($appTopic);
            }

            try {
                switch ($calledMethod) {
                    case self::PUSH:
                        if (!$appTopic instanceof PushableTopicInterface) {
                            throw new \RuntimeException(sprintf('The "%s" topic does not support push notifications', $appTopic->getName()));
                        }

                        $appTopic->onPush($topic, $request, $payload, $provider);

                        break;

                    case self::PUBLISH:
                        $appTopic->onPublish($conn, $topic, $request, $payload, $exclude, $eligible);

                        break;

                    case self::SUBSCRIPTION:
                    case self::UNSUBSCRIPTION:
                        $appTopic->{$calledMethod}($conn, $topic, $request);

                        break;

                    default:
                        throw new \InvalidArgumentException('The "'.$calledMethod.'" method is not supported.');
                }

                $dispatched = true;
            } catch (\Exception $e) {
                if ($this->logger) {
                    $this->logger->error(
                        'Websocket error processing topic callback function.',
                        [
                            'exception' => $e,
                            'topic'     => $topic,
                        ]
                    );
                }

                $conn->callError(
                    $topic->getId(),
                    $topic,
                    $e->getMessage(),
                    [
                        'code'    => 500,
                        'topic'   => $topic,
                        'request' => $request,
                        'event'   => $calledMethod,
                    ]
                );

                $dispatched = false;
            }
        }

        return $dispatched;
    }
}

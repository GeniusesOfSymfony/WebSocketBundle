<?php

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use Gos\Bundle\WebSocketBundle\Client\Auth\WebsocketAuthenticationProvider;
use Gos\Bundle\WebSocketBundle\Client\Auth\WebsocketAuthenticationProviderInterface;
use Gos\Bundle\WebSocketBundle\Client\ClientManipulator;
use Gos\Bundle\WebSocketBundle\Client\ClientManipulatorInterface;
use Gos\Bundle\WebSocketBundle\Client\ClientStorage;
use Gos\Bundle\WebSocketBundle\Client\ClientStorageInterface;
use Gos\Bundle\WebSocketBundle\Client\Driver\InMemoryDriver;
use Gos\Bundle\WebSocketBundle\Client\Driver\SymfonyCacheDriverDecorator;
use Gos\Bundle\WebSocketBundle\Command\WebsocketServerCommand;
use Gos\Bundle\WebSocketBundle\EventListener\BindSignalsToWebsocketServerEventListener;
use Gos\Bundle\WebSocketBundle\EventListener\RegisterPeriodicMemoryTimerListener;
use Gos\Bundle\WebSocketBundle\EventListener\RegisterPeriodicTimersListener;
use Gos\Bundle\WebSocketBundle\EventListener\WebsocketClientEventSubscriber;
use Gos\Bundle\WebSocketBundle\Periodic\DoctrinePeriodicPing;
use Gos\Bundle\WebSocketBundle\Periodic\PdoPeriodicPing;
use Gos\Bundle\WebSocketBundle\Periodic\PeriodicMemoryUsage;
use Gos\Bundle\WebSocketBundle\Router\WampRouter;
use Gos\Bundle\WebSocketBundle\Server\App\Dispatcher\RpcDispatcher;
use Gos\Bundle\WebSocketBundle\Server\App\Dispatcher\RpcDispatcherInterface;
use Gos\Bundle\WebSocketBundle\Server\App\Dispatcher\TopicDispatcher;
use Gos\Bundle\WebSocketBundle\Server\App\Dispatcher\TopicDispatcherInterface;
use Gos\Bundle\WebSocketBundle\Server\App\Registry\OriginRegistry;
use Gos\Bundle\WebSocketBundle\Server\App\Registry\PeriodicRegistry;
use Gos\Bundle\WebSocketBundle\Server\App\Registry\RpcRegistry;
use Gos\Bundle\WebSocketBundle\Server\App\Registry\ServerRegistry;
use Gos\Bundle\WebSocketBundle\Server\App\Registry\TopicRegistry;
use Gos\Bundle\WebSocketBundle\Server\App\ServerBuilder;
use Gos\Bundle\WebSocketBundle\Server\App\ServerBuilderInterface;
use Gos\Bundle\WebSocketBundle\Server\App\WampApplication;
use Gos\Bundle\WebSocketBundle\Server\ServerLauncher;
use Gos\Bundle\WebSocketBundle\Server\ServerLauncherInterface;
use Gos\Bundle\WebSocketBundle\Server\Type\WebSocketServer;
use Gos\Bundle\WebSocketBundle\Topic\TopicManager;
use Gos\Bundle\WebSocketBundle\Topic\TopicPeriodicTimer;
use React\EventLoop\Factory;
use React\EventLoop\LoopInterface;

return static function (ContainerConfigurator $container): void {
    $container->services()
        ->set('gos_web_socket.client.authentication.websocket_provider', WebsocketAuthenticationProvider::class)
            ->args(
                [
                    service('gos_web_socket.client.storage'),
                    param('gos_web_socket.firewall'),
                ]
            )
            ->call(
                'setLogger',
                [
                    [service('logger')],
                ]
            )
            ->tag('monolog.logger', ['channel' => 'websocket'])
        ->alias(WebsocketAuthenticationProviderInterface::class, 'gos_web_socket.client.authentication.websocket_provider')

        ->set('gos_web_socket.client.driver.symfony_cache', SymfonyCacheDriverDecorator::class)
            ->args(
                [
                    abstract_arg('decorated client driver'),
                ]
            )

        ->set('gos_web_socket.client.driver.in_memory', InMemoryDriver::class)

        ->set('gos_web_socket.client.manipulator', ClientManipulator::class)
            ->public()
            ->args(
                [
                    service('gos_web_socket.client.storage'),
                    service('gos_web_socket.client.authentication.websocket_provider'),
                ]
            )
        ->alias(ClientManipulatorInterface::class, 'gos_web_socket.client.manipulator')

        ->set('gos_web_socket.client.storage', ClientStorage::class)
            ->args(
                [
                    abstract_arg('client driver'),
                    param('gos_web_socket.client.storage.ttl'),
                ]
            )
            ->call(
                'setLogger',
                [
                    [service('logger')],
                ]
            )
            ->tag('monolog.logger', ['channel' => 'websocket'])
        ->alias(ClientStorageInterface::class, 'gos_web_socket.client.storage')

        ->set('gos_web_socket.command.websocket_server', WebsocketServerCommand::class)
            ->args(
                [
                    service('gos_web_socket.server.server_launcher'),
                    param('gos_web_socket.server.host'),
                    param('gos_web_socket.server.port'),
                ]
            )

        ->set('gos_web_socket.dispatcher.rpc', RpcDispatcher::class)
            ->public()
            ->args(
                [
                    service('gos_web_socket.registry.rpc'),
                ]
            )
            ->call(
                'setLogger',
                [
                    [service('logger')],
                ]
            )
            ->tag('monolog.logger', ['channel' => 'websocket'])
        ->alias(RpcDispatcherInterface::class, 'gos_web_socket.dispatcher.rpc')

        ->set('gos_web_socket.dispatcher.topic', TopicDispatcher::class)
            ->public()
            ->args(
                [
                    service('gos_web_socket.registry.topic'),
                    service('gos_web_socket.router.wamp'),
                    service('gos_web_socket.topic.periodic_timer'),
                ]
            )
            ->call(
                'setLogger',
                [
                    [service('logger')],
                ]
            )
            ->tag('monolog.logger', ['channel' => 'websocket'])
        ->alias(TopicDispatcherInterface::class, 'gos_web_socket.dispatcher.topic')

        ->set('gos_web_socket.event_listener.bind_signals_to_websocket_server', BindSignalsToWebsocketServerEventListener::class)
            ->args(
                [
                    service('gos_web_socket.registry.periodic'),
                    service('gos_web_socket.client.storage'),
                ]
            )
            ->call(
                'setLogger',
                [
                    [service('logger')],
                ]
            )
            ->tag('kernel.event_listener')
            ->tag('monolog.logger', ['channel' => 'websocket'])

        ->set('gos_web_socket.event_listener.register_periodic_memory_timer', RegisterPeriodicMemoryTimerListener::class)
            ->args(
                [
                    service('gos_web_socket.registry.periodic'),
                ]
            )
            ->call(
                'setLogger',
                [
                    [service('logger')],
                ]
            )
            ->tag('kernel.event_listener', ['priority' => 255])
            ->tag('monolog.logger', ['channel' => 'websocket'])

        ->set('gos_web_socket.event_listener.register_periodic_memory_timer', RegisterPeriodicTimersListener::class)
            ->args(
                [
                    service('gos_web_socket.registry.periodic'),
                ]
            )
            ->call(
                'setLogger',
                [
                    [service('logger')],
                ]
            )
            ->tag('kernel.event_listener')
            ->tag('monolog.logger', ['channel' => 'websocket'])

        ->set('gos_web_socket.event_subscriber.client', WebsocketClientEventSubscriber::class)
            ->args(
                [
                    service('gos_web_socket.client.storage'),
                    service('gos_web_socket.authentication.websocket_provider'),
                ]
            )
            ->call(
                'setLogger',
                [
                    [service('logger')],
                ]
            )
            ->tag('kernel.event_subscriber')
            ->tag('monolog.logger', ['channel' => 'websocket'])

        ->set('gos_web_socket.periodic_ping.doctrine', DoctrinePeriodicPing::class)
            ->abstract()
            ->args(
                [
                    abstract_arg('Doctrine connection'),
                ]
            )
            ->call(
                'setLogger',
                [
                    [service('logger')],
                ]
            )
            ->tag('monolog.logger', ['channel' => 'websocket'])

        ->set('gos_web_socket.periodic_ping.pdo', PdoPeriodicPing::class)
            ->abstract()
            ->args(
                [
                    abstract_arg('PDO connection'),
                ]
            )
            ->call(
                'setLogger',
                [
                    [service('logger')],
                ]
            )
            ->tag('monolog.logger', ['channel' => 'websocket'])

        ->set('gos_web_socket.periodic_ping.memory_usage', PeriodicMemoryUsage::class)
            ->call(
                'setLogger',
                [
                    [service('logger')],
                ]
            )
            ->tag('monolog.logger', ['channel' => 'websocket'])

        ->set('gos_web_socket.registry.origins', OriginRegistry::class)
        ->alias(OriginRegistry::class, 'gos_web_socket.registry.origins')

        ->set('gos_web_socket.registry.periodic', PeriodicRegistry::class)
            ->args(
                [
                    tagged_iterator('gos_web_socket.periodic'),
                ]
            )
        ->alias(PeriodicRegistry::class, 'gos_web_socket.registry.periodic')

        ->set('gos_web_socket.registry.rpc', RpcRegistry::class)
            ->args(
                [
                    tagged_iterator('gos_web_socket.rpc'),
                ]
            )
        ->alias(RpcRegistry::class, 'gos_web_socket.registry.rpc')

        ->set('gos_web_socket.registry.server', ServerRegistry::class)
            ->args(
                [
                    tagged_iterator('gos_web_socket.server'),
                ]
            )
        ->alias(ServerRegistry::class, 'gos_web_socket.registry.server')

        ->set('gos_web_socket.registry.topic', TopicRegistry::class)
            ->args(
                [
                    tagged_iterator('gos_web_socket.topic'),
                ]
            )
        ->alias(TopicRegistry::class, 'gos_web_socket.registry.topic')

        ->set('gos_web_socket.router.wamp', WampRouter::class)
            ->args(
                [
                    service('gos_pubsub_router.router.websocket'),
                ]
            )
            ->call(
                'setLogger',
                [
                    [service('logger')],
                ]
            )
            ->tag('monolog.logger', ['channel' => 'websocket'])

        ->set('gos_web_socket.server.application.wamp', WampApplication::class)
            ->args(
                [
                    service('gos_web_socket.dispatcher.rpc'),
                    service('gos_web_socket.dispatcher.topic'),
                    service('event_dispatcher'),
                    service('gos_web_socket.client.storage'),
                    service('gos_web_socket.router.wamp'),
                ]
            )
            ->call(
                'setLogger',
                [
                    [service('logger')],
                ]
            )
            ->tag('monolog.logger', ['channel' => 'websocket'])

        ->set('gos_web_socket.server.builder', ServerBuilder::class)
            ->args(
                [
                    service('gos_web_socket.server.event_loop'),
                    service('gos_web_socket.wamp.topic_manager'),
                    service('gos_web_socket.registry.origins'),
                    service('event_dispatcher'),
                    param('gos_web_socket.server.origin_check'),
                    param('gos_web_socket.server.keepalive_ping'),
                    param('gos_web_socket.server.keepalive_interval'),
                ]
            )
        ->alias(ServerBuilderInterface::class, 'gos_web_socket.server.builder')

        ->set('gos_web_socket.server.server_launcher', ServerLauncher::class)
            ->public()
            ->args(
                [
                    service('gos_web_socket.registry.server'),
                ]
            )
        ->alias(ServerLauncherInterface::class, 'gos_web_socket.server.server_launcher')

        ->set('gos_web_socket.server.event_loop', LoopInterface::class)
            ->public()
            ->factory([Factory::class, 'create'])
        ->alias(LoopInterface::class, 'gos_web_socket.server.event_loop')

        ->set('gos_web_socket.server.websocket', WebSocketServer::class)
            ->args(
                [
                    service('gos_web_socket.server.builder'),
                    service('gos_web_socket.server.event_loop'),
                    service('event_dispatcher'),
                ]
            )
            ->call(
                'setLogger',
                [
                    [service('logger')],
                ]
            )
            ->tag('gos_web_socket.server')
            ->tag('monolog.logger', ['channel' => 'websocket'])

        ->set('gos_web_socket.topic.periodic_timer', TopicPeriodicTimer::class)
            ->public()
            ->args(
                [
                    service('gos_web_socket.server.event_loop'),
                ]
            )

        ->set('gos_web_socket.wamp.topic_manager', TopicManager::class)
            ->public()
            ->args(
                [
                    service('gos_web_socket.server.application.wamp'),
                ]
            )
        ->alias(TopicManager::class, 'gos_web_socket.wamp.topic_manager')
    ;
};

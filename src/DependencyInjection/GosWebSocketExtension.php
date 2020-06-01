<?php declare(strict_types=1);

namespace Gos\Bundle\WebSocketBundle\DependencyInjection;

use Gos\Bundle\WebSocketBundle\Client\Driver\DriverInterface;
use Gos\Bundle\WebSocketBundle\Periodic\PeriodicInterface;
use Gos\Bundle\WebSocketBundle\RPC\RpcInterface;
use Gos\Bundle\WebSocketBundle\Server\Type\ServerInterface;
use Gos\Bundle\WebSocketBundle\Topic\TopicInterface;
use Gos\Component\WebSocketClient\Wamp\Client;
use Gos\Component\WebSocketClient\Wamp\ClientFactory;
use Gos\Component\WebSocketClient\Wamp\ClientFactoryInterface;
use Gos\Component\WebSocketClient\Wamp\ClientInterface;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\Alias;
use Symfony\Component\DependencyInjection\ChildDefinition;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Exception\InvalidArgumentException;
use Symfony\Component\DependencyInjection\Exception\LogicException;
use Symfony\Component\DependencyInjection\Exception\RuntimeException;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Extension\PrependExtensionInterface;
use Symfony\Component\DependencyInjection\Loader;
use Symfony\Component\DependencyInjection\Reference;

/**
 * @author Johann Saunier <johann_27@hotmail.fr>
 */
final class GosWebSocketExtension extends Extension implements PrependExtensionInterface
{
    public function load(array $configs, ContainerBuilder $container): void
    {
        $loader = new Loader\YamlFileLoader($container, new FileLocator(__DIR__.'/../../config'));

        $loader->load('services.yaml');
        $loader->load('aliases.yaml');

        $configs = $this->processConfiguration($this->getConfiguration($configs, $container), $configs);

        $container->registerForAutoconfiguration(PeriodicInterface::class)->addTag('gos_web_socket.periodic');
        $container->registerForAutoconfiguration(RpcInterface::class)->addTag('gos_web_socket.rpc');
        $container->registerForAutoconfiguration(ServerInterface::class)->addTag('gos_web_socket.server');
        $container->registerForAutoconfiguration(TopicInterface::class)->addTag('gos_web_socket.topic');

        $container->setParameter('gos_web_socket.shared_config', $configs['shared_config']);

        $this->registerClientConfiguration($configs, $container);
        $this->registerServerConfiguration($configs, $container);
        $this->registerOriginsConfiguration($configs, $container);
        $this->registerPingConfiguration($configs, $container);
        $this->registerWebsocketClientConfiguration($configs, $container);
    }

    private function registerClientConfiguration(array $configs, ContainerBuilder $container): void
    {
        if (!isset($configs['client'])) {
            return;
        }

        $container->setParameter('gos_web_socket.client.storage.ttl', $configs['client']['storage']['ttl']);
        $container->setParameter('gos_web_socket.firewall', (array) $configs['client']['firewall']);

        if (isset($configs['client']['session_handler'])) {
            $sessionHandler = ltrim($configs['client']['session_handler'], '@');

            $container->getDefinition('gos_web_socket.server.builder')
                ->addMethodCall('setSessionHandler', [new Reference($sessionHandler)]);

            $container->setAlias('gos_web_socket.session_handler', $sessionHandler);
        }

        if (isset($configs['client']['storage']['driver'])) {
            $driverRef = ltrim($configs['client']['storage']['driver'], '@');
            $storageDriver = $driverRef;

            if (isset($configs['client']['storage']['decorator'])) {
                $decoratorRef = ltrim($configs['client']['storage']['decorator'], '@');
                $container->getDefinition($decoratorRef)
                    ->addArgument(new Reference($driverRef));

                $storageDriver = $decoratorRef;
            }

            // Alias the DriverInterface in use for autowiring
            $container->setAlias(DriverInterface::class, new Alias($storageDriver));

            $container->getDefinition('gos_web_socket.client.storage')
                ->replaceArgument(0, new Reference($storageDriver));
        }
    }

    private function registerServerConfiguration(array $configs, ContainerBuilder $container): void
    {
        if (!isset($configs['server'])) {
            return;
        }

        if (isset($configs['server']['port'])) {
            $container->setParameter('gos_web_socket.server.port', $configs['server']['port']);
        }

        if (isset($configs['server']['host'])) {
            $container->setParameter('gos_web_socket.server.host', $configs['server']['host']);
        }

        if (isset($configs['server']['origin_check'])) {
            $container->setParameter('gos_web_socket.server.origin_check', $configs['server']['origin_check']);
        }

        if (isset($configs['server']['keepalive_ping'])) {
            $container->setParameter('gos_web_socket.server.keepalive_ping', $configs['server']['keepalive_ping']);
        }

        if (isset($configs['server']['keepalive_interval'])) {
            $container->setParameter('gos_web_socket.server.keepalive_interval', $configs['server']['keepalive_interval']);
        }

        if (isset($configs['server']['router'])) {
            $routerConfig = [];

            // Adapt configuration based on the version of GosPubSubRouterBundle installed, if the XML loader is available the newer configuration structure is used
            if (isset($configs['server']['router']['resources'])) {
                foreach ($configs['server']['router']['resources'] as $resource) {
                    if (is_array($resource)) {
                        $routerConfig[] = $resource;
                    } else {
                        $routerConfig[] = [
                            'resource' => $resource,
                            'type' => null,
                        ];
                    }
                }
            }

            $container->setParameter('gos_web_socket.router_resources', $routerConfig);
        }
    }

    private function registerOriginsConfiguration(array $configs, ContainerBuilder $container): void
    {
        $originsRegistryDef = $container->getDefinition('gos_web_socket.registry.origins');

        foreach ($configs['origins'] as $origin) {
            $originsRegistryDef->addMethodCall('addOrigin', [$origin]);
        }
    }

    /**
     * @throws InvalidArgumentException if an unsupported ping service type is given
     */
    private function registerPingConfiguration(array $configs, ContainerBuilder $container): void
    {
        if (!isset($configs['ping'])) {
            return;
        }

        foreach ((array) $configs['ping']['services'] as $pingService) {
            switch ($pingService['type']) {
                case Configuration::PING_SERVICE_TYPE_DOCTRINE:
                    $serviceRef = ltrim($pingService['name'], '@');

                    $definition = new ChildDefinition('gos_web_socket.periodic_ping.doctrine');
                    $definition->addArgument(new Reference($serviceRef));
                    $definition->addTag('gos_web_socket.periodic');

                    $container->setDefinition('gos_web_socket.periodic_ping.doctrine.'.$serviceRef, $definition);

                    break;

                case Configuration::PING_SERVICE_TYPE_PDO:
                    $serviceRef = ltrim($pingService['name'], '@');

                    $definition = new ChildDefinition('gos_web_socket.periodic_ping.pdo');
                    $definition->addArgument(new Reference($serviceRef));
                    $definition->addTag('gos_web_socket.periodic');

                    $container->setDefinition('gos_web_socket.periodic_ping.pdo.'.$serviceRef, $definition);

                    break;

                default:
                    throw new InvalidArgumentException(sprintf('Unsupported ping service type "%s"', $pingService['type']));
            }
        }
    }

    private function registerWebsocketClientConfiguration(array $configs, ContainerBuilder $container): void
    {
        if (!isset($configs['websocket_client']) || !$configs['websocket_client']['enabled']) {
            return;
        }

        if (!class_exists(Client::class)) {
            throw new LogicException('The websocket client cannot be enabled because the required package is not installed, please run "composer require gos/websocket-client".');
        }

        // Pull the 'enabled' field out of the client's config
        $factoryConfig = $configs['websocket_client'];
        unset($factoryConfig['enabled']);

        $clientFactoryDef = new Definition(
            ClientFactory::class,
            [
                $factoryConfig,
            ]
        );
        $clientFactoryDef->setPrivate(true);
        $clientFactoryDef->addTag('monolog.logger', ['channel' => 'websocket']);
        $clientFactoryDef->addMethodCall('setLogger', [new Reference('logger', ContainerInterface::IGNORE_ON_INVALID_REFERENCE)]);

        $container->setDefinition('gos_web_socket.websocket_client_factory', $clientFactoryDef);
        $container->setAlias(ClientFactory::class, 'gos_web_socket.websocket_client_factory');
        $container->setAlias(ClientFactoryInterface::class, 'gos_web_socket.websocket_client_factory');

        $clientDef = new Definition(Client::class);
        $clientDef->setFactory([new Reference('gos_web_socket.websocket_client_factory'), 'createConnection']);

        $container->setDefinition('gos_web_socket.websocket_client', $clientDef);
        $container->setAlias(Client::class, 'gos_web_socket.websocket_client');
        $container->setAlias(ClientInterface::class, 'gos_web_socket.websocket_client');
    }

    /**
     * @throws RuntimeException if required dependencies are missing
     */
    public function prepend(ContainerBuilder $container): void
    {
        $bundles = $container->getParameter('kernel.bundles');

        if (!isset($bundles['GosPubSubRouterBundle'])) {
            throw new RuntimeException('The GosWebSocketBundle requires the GosPubSubRouterBundle.');
        }

        // Prepend the websocket router now so the pubsub bundle creates the router service, we will inject the resources into the service with a compiler pass
        $container->prependExtensionConfig(
            'gos_pubsub_router',
            [
                'routers' => [
                    'websocket' => [],
                ],
            ]
        );
    }
}

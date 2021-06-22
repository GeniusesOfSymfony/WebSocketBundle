<?php declare(strict_types=1);

namespace Gos\Bundle\WebSocketBundle\DependencyInjection;

use Gos\Bundle\WebSocketBundle\Client\Driver\DriverInterface;
use Gos\Bundle\WebSocketBundle\Periodic\PeriodicInterface;
use Gos\Bundle\WebSocketBundle\RPC\RpcInterface;
use Gos\Bundle\WebSocketBundle\Server\Type\ServerInterface;
use Gos\Bundle\WebSocketBundle\Topic\TopicInterface;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\Alias;
use Symfony\Component\DependencyInjection\ChildDefinition;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Exception\InvalidArgumentException;
use Symfony\Component\DependencyInjection\Exception\LogicException;
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
        $loader = new Loader\PhpFileLoader($container, new FileLocator(__DIR__.'/../../config'));

        $loader->load('services.php');

        $config = $this->processConfiguration($this->getConfiguration($configs, $container), $configs);

        $container->registerForAutoconfiguration(PeriodicInterface::class)->addTag('gos_web_socket.periodic');
        $container->registerForAutoconfiguration(RpcInterface::class)->addTag('gos_web_socket.rpc');
        $container->registerForAutoconfiguration(ServerInterface::class)->addTag('gos_web_socket.server');
        $container->registerForAutoconfiguration(TopicInterface::class)->addTag('gos_web_socket.topic');

        $container->setParameter('gos_web_socket.shared_config', $config['shared_config']);

        $this->registerClientConfiguration($config, $container);
        $this->registerServerConfiguration($config, $container);
        $this->registerOriginsConfiguration($config, $container);
        $this->registerBlockedIpAddressesConfiguration($config, $container);
        $this->registerPingConfiguration($config, $container);
    }

    private function registerClientConfiguration(array $config, ContainerBuilder $container): void
    {
        if (!isset($config['client'])) {
            return;
        }

        $container->setParameter('gos_web_socket.client.storage.ttl', $config['client']['storage']['ttl']);
        $container->setParameter('gos_web_socket.firewall', (array) $config['client']['firewall']);

        if (isset($config['client']['session_handler'])) {
            $sessionHandler = ltrim($config['client']['session_handler'], '@');

            $container->getDefinition('gos_web_socket.server.builder')
                ->addMethodCall('setSessionHandler', [new Reference($sessionHandler)]);

            $container->setAlias('gos_web_socket.session_handler', $sessionHandler);
        }

        if (isset($config['client']['storage']['driver'])) {
            $driverRef = ltrim($config['client']['storage']['driver'], '@');
            $storageDriver = $driverRef;

            if (isset($config['client']['storage']['decorator'])) {
                $decoratorRef = ltrim($config['client']['storage']['decorator'], '@');
                $container->getDefinition($decoratorRef)
                    ->setArgument(0, new Reference($driverRef));

                $storageDriver = $decoratorRef;
            }

            // Alias the DriverInterface in use for autowiring
            $container->setAlias(DriverInterface::class, new Alias($storageDriver));

            $container->getDefinition('gos_web_socket.client.storage')
                ->replaceArgument(0, new Reference($storageDriver));
        }
    }

    private function registerServerConfiguration(array $config, ContainerBuilder $container): void
    {
        if (!isset($config['server'])) {
            return;
        }

        if (isset($config['server']['port'])) {
            $container->setParameter('gos_web_socket.server.port', $config['server']['port']);
        }

        if (isset($config['server']['host'])) {
            $container->setParameter('gos_web_socket.server.host', $config['server']['host']);
        }

        if (isset($config['server']['origin_check'])) {
            $container->setParameter('gos_web_socket.server.origin_check', $config['server']['origin_check']);
        }

        if (isset($config['server']['ip_address_check'])) {
            $container->setParameter('gos_web_socket.server.ip_address_check', $config['server']['ip_address_check']);
        }

        if (isset($config['server']['keepalive_ping'])) {
            $container->setParameter('gos_web_socket.server.keepalive_ping', $config['server']['keepalive_ping']);
        }

        if (isset($config['server']['keepalive_interval'])) {
            $container->setParameter('gos_web_socket.server.keepalive_interval', $config['server']['keepalive_interval']);
        }

        if (isset($config['server']['router'])) {
            $routerConfig = [];

            // Adapt configuration based on the version of GosPubSubRouterBundle installed, if the XML loader is available the newer configuration structure is used
            if (isset($config['server']['router']['resources'])) {
                foreach ($config['server']['router']['resources'] as $resource) {
                    if (\is_array($resource)) {
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

    private function registerOriginsConfiguration(array $config, ContainerBuilder $container): void
    {
        $container->getDefinition('gos_web_socket.registry.origins')
            ->replaceArgument(0, $config['origins']);
    }

    private function registerBlockedIpAddressesConfiguration(array $config, ContainerBuilder $container): void
    {
        $container->setParameter('gos_web_socket.blocked_ip_addresses', $config['blocked_ip_addresses']);
    }

    /**
     * @throws InvalidArgumentException if an unsupported ping service type is given
     */
    private function registerPingConfiguration(array $config, ContainerBuilder $container): void
    {
        if (!isset($config['ping'])) {
            return;
        }

        foreach ((array) $config['ping']['services'] as $pingService) {
            switch ($pingService['type']) {
                case Configuration::PING_SERVICE_TYPE_DOCTRINE:
                    $serviceRef = ltrim($pingService['name'], '@');

                    $definition = new ChildDefinition('gos_web_socket.periodic_ping.doctrine');
                    $definition->replaceArgument(0, new Reference($serviceRef));
                    $definition->replaceArgument(1, $pingService['interval']);
                    $definition->addTag('gos_web_socket.periodic');

                    $container->setDefinition('gos_web_socket.periodic_ping.doctrine.'.$serviceRef, $definition);

                    break;

                case Configuration::PING_SERVICE_TYPE_PDO:
                    $serviceRef = ltrim($pingService['name'], '@');

                    $definition = new ChildDefinition('gos_web_socket.periodic_ping.pdo');
                    $definition->replaceArgument(0, new Reference($serviceRef));
                    $definition->replaceArgument(1, $pingService['interval']);
                    $definition->addTag('gos_web_socket.periodic');

                    $container->setDefinition('gos_web_socket.periodic_ping.pdo.'.$serviceRef, $definition);

                    break;

                default:
                    throw new InvalidArgumentException(sprintf('Unsupported ping service type "%s"', $pingService['type']));
            }
        }
    }

    /**
     * @throws LogicException if required dependencies are missing
     */
    public function prepend(ContainerBuilder $container): void
    {
        /** @var array<string, class-string> $bundles */
        $bundles = $container->getParameter('kernel.bundles');

        if (!isset($bundles['GosPubSubRouterBundle'])) {
            throw new LogicException('The GosWebSocketBundle requires the GosPubSubRouterBundle, please run "composer require gos/pubsub-router-bundle".');
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

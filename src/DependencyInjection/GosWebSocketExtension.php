<?php declare(strict_types=1);

namespace Gos\Bundle\WebSocketBundle\DependencyInjection;

use Gos\Bundle\WebSocketBundle\Authentication\Storage\Driver\StorageDriverInterface;
use Gos\Bundle\WebSocketBundle\DependencyInjection\Factory\Authentication\AuthenticationProviderFactoryInterface;
use Gos\Bundle\WebSocketBundle\Periodic\PeriodicInterface;
use Gos\Bundle\WebSocketBundle\RPC\RpcInterface;
use Gos\Bundle\WebSocketBundle\Server\Type\ServerInterface;
use Gos\Bundle\WebSocketBundle\Topic\TopicInterface;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\Argument\IteratorArgument;
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
    /**
     * @var AuthenticationProviderFactoryInterface[]
     */
    private array $authenticationProviderFactories = [];

    public function addAuthenticationProviderFactory(AuthenticationProviderFactoryInterface $factory): void
    {
        $this->authenticationProviderFactories[] = $factory;
    }

    public function getConfiguration(array $config, ContainerBuilder $container): Configuration
    {
        return new Configuration($this->authenticationProviderFactories);
    }

    public function load(array $configs, ContainerBuilder $container): void
    {
        $loader = new Loader\PhpFileLoader($container, new FileLocator(__DIR__.'/../../config'));

        $loader->load('services.php');

        $config = $this->processConfiguration($this->getConfiguration($configs, $container), $configs);

        $container->registerForAutoconfiguration(PeriodicInterface::class)->addTag('gos_web_socket.periodic');
        $container->registerForAutoconfiguration(RpcInterface::class)->addTag('gos_web_socket.rpc');
        $container->registerForAutoconfiguration(ServerInterface::class)->addTag('gos_web_socket.server');
        $container->registerForAutoconfiguration(TopicInterface::class)->addTag('gos_web_socket.topic');

        $this->registerAuthenticationConfiguration($config, $container);
        $this->registerServerConfiguration($config, $container);
        $this->registerOriginsConfiguration($config, $container);
        $this->registerBlockedIpAddressesConfiguration($config, $container);
        $this->registerPingConfiguration($config, $container);
    }

    private function registerAuthenticationConfiguration(array $config, ContainerBuilder $container): void
    {
        $authenticators = [];

        if (isset($config['authentication']['providers'])) {
            foreach ($this->authenticationProviderFactories as $factory) {
                $key = str_replace('-', '_', $factory->getKey());

                if (!isset($config['authentication']['providers'][$key])) {
                    continue;
                }

                $authenticators[] = new Reference($factory->createAuthenticationProvider($container, $config['authentication']['providers'][$key]));
            }
        }

        $container->getDefinition('gos_web_socket.authentication.authenticator')
            ->replaceArgument(0, new IteratorArgument($authenticators));

        $storageId = null;

        switch ($config['authentication']['storage']['type']) {
            case Configuration::AUTHENTICATION_STORAGE_TYPE_IN_MEMORY:
                $storageId = 'gos_web_socket.authentication.storage.driver.in_memory';

                break;

            case Configuration::AUTHENTICATION_STORAGE_TYPE_PSR_CACHE:
                $storageId = 'gos_web_socket.authentication.storage.driver.psr_cache';

                $container->getDefinition($storageId)
                    ->replaceArgument(0, new Reference($config['authentication']['storage']['pool']));

                break;

            case Configuration::AUTHENTICATION_STORAGE_TYPE_SERVICE:
                $storageId = $config['authentication']['storage']['id'];

                break;
        }

        $container->setAlias('gos_web_socket.authentication.storage.driver', $storageId);
        $container->setAlias(StorageDriverInterface::class, $storageId);
    }

    private function registerServerConfiguration(array $config, ContainerBuilder $container): void
    {
        $container->setParameter('gos_web_socket.server.port', $config['server']['port']);
        $container->setParameter('gos_web_socket.server.host', $config['server']['host']);
        $container->setParameter('gos_web_socket.server.tls.enabled', $config['server']['tls']['enabled']);
        $container->setParameter('gos_web_socket.server.tls.options', $config['server']['tls']['options']);
        $container->setParameter('gos_web_socket.server.origin_check', $config['server']['origin_check']);
        $container->setParameter('gos_web_socket.server.ip_address_check', $config['server']['ip_address_check']);
        $container->setParameter('gos_web_socket.server.keepalive_ping', $config['server']['keepalive_ping']);
        $container->setParameter('gos_web_socket.server.keepalive_interval', $config['server']['keepalive_interval']);

        $routerConfig = [];

        foreach (($config['server']['router']['resources'] ?? []) as $resource) {
            if (\is_array($resource)) {
                $routerConfig[] = $resource;
            } else {
                $routerConfig[] = [
                    'resource' => $resource,
                    'type' => null,
                ];
            }
        }

        $container->setParameter('gos_web_socket.router_resources', $routerConfig);
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
            $serviceId = $pingService['name'];

            switch ($pingService['type']) {
                case Configuration::PING_SERVICE_TYPE_DOCTRINE:
                    $definition = new ChildDefinition('gos_web_socket.periodic_ping.doctrine');
                    $definition->replaceArgument(0, new Reference($serviceId));
                    $definition->replaceArgument(1, $pingService['interval']);
                    $definition->addTag('gos_web_socket.periodic');

                    $container->setDefinition('gos_web_socket.periodic_ping.doctrine.'.$serviceId, $definition);

                    break;

                case Configuration::PING_SERVICE_TYPE_PDO:
                    $definition = new ChildDefinition('gos_web_socket.periodic_ping.pdo');
                    $definition->replaceArgument(0, new Reference($serviceId));
                    $definition->replaceArgument(1, $pingService['interval']);
                    $definition->addTag('gos_web_socket.periodic');

                    $container->setDefinition('gos_web_socket.periodic_ping.pdo.'.$serviceId, $definition);

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

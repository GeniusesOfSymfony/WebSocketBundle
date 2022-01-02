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
use Symfony\Component\DependencyInjection\Extension\PrependExtensionInterface;
use Symfony\Component\DependencyInjection\Loader;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\HttpKernel\DependencyInjection\ConfigurableExtension;

/**
 * @author Johann Saunier <johann_27@hotmail.fr>
 */
final class GosWebSocketExtension extends ConfigurableExtension implements PrependExtensionInterface
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

    protected function loadInternal(array $mergedConfig, ContainerBuilder $container): void
    {
        $loader = new Loader\PhpFileLoader($container, new FileLocator(__DIR__.'/../../config'));

        $loader->load('services.php');

        $container->registerForAutoconfiguration(PeriodicInterface::class)->addTag('gos_web_socket.periodic');
        $container->registerForAutoconfiguration(RpcInterface::class)->addTag('gos_web_socket.rpc');
        $container->registerForAutoconfiguration(ServerInterface::class)->addTag('gos_web_socket.server');
        $container->registerForAutoconfiguration(TopicInterface::class)->addTag('gos_web_socket.topic');

        $this->registerAuthenticationConfiguration($mergedConfig, $container);
        $this->registerServerConfiguration($mergedConfig, $container);
        $this->registerOriginsConfiguration($mergedConfig, $container);
        $this->registerBlockedIpAddressesConfiguration($mergedConfig, $container);
        $this->registerPingConfiguration($mergedConfig, $container);
    }

    private function registerAuthenticationConfiguration(array $mergedConfig, ContainerBuilder $container): void
    {
        $authenticators = [];

        if (isset($mergedConfig['authentication']['providers'])) {
            foreach ($this->authenticationProviderFactories as $factory) {
                $key = str_replace('-', '_', $factory->getKey());

                if (!isset($mergedConfig['authentication']['providers'][$key])) {
                    continue;
                }

                $authenticators[] = new Reference($factory->createAuthenticationProvider($container, $mergedConfig['authentication']['providers'][$key]));
            }
        }

        $container->getDefinition('gos_web_socket.authentication.authenticator')
            ->replaceArgument(0, new IteratorArgument($authenticators));

        $storageId = null;

        switch ($mergedConfig['authentication']['storage']['type']) {
            case Configuration::AUTHENTICATION_STORAGE_TYPE_IN_MEMORY:
                $storageId = 'gos_web_socket.authentication.storage.driver.in_memory';

                break;

            case Configuration::AUTHENTICATION_STORAGE_TYPE_PSR_CACHE:
                $storageId = 'gos_web_socket.authentication.storage.driver.psr_cache';

                $container->getDefinition($storageId)
                    ->replaceArgument(0, new Reference($mergedConfig['authentication']['storage']['pool']));

                break;

            case Configuration::AUTHENTICATION_STORAGE_TYPE_SERVICE:
                $storageId = $mergedConfig['authentication']['storage']['id'];

                break;
        }

        $container->setAlias('gos_web_socket.authentication.storage.driver', $storageId);
        $container->setAlias(StorageDriverInterface::class, $storageId);
    }

    private function registerServerConfiguration(array $mergedConfig, ContainerBuilder $container): void
    {
        $container->setParameter('gos_web_socket.server.port', $mergedConfig['server']['port']);
        $container->setParameter('gos_web_socket.server.host', $mergedConfig['server']['host']);
        $container->setParameter('gos_web_socket.server.tls.enabled', $mergedConfig['server']['tls']['enabled']);
        $container->setParameter('gos_web_socket.server.tls.options', $mergedConfig['server']['tls']['options']);
        $container->setParameter('gos_web_socket.server.origin_check', $mergedConfig['server']['origin_check']);
        $container->setParameter('gos_web_socket.server.ip_address_check', $mergedConfig['server']['ip_address_check']);
        $container->setParameter('gos_web_socket.server.keepalive_ping', $mergedConfig['server']['keepalive_ping']);
        $container->setParameter('gos_web_socket.server.keepalive_interval', $mergedConfig['server']['keepalive_interval']);

        $routerConfig = [];

        foreach (($mergedConfig['server']['router']['resources'] ?? []) as $resource) {
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

    private function registerOriginsConfiguration(array $mergedConfig, ContainerBuilder $container): void
    {
        $container->getDefinition('gos_web_socket.registry.origins')
            ->replaceArgument(0, $mergedConfig['origins']);
    }

    private function registerBlockedIpAddressesConfiguration(array $mergedConfig, ContainerBuilder $container): void
    {
        $container->setParameter('gos_web_socket.blocked_ip_addresses', $mergedConfig['blocked_ip_addresses']);
    }

    /**
     * @throws InvalidArgumentException if an unsupported ping service type is given
     */
    private function registerPingConfiguration(array $mergedConfig, ContainerBuilder $container): void
    {
        if (!isset($mergedConfig['ping'])) {
            return;
        }

        foreach ((array) $mergedConfig['ping']['services'] as $pingService) {
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

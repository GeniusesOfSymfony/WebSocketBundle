<?php

namespace Gos\Bundle\WebSocketBundle\Tests\Pusher;

use Gos\Bundle\WebSocketBundle\Pusher\PusherInterface;
use Gos\Bundle\WebSocketBundle\Pusher\PusherRegistry;
use PHPUnit\Framework\TestCase;

class PusherRegistryTest extends TestCase
{
    /**
     * @var PusherRegistry
     */
    private $registry;

    protected function setUp()
    {
        parent::setUp();

        $this->registry = new PusherRegistry();
    }

    public function testPushersAreAddedToTheRegistry()
    {
        $pusher = new class implements PusherInterface
        {
            public function push($data, $routeName, array $routeParameters = [], array $context = [])
            {
                // no-op
            }

            public function getConfig()
            {
                // no-op
            }

            public function setConfig($config)
            {
                // no-op
            }

            public function close()
            {
                // no-op
            }

            public function getName()
            {
                return 'test';
            }
        };

        $this->registry->addPusher($pusher);

        $this->assertSame($pusher, $this->registry->getPusher($pusher->getName()));
        $this->assertContains($pusher, $this->registry->getPushers());
        $this->assertTrue($this->registry->hasPusher($pusher->getName()));
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage A pusher named "main" has not been registered.
     */
    public function testRetrievingAHandlerFailsIfTheNamedHandlerDoesNotExist()
    {
        $pusher = new class implements PusherInterface
        {
            public function push($data, $routeName, array $routeParameters = [], array $context = [])
            {
                // no-op
            }

            public function getConfig()
            {
                // no-op
            }

            public function setConfig($config)
            {
                // no-op
            }

            public function close()
            {
                // no-op
            }

            public function getName()
            {
                return 'test';
            }
        };

        $this->registry->addPusher($pusher);

        $this->registry->getPusher('main');
    }
}

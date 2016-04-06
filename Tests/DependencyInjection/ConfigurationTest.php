<?php

namespace Gos\Bundle\WebSocketBundle\Tests;

use Gos\Bundle\WebSocketBundle\DependencyInjection\Configuration;
use Symfony\Component\Config\Definition\Processor;

/**
 * Class ConfigurationTest
 */
final class ConfigurationTest extends \PHPUnit_Framework_TestCase
{
    public function testContextConfigurationIsOptional()
    {
        /* Config:
         *
         * server:
         *   host: "127.0.0.1"
         *   port: "8080"
         */
        $configs = array(
            array(
                'server' => array(
                    'host' => "127.0.0.1",
                    'port' => "8080",
                ),
            ),
        );

        $config = $this->process($configs);

        $this->assertEquals('127.0.0.1', $config['server']['host']);
        $this->assertEquals('8080', $config['server']['port']);
    }

    /**
     * Processes an array of configurations and returns a compiled version.
     *
     * @param array $configs An array of raw configurations
     *
     * @return array A normalized array
     */
    protected function process($configs)
    {
        $processor = new Processor();

        return $processor->processConfiguration(new Configuration(), $configs);
    }

    public function testTokenSeparatorIsSet()
    {
        /*
         * Config:
         *
         * server:
         *   host: "127.0.0.1"
         *   port: "8080"
         *   router:
         *     context:
         *       tokenSeparator: "-"
         */
        $configs = array(
            array(
                'server' => array(
                    'host' => "127.0.0.1",
                    'port' => "8080",
                    'router' => array(
                        'context' => array(
                            "tokenSeparator" => "/",
                        ),
                    ),
                ),
            ),
        );

        $config = $this->process($configs);

        $this->assertEquals(
            '/',
            $config['server']['router']['context']['tokenSeparator']
        );
    }
}

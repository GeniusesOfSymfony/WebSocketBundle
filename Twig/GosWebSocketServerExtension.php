<?php

namespace Gos\Bundle\WebSocketBundle\Twig;

use Twig\Environment;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

trigger_deprecation('gos/web-socket-bundle', '1.9', 'The %s class is deprecated will be removed in 2.0.', GosWebSocketServerExtension::class);

/**
 * @author Johann Saunier <johann_27@hotmail.fr>
 *
 * @deprecated to be removed in 2.0
 */
class GosWebSocketServerExtension extends AbstractExtension
{
    /**
     * @return string
     */
    public function getName()
    {
        return 'gos_web_socket_server';
    }

    /**
     * @return array
     */
    public function getFunctions()
    {
        return [
            'ws_client' => new TwigFunction(
                'ws_client',
                [$this, 'clientOutput'],
                [
                    'is_safe' => ['html'],
                    'needs_environment' => true,
                    'deprecated' => true,
                ]
            ),
        ];
    }

    /**
     * @return string
     *
     * @throws \Exception
     * @throws \Twig_Error
     */
    public function clientOutput(Environment $twig)
    {
        return $twig->render('GosWebSocketBundle::client.html.twig');
    }
}

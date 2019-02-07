<?php

namespace Gos\Bundle\WebSocketBundle\Twig;

@trigger_error(sprintf('The %s class is deprecated will be removed in 2.0.', GosWebSocketServerExtension::class), E_USER_DEPRECATED);

/**
 * @author Johann Saunier <johann_27@hotmail.fr>
 *
 * @deprecated to be removed in 2.0
 */
class GosWebSocketServerExtension extends \Twig_Extension
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
            'ws_client' => new \Twig_SimpleFunction(
                'ws_client',
                array($this, 'clientOutput'),
                array(
                    'is_safe' => array('html'),
                    'needs_environment' => true,
                    'deprecated' => true,
                )
            ),
        ];
    }

    /**
     * @return string
     *
     * @throws \Exception
     * @throws \Twig_Error
     */
    public function clientOutput(\Twig_Environment $twig)
    {
        return $twig->render('GosWebSocketBundle::client.html.twig');
    }
}

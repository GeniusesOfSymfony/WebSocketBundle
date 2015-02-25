<?php

namespace Gos\Bundle\WebSocketBundle\Event;

/**
 * @author Johann Saunier <johann_27@hotmail.fr>
 */
final class Events
{
    const SERVER_LAUNCHED = 'gos_web_socket.server_launched';
    const CLIENT_CONNECTED = 'gos_web_socket.client_connected';
    const CLIENT_DISCONNECTED = 'gos_web_socket.client_disconnected';
    const CLIENT_ERROR = 'gos_web_socket.client_error';
    const CLIENT_REJECTED = 'gos_web_socket.client_rejected';
}

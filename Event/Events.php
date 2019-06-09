<?php

namespace Gos\Bundle\WebSocketBundle\Event;

/**
 * @author Johann Saunier <johann_27@hotmail.fr>
 */
final class Events
{
    public const SERVER_LAUNCHED = 'gos_web_socket.server_launched';
    public const CLIENT_CONNECTED = 'gos_web_socket.client_connected';
    public const CLIENT_DISCONNECTED = 'gos_web_socket.client_disconnected';
    public const CLIENT_ERROR = 'gos_web_socket.client_error';
    public const CLIENT_REJECTED = 'gos_web_socket.client_rejected';
    public const PUSHER_FAIL = 'gos_web_socket.push_fail';
    public const PUSHER_SUCCESS = 'gos_web_socket.push_success';
}

<?php

namespace Gos\Bundle\WebSocketBundle\Event;

/**
 * @author Johann Saunier <johann_27@hotmail.fr>
 */
final class Events
{
    /**
     * The SERVER_LAUNCHED event occurs when a websocket server is launched.
     *
     * This event allows you to add services to the event loop for the server.
     *
     * @Event("Gos\Bundle\WebSocketBundle\Event\ServerEvent")
     */
    public const SERVER_LAUNCHED = 'gos_web_socket.server_launched';

    /**
     * The CLIENT_CONNECTED event occurs when a client connects to a websocket server.
     *
     * @Event("Gos\Bundle\WebSocketBundle\Event\ClientEvent")
     */
    public const CLIENT_CONNECTED = 'gos_web_socket.client_connected';

    /**
     * The CLIENT_DISCONNECTED event occurs when a client disconnects from a websocket server.
     *
     * @Event("Gos\Bundle\WebSocketBundle\Event\ClientEvent")
     */
    public const CLIENT_DISCONNECTED = 'gos_web_socket.client_disconnected';

    /**
     * The CLIENT_ERROR event occurs when a client connection receives an error from a websocket server.
     *
     * This event allows you to add extra error handling within your application.
     *
     * @Event("Gos\Bundle\WebSocketBundle\Event\ClientErrorEvent")
     */
    public const CLIENT_ERROR = 'gos_web_socket.client_error';

    /**
     * The CLIENT_REJECTED event occurs when a client connection is rejected.
     *
     * @Event("Gos\Bundle\WebSocketBundle\Event\ClientRejectedEvent")
     */
    public const CLIENT_REJECTED = 'gos_web_socket.client_rejected';

    /**
     * The PUSHER_FAIL event occurs when a push handler has an error pushing a message to a server.
     *
     * @Event("Gos\Bundle\WebSocketBundle\Event\PushHandlerEvent")
     */
    public const PUSHER_FAIL = 'gos_web_socket.push_fail';

    /**
     * The PUSHER_SUCCESS event occurs when a push handler succeeds in pushing a message to a server.
     *
     * @Event("Gos\Bundle\WebSocketBundle\Event\PushHandlerEvent")
     */
    public const PUSHER_SUCCESS = 'gos_web_socket.push_success';
}

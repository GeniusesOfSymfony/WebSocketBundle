<?php declare(strict_types=1);

namespace Gos\Bundle\WebSocketBundle;

use Gos\Bundle\WebSocketBundle\Event\ClientConnectedEvent;
use Gos\Bundle\WebSocketBundle\Event\ClientDisconnectedEvent;
use Gos\Bundle\WebSocketBundle\Event\ClientErrorEvent;
use Gos\Bundle\WebSocketBundle\Event\ClientRejectedEvent;
use Gos\Bundle\WebSocketBundle\Event\ConnectionRejectedEvent;
use Gos\Bundle\WebSocketBundle\Event\PushHandlerFailEvent;
use Gos\Bundle\WebSocketBundle\Event\PushHandlerSuccessEvent;
use Gos\Bundle\WebSocketBundle\Event\ServerLaunchedEvent;

final class GosWebSocketEvents
{
    /**
     * The SERVER_LAUNCHED event occurs when a websocket server is launched.
     *
     * This event allows you to add services to the event loop for the server.
     *
     * @Event("Gos\Bundle\WebSocketBundle\Event\ServerLaunchedEvent")
     */
    public const SERVER_LAUNCHED = 'gos_web_socket.server_launched';

    /**
     * The CLIENT_CONNECTED event occurs when a client connects to a websocket server.
     *
     * @Event("Gos\Bundle\WebSocketBundle\Event\ClientConnectedEvent")
     */
    public const CLIENT_CONNECTED = 'gos_web_socket.client_connected';

    /**
     * The CLIENT_DISCONNECTED event occurs when a client disconnects from a websocket server.
     *
     * @Event("Gos\Bundle\WebSocketBundle\Event\ClientDisconnectedEvent")
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
     *
     * @deprecated to be removed in 4.0, subscribe to the "gos_web_socket.connection_rejected" event instead
     */
    public const CLIENT_REJECTED = 'gos_web_socket.client_rejected';

    /**
     * The CONNECTION_REJECTED event occurs when a connection is rejected.
     *
     * @Event("Gos\Bundle\WebSocketBundle\Event\ConnectionRejectedEvent")
     */
    public const CONNECTION_REJECTED = 'gos_web_socket.connection_rejected';

    /**
     * The PUSHER_FAIL event occurs when a push handler has an error pushing a message to a server.
     *
     * @Event("Gos\Bundle\WebSocketBundle\Event\PushHandlerFailEvent")
     *
     * @deprecated to be removed in 4.0
     */
    public const PUSHER_FAIL = 'gos_web_socket.push_fail';

    /**
     * The PUSHER_SUCCESS event occurs when a push handler succeeds in pushing a message to a server.
     *
     * @Event("Gos\Bundle\WebSocketBundle\Event\PushHandlerSuccessEvent")
     *
     * @deprecated to be removed in 4.0
     */
    public const PUSHER_SUCCESS = 'gos_web_socket.push_success';

    /**
     * Event aliases.
     *
     * These aliases are consumed by RegisterListenersPass.
     */
    public const ALIASES = [
        ServerLaunchedEvent::class => self::SERVER_LAUNCHED,
        ClientConnectedEvent::class => self::CLIENT_CONNECTED,
        ClientDisconnectedEvent::class => self::CLIENT_DISCONNECTED,
        ClientErrorEvent::class => self::CLIENT_ERROR,
        ClientRejectedEvent::class => self::CLIENT_REJECTED,
        ConnectionRejectedEvent::class => self::CONNECTION_REJECTED,
        PushHandlerFailEvent::class => self::PUSHER_FAIL,
        PushHandlerSuccessEvent::class => self::PUSHER_SUCCESS,
    ];
}

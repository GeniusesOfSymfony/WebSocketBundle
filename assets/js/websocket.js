/**
 * Prototype for the listener callbacks
 *
 * @callback GosSocketListener
 * @param {GosSocket} this The scope for "this" in the callback is the GosSocket instance
 * @param {Object} data The data provided for the event
 */

/**
 * Prototype for the subscribe callbacks
 *
 * @callback AutobahnSubscribeListener
 * @param {String} uri The URI that has been published to
 * @param {Object} payload The data payload for the publish event
 */

class GosSocket {
    /**
     * Create a new GosSocket instance
     *
     * @param {ab} autobahn AutobahnJS API object
     * @param {String} uri URI to open the connection to
     * @param {{retryDelay: Number, maxRetries: Number, skipSubprotocolCheck: Boolean, skipSubprotocolAnnounce: Boolean}} sessionConfig Configuration object to forward to the Autobahn connect method
     */
    constructor(autobahn, uri, sessionConfig) {
        /**
         * Reference to the AutobahnJS API
         *
         * @type {ab}
         * @private
         */
        this._autobahn = autobahn;

        /**
         * Collection of listeners to trigger as callbacks for events
         *
         * @type {Object.GosSocketListener[]}
         * @private
         */
        this._listeners = {};

        /**
         * Reference to the current AutobahnJS session
         *
         * @type {ab.Session|null}
         * @private
         */
        this._session = null;

        this._connect(uri, sessionConfig);
    }

    /**
     * Create a new connection
     *
     * @param {String} uri URI to open the connection to
     * @param {{retryDelay: Number, maxRetries: Number, skipSubprotocolCheck: Boolean, skipSubprotocolAnnounce: Boolean}} sessionConfig Configuration object to forward to the Autobahn connect method
     * @returns {GosSocket}
     * @throws {Error} If AutobahnJS is not loaded
     */
    static connect(uri, sessionConfig = null) {
        if (typeof global.ab === 'undefined') {
            throw new Error('GosSocket requires AutobahnJS to be loaded.');
        }

        return new GosSocket(global.ab, uri, sessionConfig);
    }

    /**
     * Retrieve the AutobahnJS API object
     *
     * @returns {ab}
     */
    get autobahn() {
        return this._autobahn;
    }

    /**
     * Retrieve the current AutobahnJS session object
     *
     * @returns {ab.Session|null}
     */
    get session() {
        return this._session;
    }

    /**
     * Check if currently connected to the websocket server
     *
     * @returns {Boolean}
     */
    isConnected() {
        return this._session !== null;
    }

    /**
     * Remove a listener for an event
     *
     * @param {String} event The name of the event unsubscribe the listener from
     * @param {GosSocketListener} listener The callback to be removed
     */
    off(event, listener) {
        if (!(this._listeners[event] instanceof Array)) {
            return;
        }

        let i,
            listeners = this._listeners[event],
            totalListeners = listeners.length;

        for (i = 0; i < totalListeners; i++) {
            if (listeners[i] === listener) {
                listeners.splice(i, 1);
                break;
            }
        }
    }

    /**
     * Add a listener for an event
     *
     * @param {String} event The name of the event to listen for
     * @param {GosSocketListener} listener The callback to be executed
     */
    on(event, listener) {
        if (typeof this._listeners[event] === 'undefined') {
            this._listeners[event] = [];
        }

        this._listeners[event].push(listener);
    }

    /**
     * Publishes a message to a websocket topic
     *
     * @param {String} uri The URI for the topic to publish to
     * @param {*} data The data to pass to the topic
     * @throws {Error} If not connected to the websocket server
     */
    publishToTopic(uri, data = {}) {
        if (!this.isConnected()) {
            throw new Error('Websocket session is not active, cannot publish to URI');
        }

        this._session.publish(uri, data);
    }

    /**
     * Calls a RPC handler
     *
     * @param {String} uri The URI for the RPC handler
     * @param {*} data The data to pass to the handler
     * @returns {Promise}
     * @throws {Error} If not connected to the websocket server
     */
    rpcCall(uri, data = {}) {
        if (!this.isConnected()) {
            throw new Error('Websocket session is not active, cannot perform RPC call');
        }

        return this._session.call(uri, data);
    }

    /**
     * Add a subscriber for events on a websocket channel
     *
     * @param {String} uri The URI for the websocket channel to subscribe to
     * @param {AutobahnSubscribeListener} callback The callback to be executed when events are published
     * @throws {Error} If not connected to the websocket server
     */
    subscribeToChannel(uri, callback) {
        if (!this.isConnected()) {
            throw new Error('Websocket session is not active, cannot subscribe to channel');
        }

        try {
            this._session.subscribe(uri, callback);
        } catch (ex) {
            // Absorb errors related to already being subscribed to a channel
            // 'callback ${callback} already subscribed for topic ${resolveduri}'
            if (ex.message.indexOf(' already subscribed for topic ') !== -1) {
                // no-op
            } else {
                throw ex;
            }
        }
    }

    /**
     * Remove a subscriber for events on a websocket channel
     *
     * @param {String} uri The URI for the websocket channel to unsubscribe from
     * @param {AutobahnSubscribeListener} callback The callback to be unsubscribed
     * @throws {Error} If not connected to the websocket server
     */
    unsubscribeFromChannel(uri, callback) {
        if (!this.isConnected()) {
            throw new Error('Websocket session is not active, cannot unsubscribe from channel');
        }

        try {
            this._session.unsubscribe(uri, callback);
        } catch (ex) {
            // Absorb errors related to not being subscribed to a channel or the callback not being subscribed on this channel
            // 'not subscribed to topic ${resolveduri}'
            // 'no callback ${callback} subscribed on topic ${resolveduri}'
            if (ex.message.indexOf('not subscribed to topic ') !== -1 || ex.message.indexOf(' subscribed on topic ') !== -1) {
                // no-op
            } else {
                throw ex;
            }
        }
    }

    /**
     * Create a new connection
     *
     * @param {String} uri URI to open the connection to
     * @param {{retryDelay: Number, maxRetries: Number, skipSubprotocolCheck: Boolean, skipSubprotocolAnnounce: Boolean}} sessionConfig Configuration object to forward to the Autobahn connect method
     * @private
     */
    _connect(uri, sessionConfig = null) {
        this._autobahn.connect(
            uri,
            (session) => {
                this._session = session;

                this._fire('socket/connect', session);
            },
            (code, reason, detail) => {
                this._session = null;

                this._fire('socket/disconnect', {code: code, reason: reason});
            },
            sessionConfig
        );
    }

    /**
     * Call all listeners for an event
     *
     * @param {String} event The name of the event to be called
     * @param {*} data The data to pass to the event
     * @private
     */
    _fire(event, data = null) {
        if (!(this._listeners[event] instanceof Array)) {
            return;
        }

        let i,
            listeners = this._listeners[event],
            totalListeners = listeners.length;

        for (i = 0; i < totalListeners; i++) {
            listeners[i].call(this, data);
        }
    }
}

/**
 * @deprecated
 */
class WS {
    /**
     * Create a new connection
     *
     * @param {String} uri URI to open the connection to
     * @param {{retryDelay: Number, maxRetries: Number, skipSubprotocolCheck: Boolean, skipSubprotocolAnnounce: Boolean}} sessionConfig Configuration object to forward to the Autobahn connect method
     * @returns {GosSocket}
     * @throws {Error} If AutobahnJS is not loaded
     * @deprecated Use `GosSocket.connect()` instead
     */
    connect(uri, sessionConfig = null) {
        return GosSocket.connect(uri, sessionConfig);
    }

    /**
     * Get the singleton instance of this object
     *
     * @returns {WS}
     * @deprecated
     */
    static get instance() {
        return Socket;
    }
}

/**
 * Singleton instance of the WS object
 *
 * @type {WS}
 * @deprecated
 */
const Socket = new WS();

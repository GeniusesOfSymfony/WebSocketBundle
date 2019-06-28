/**
 * Prototype for the listener callbacks
 *
 * @callback GosSocketListener
 * @param {GosSocket} this The scope for "this" in the callback is the GosSocket instance
 * @param {Object} data The data provided for the event
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
        this._session = null

        this._connect(uri, sessionConfig);
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

class WS {
    /**
     * Create a new connection
     *
     * @param {String} uri URI to open the connection to
     * @param {{retryDelay: Number, maxRetries: Number, skipSubprotocolCheck: Boolean, skipSubprotocolAnnounce: Boolean}} sessionConfig Configuration object to forward to the Autobahn connect method
     * @returns {GosSocket}
     * @throws {Error} If AutobahnJS is not loaded
     */
    connect(uri, sessionConfig = null) {
        if (typeof global.ab === 'undefined') {
            throw new Error('GosSocket requires AutobahnJS to be loaded.');
        }

        return new GosSocket(global.ab, uri, sessionConfig);
    }

    /**
     * Get the singleton instance of this object
     *
     * @returns {WS}
     */
    static get instance() {
        return Socket;
    }
}

/**
 * Singleton instance of the WS object
 *
 * @type {WS}
 */
const Socket = new WS();

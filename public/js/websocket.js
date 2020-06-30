(function (root, factory) {
  var Socket = factory();
  if (typeof define === 'function' && define.amd) {
    // AMD. Register as an anonymous module.
    define([], Socket);
  } else if (typeof module === 'object' && module.exports) {
    // Node. Does not work with strict CommonJS, but
    // only CommonJS-like environments that support module.exports,
    // like Node.
    module.exports = Socket;
  } else {
    // Browser globals (root is window)
    root.WS = Socket;
  }
}(this, function () {
  "use strict";

function _classCallCheck(instance, Constructor) { if (!(instance instanceof Constructor)) { throw new TypeError("Cannot call a class as a function"); } }

function _defineProperties(target, props) { for (var i = 0; i < props.length; i++) { var descriptor = props[i]; descriptor.enumerable = descriptor.enumerable || false; descriptor.configurable = true; if ("value" in descriptor) descriptor.writable = true; Object.defineProperty(target, descriptor.key, descriptor); } }

function _createClass(Constructor, protoProps, staticProps) { if (protoProps) _defineProperties(Constructor.prototype, protoProps); if (staticProps) _defineProperties(Constructor, staticProps); return Constructor; }

function _classPrivateFieldGet(receiver, privateMap) { var descriptor = privateMap.get(receiver); if (!descriptor) { throw new TypeError("attempted to get private field on non-instance"); } if (descriptor.get) { return descriptor.get.call(receiver); } return descriptor.value; }

function _classPrivateMethodGet(receiver, privateSet, fn) { if (!privateSet.has(receiver)) { throw new TypeError("attempted to get private field on non-instance"); } return fn; }

function _classPrivateFieldSet(receiver, privateMap, value) { var descriptor = privateMap.get(receiver); if (!descriptor) { throw new TypeError("attempted to set private field on non-instance"); } if (descriptor.set) { descriptor.set.call(receiver, value); } else { if (!descriptor.writable) { throw new TypeError("attempted to set read only private field"); } descriptor.value = value; } return value; }

var _autobahn = new WeakMap();

var _listeners = new WeakMap();

var _session = new WeakMap();

var _connect = new WeakSet();

var _fire = new WeakSet();

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
var GosSocket = /*#__PURE__*/function () {
  /**
   * Reference to the AutobahnJS API
   *
   * @type {ab}
   */

  /**
   * Collection of listeners to trigger as callbacks for events
   *
   * @type {Object.GosSocketListener[]}
   */

  /**
   * Reference to the current AutobahnJS session
   *
   * @type {ab.Session|null}
   */

  /**
   * Create a new GosSocket instance
   *
   * @param {ab} autobahn AutobahnJS API object
   * @param {String} uri URI to open the connection to
   * @param {{retryDelay: Number, maxRetries: Number, skipSubprotocolCheck: Boolean, skipSubprotocolAnnounce: Boolean}} sessionConfig Configuration object to forward to the Autobahn connect method
   */
  function GosSocket(autobahn, _uri, _sessionConfig) {
    _classCallCheck(this, GosSocket);

    _fire.add(this);

    _connect.add(this);

    _autobahn.set(this, {
      writable: true,
      value: void 0
    });

    _listeners.set(this, {
      writable: true,
      value: {}
    });

    _session.set(this, {
      writable: true,
      value: null
    });

    _classPrivateFieldSet(this, _autobahn, autobahn);

    _classPrivateMethodGet(this, _connect, _connect2).call(this, _uri, _sessionConfig);
  }
  /**
   * Retrieve the AutobahnJS API object
   *
   * @returns {ab}
   */


  _createClass(GosSocket, [{
    key: "isConnected",

    /**
     * Check if currently connected to the websocket server
     *
     * @returns {Boolean}
     */
    value: function isConnected() {
      return _classPrivateFieldGet(this, _session) !== null;
    }
    /**
     * Remove a listener for an event
     *
     * @param {String} event The name of the event unsubscribe the listener from
     * @param {GosSocketListener} listener The callback to be removed
     */

  }, {
    key: "off",
    value: function off(event, listener) {
      if (!(_classPrivateFieldGet(this, _listeners)[event] instanceof Array)) {
        return;
      }

      var i,
          listeners = _classPrivateFieldGet(this, _listeners)[event],
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

  }, {
    key: "on",
    value: function on(event, listener) {
      if (typeof _classPrivateFieldGet(this, _listeners)[event] === 'undefined') {
        _classPrivateFieldGet(this, _listeners)[event] = [];
      }

      _classPrivateFieldGet(this, _listeners)[event].push(listener);
    }
    /**
     * Publishes a message to a websocket topic
     *
     * @param {String} uri The URI for the topic to publish to
     * @param {*} data The data to pass to the topic
     * @throws {Error} If not connected to the websocket server
     */

  }, {
    key: "publishToTopic",
    value: function publishToTopic(uri) {
      var data = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : {};

      if (!this.isConnected()) {
        throw new Error('Websocket session is not active, cannot publish to URI');
      }

      _classPrivateFieldGet(this, _session).publish(uri, data);
    }
    /**
     * Calls a RPC handler
     *
     * @param {String} uri The URI for the RPC handler
     * @param {*} data The data to pass to the handler
     * @returns {Promise}
     * @throws {Error} If not connected to the websocket server
     */

  }, {
    key: "rpcCall",
    value: function rpcCall(uri) {
      var data = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : {};

      if (!this.isConnected()) {
        throw new Error('Websocket session is not active, cannot perform RPC call');
      }

      return _classPrivateFieldGet(this, _session).call(uri, data);
    }
    /**
     * Add a subscriber for events on a websocket channel
     *
     * @param {String} uri The URI for the websocket channel to subscribe to
     * @param {AutobahnSubscribeListener} callback The callback to be executed when events are published
     * @throws {Error} If not connected to the websocket server
     */

  }, {
    key: "subscribeToChannel",
    value: function subscribeToChannel(uri, callback) {
      if (!this.isConnected()) {
        throw new Error('Websocket session is not active, cannot subscribe to channel');
      }

      try {
        _classPrivateFieldGet(this, _session).subscribe(uri, callback);
      } catch (ex) {
        // Absorb errors related to already being subscribed to a channel
        // 'callback ${callback} already subscribed for topic ${resolveduri}'
        if (ex.message.indexOf(' already subscribed for topic ') !== -1) {// no-op
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

  }, {
    key: "unsubscribeFromChannel",
    value: function unsubscribeFromChannel(uri, callback) {
      if (!this.isConnected()) {
        throw new Error('Websocket session is not active, cannot unsubscribe from channel');
      }

      try {
        _classPrivateFieldGet(this, _session).unsubscribe(uri, callback);
      } catch (ex) {
        // Absorb errors related to not being subscribed to a channel or the callback not being subscribed on this channel
        // 'not subscribed to topic ${resolveduri}'
        // 'no callback ${callback} subscribed on topic ${resolveduri}'
        if (ex.message.indexOf('not subscribed to topic ') !== -1 || ex.message.indexOf(' subscribed on topic ') !== -1) {// no-op
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
     */

  }, {
    key: "autobahn",
    get: function get() {
      return _classPrivateFieldGet(this, _autobahn);
    }
    /**
     * Retrieve the current AutobahnJS session object
     *
     * @returns {ab.Session|null}
     */

  }, {
    key: "session",
    get: function get() {
      return _classPrivateFieldGet(this, _session);
    }
  }]);

  return GosSocket;
}();

var _connect2 = function _connect2(uri) {
  var _this = this;

  var sessionConfig = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : null;

  _classPrivateFieldGet(this, _autobahn).connect(uri, function (session) {
    _classPrivateFieldSet(_this, _session, session);

    _classPrivateMethodGet(_this, _fire, _fire2).call(_this, 'socket/connect', session);
  }, function (code, reason, detail) {
    _classPrivateFieldSet(_this, _session, null);

    _classPrivateMethodGet(_this, _fire, _fire2).call(_this, 'socket/disconnect', {
      code: code,
      reason: reason
    });
  }, sessionConfig);
};

var _fire2 = function _fire2(event) {
  var data = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : null;

  if (!(_classPrivateFieldGet(this, _listeners)[event] instanceof Array)) {
    return;
  }

  var i,
      listeners = _classPrivateFieldGet(this, _listeners)[event],
      totalListeners = listeners.length;

  for (i = 0; i < totalListeners; i++) {
    listeners[i].call(this, data);
  }
};

var WS = /*#__PURE__*/function () {
  function WS() {
    _classCallCheck(this, WS);
  }

  _createClass(WS, [{
    key: "connect",

    /**
     * Create a new connection
     *
     * @param {String} uri URI to open the connection to
     * @param {{retryDelay: Number, maxRetries: Number, skipSubprotocolCheck: Boolean, skipSubprotocolAnnounce: Boolean}} sessionConfig Configuration object to forward to the Autobahn connect method
     * @returns {GosSocket}
     * @throws {Error} If AutobahnJS is not loaded
     */
    value: function connect(uri) {
      var sessionConfig = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : null;

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

  }], [{
    key: "instance",
    get: function get() {
      return Socket;
    }
  }]);

  return WS;
}();
/**
 * Singleton instance of the WS object
 *
 * @type {WS}
 */


var Socket = new WS();

  return Socket;
}));
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

/**
 * Prototype for the listener callbacks
 *
 * @callback GosSocketListener
 * @param {GosSocket} this The scope for "this" in the callback is the GosSocket instance
 * @param {Object} data The data provided for the event
 */
var GosSocket =
/*#__PURE__*/
function () {
  /**
   * Create a new GosSocket instance
   *
   * @param {ab} autobahn AutobahnJS API object
   * @param {String} uri URI to open the connection to
   * @param {{retryDelay: Number, maxRetries: Number, skipSubprotocolCheck: Boolean, skipSubprotocolAnnounce: Boolean}} sessionConfig Configuration object to forward to the Autobahn connect method
   */
  function GosSocket(autobahn, uri, sessionConfig) {
    _classCallCheck(this, GosSocket);

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
   * Remove a listener for an event
   *
   * @param {String} event The name of the event unsubscribe the listener from
   * @param {GosSocketListener} listener The callback to be removed
   */


  _createClass(GosSocket, [{
    key: "off",
    value: function off(event, listener) {
      if (!(this._listeners[event] instanceof Array)) {
        return;
      }

      var i,
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

  }, {
    key: "on",
    value: function on(event, listener) {
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

  }, {
    key: "_connect",
    value: function _connect(uri) {
      var _this = this;

      var sessionConfig = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : null;

      this._autobahn.connect(uri, function (session) {
        _this._session = session;

        _this._fire('socket/connect', session);
      }, function (code, reason, detail) {
        _this._session = null;

        _this._fire('socket/disconnect', {
          code: code,
          reason: reason
        });
      }, sessionConfig);
    }
    /**
     * Call all listeners for an event
     *
     * @param {String} event The name of the event to be called
     * @param {*} data The data to pass to the event
     * @private
     */

  }, {
    key: "_fire",
    value: function _fire(event) {
      var data = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : null;

      if (!(this._listeners[event] instanceof Array)) {
        return;
      }

      var i,
          listeners = this._listeners[event],
          totalListeners = listeners.length;

      for (i = 0; i < totalListeners; i++) {
        listeners[i].call(this, data);
      }
    }
  }]);

  return GosSocket;
}();

var WS =
/*#__PURE__*/
function () {
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
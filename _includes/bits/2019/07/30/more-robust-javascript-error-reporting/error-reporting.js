// JavaScript error reporting functions, including automatic window.onerror
// and unhandledrejection reporting.
//
// Based on:
// https://kevinlocke.name/bits/2019/07/30/more-robust-javascript-error-reporting/
//
// API:
// reportError(message, error):
//   Report an exception with optional message and exception value.
// reportRejection(message, cause):
//   Report a rejection with optional message and cause.
// setReportUrl(newReportUrl):
//   Set URL to which reports are POSTed as application/x-www-form-urlencoded
//   Must be called before reporting any errors unless a default reportUrl is
//   defined below.
//
// Note: unhandledrejection is only raised by Chrome 49+, Edge, Firefox 69+,
// and Bluebird.  Others must use .catch(errorReporting.reportRejection).
// when.js and yaku users could call reportRejection on unhandledRejection.
//
// Note: This script is intended to work with IE 6+ so that errors are reported
// for incorrect Compatibility View, EMIE, and/or X-UA-Compatible settings.
//
// To the extent possible under law, Kevin Locke <kevin@kevinlocke.name> has
// waived all copyright and related or neighboring rights to this work.
// See https://creativecommons.org/publicdomain/zero/1.0/

// Universal Module Definition (UMD) for Node, AMD, and browser globals
// https://github.com/umdjs/umd/blob/master/templates/returnExports.js
(function(root, factory) {
  if (typeof define === 'function' && define.amd) {
    // AMD. Register as an anonymous module.
    define([], factory);
  } else if (typeof module === 'object' && module.exports) {
    // Node. Does not work with strict CommonJS, but
    // only CommonJS-like environments that support module.exports,
    // like Node.
    module.exports = factory();
  } else {
    // Browser globals (root is window)
    root.errorReporting = factory();
  }
}(typeof self !== 'undefined' ? self : this, function() {
  'use strict';

  var reportUrl;

  // hasOwnProperty is not available from global scope in IE
  // eslint-disable-next-line no-shadow
  var hasOwnProperty = Object.prototype.hasOwnProperty;

  function logError(/* args */) {
    try {
      // eslint-disable-next-line no-console
      console.error.apply(console, arguments);
    } catch (err) {
      // Ignore failures (e.g. console not available).  Can't log.
    }
  }

  /** Gets the URL-encoding of a given string.
   * https://github.com/jerrybendy/url-search-params-polyfill/blob/v7.0.0/index.js#L117
   * @private
   */
  function urlEncode(str) {
    var replace = {
      '!': '%21',
      "'": '%27',
      '(': '%28',
      ')': '%29',
      '~': '%7E',
      '%20': '+',
      '%00': '\x00'
    };
    return encodeURIComponent(str)
      .replace(/[!'()~]|%20|%00/g, function(match) {
        return replace[match];
      });
  }
  // eslint-disable-next-line no-shadow
  var URLSearchParams = window.URLSearchParams
    || function URLSearchParamsPolyfill() {
      var params = {};
      this.set = function URLSearchParamsPolyfill$set(param, value) {
        params[param] = value == null ? '' : String(value);
      };
      this.toString = function URLSearchParamsPolyfill$toString() {
        var query = [];
        for (var param in params) {
          if (hasOwnProperty.call(params, param)) {
            query.push(param + '=' + urlEncode(params[param]));
          }
        }
        return query.join('&');
      };
    };

  function sendReport(url, report) {
    // sendBeacon support for CORS is in flux.  Now spec'd as no-cors mode.
    // https://bugzilla.mozilla.org/1280692
    // https://bugzilla.mozilla.org/1289387
    // Chrome rejects non-CORS Blob types:  https://crbug.com/490015
    // ScriptService doesn't support multipart/form-data, so use urlencoded
    var reportParams = new URLSearchParams();
    for (var reportProp in report) {
      if (hasOwnProperty.call(report, reportProp)) {
        var reportVal = report[reportProp];
        // Omit null and undefined values since urlencoded values are strings
        // and URLSearchParams encodes null as 'null', undefined as 'undefined'.
        if (reportVal != null) {
          reportParams.set(reportProp, reportVal);
        }
      }
    }
    var reportParamsStr = reportParams.toString();

    // Use sendBeacon, when available, to ensure error report is delivered,
    // even when the page is unloading, without impacting UX.
    // https://groups.google.com/a/chromium.org/d/msg/blink-dev/LnqwTCiT9Gs/tO0IBO4PAwAJ
    // https://bugzilla.mozilla.org/980902
    // https://bugzilla.mozilla.org/1542967
    //
    // Note: Could use fetch with keepalive:true, where supported
    // (e.g. by checking whether new Request().keepalive is undefined)
    // Better CORS support, but less widely implemented.
    // https://bugzilla.mozilla.org/1342484
    if (typeof navigator.sendBeacon === 'function') {
      // Chrome sends URLSearchParams as text/plain - https://crbug.com/747787
      // convert to Blob to avoid the issue
      var reportBlob = new Blob(
        [reportParamsStr],
        {type: 'application/x-www-form-urlencoded'}
      );

      // Note: Chrome throws on CORS type error.  Protect with try-catch.
      try {
        if (navigator.sendBeacon(url, reportBlob)) {
          return true;
        }
      } catch (errSendBeacon) {
        logError('Error calling sendBeacon', errSendBeacon);
      }
    }

    try {
      // Note: IE < 7 didn't provide XMLHttpRequest, but it is available in
      // IE 5 compatibility mode on IE 11.  Don't bother providing a fallback.
      var req = new XMLHttpRequest();
      // If an error occurs during unload or beforeunload, need to send
      // synchronously to ensure the request is sent before the page unloads.
      // FIXME: Can window.onerror be triggered from window.beforeunload?
      //        If so, this check likely won't detect that case.
      var isAsync = !window.event
        || (window.event.type !== 'unload'
          && window.event.type !== 'beforeunload');
      req.open('POST', url, isAsync);
      req.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
      req.send(reportParamsStr);
      return true;
    } catch (errXHR) {
      logError('Error sending XMLHttpRequest', errXHR);
      return false;
    }
  }

  function errorEventToReport(errorEvent) {
    // IE 9 uses non-standard ErrorEvent property names which are available on
    // window.event but not on the event argument.
    var windowEvent = window.event;
    if (!errorEvent.error
        && !errorEvent.message
        && windowEvent
        && windowEvent.errorMessage) {
      errorEvent = {
        type: errorEvent.type,
        message: windowEvent.errorMessage,
        filename: windowEvent.errorUrl,
        lineno: windowEvent.errorLine,
        colno: windowEvent.errorCharacter
      };
    }

    // Note: error may not be instanceof Error.  Handle carefully.
    var error = errorEvent.error || errorEvent.cause;
    var errorString = error == null ? null : String(error);
    // Note: Error.prototype.toString isn't useful on IE < 8.  Detect and fix.
    if (error
        && typeof error.message === 'string'
        && errorString === Object.prototype.toString.call(error)) {
      if (typeof error.name === 'string') {
        errorString = error.name + ': ' + error.message;
      } else {
        errorString = error.message;
      }
    }

    var eventMessage =
      errorEvent.message == null ? null : String(errorEvent.message);
    var message =
      // If there was no event message, use error string (if any)
      !eventMessage ? errorString
        // If there was no error string, use event message (if any)
        : !errorString ? eventMessage
          // If error string contains event message, use error string
          // e.g. On Edge, IE eventMessage === error.message
          : errorString.indexOf(eventMessage) >= 0 ? errorString
            // If event message contains error string, use event message
            // e.g. On Chrome eventMessage === 'Unhandled ' + errorString
            : eventMessage.indexOf(errorString) >= 0 ? eventMessage
              // Otherwise, combine them
              : eventMessage + ': ' + errorString;

    // FIXME: To get more useful stack information (especially when minified),
    // consider https://github.com/stacktracejs/stacktrace.js
    // May want to send unresolved stack early and resolved later, in case page
    // unloads before source resolution completes.

    var stack = error && error.stack;
    if (stack) {
      // Remove redundancy between stack and message
      var nlPos = stack.indexOf('\n');
      var firstLine = nlPos > 0 ? stack.slice(0, nlPos) : null;
      if (firstLine && message.indexOf(firstLine) >= 0) {
        // First line of stack is included in message.  Remove it.
        stack = stack.slice(nlPos + 1);
      }
    }

    if (!stack) {
      if (errorEvent.filename) {
        stack = '    at ' + errorEvent.filename;
        if (errorEvent.lineno) {
          stack += ':' + errorEvent.lineno;
          if (errorEvent.colno) {
            stack += ':' + errorEvent.colno;
          }
        }
      } else {
        try {
          throw new Error('Reported from');
        } catch (err) {
          stack = err.stack;
        }
      }
    }

    return {
      type: errorEvent.type,
      message: message,
      stack: stack,
      url: location.href,
      referrer: document.referrer,
      // Note: May differ from header due to Compatibility View + X-UA-Compat
      userAgent: navigator.userAgent
    };
  }

  function sendError(errorEvent) {
    if (!reportUrl) {
      logError('Unable to send error report: Report URL not set', errorEvent);
      return false;
    }

    return sendReport(reportUrl, errorEventToReport(errorEvent));
  }

  /** Reports an error to the configured report URL.
   * @param {string=} message Optional error message.
   * @param {*} error Optional error message.
   * @return {bool} Was the report successfully sent or queued to send?
   * Note: An error is logged to the console if the report can not be sent.
   */
  function reportError(message, error) {
    if (error == null && typeof message !== 'string') {
      error = message;
      message = undefined;
    }
    // Log error to console for parity with unhandled exceptions
    logError(message || 'Reporting error', error);
    return sendError({
      type: 'error',
      message: message == null ? undefined : String(message),
      error: error
    });
  }

  /** Reports a rejection to the configured report URL.
   * @param {string=} message Optional error message.
   * @param {*} cause Optional rejection cause.
   * @return {bool} Was the report successfully sent or queued to send?
   * Note: An error is logged to the console if the report can not be sent.
   */
  function reportRejection(message, cause) {
    if (cause == null && typeof message !== 'string') {
      cause = message;
      message = undefined;
    }
    // Log error to console for parity with unhandledrejection events
    logError(message || 'Reporting unhandledrejection', cause);
    return sendError({
      type: 'unhandledrejection',
      message: message == null ? undefined : String(message),
      cause: cause
    });
  }

  /** Sets the URL to which errors are reported.
   * @param {string} newReportUrl URL to which errors should be reported.
   */
  function setReportUrl(newReportUrl) {
    reportUrl = newReportUrl;
  }

  if (window.addEventListener) {
    window.addEventListener('error', sendError, false);
    window.addEventListener('unhandledrejection', sendError, false);
  } else {
    var oldonerror = window.onerror;
    window.onerror = function(message, filename, lineno, colno, error) {
      sendError({
        type: 'error',
        message: message,
        filename: filename,
        lineno: lineno,
        colno: colno,
        error: error
      });
      return oldonerror ? oldonerror.apply(this, arguments) : false;
    };
  }

  return {
    reportError: reportError,
    reportRejection: reportRejection,
    setReportUrl: setReportUrl,
  };
}));

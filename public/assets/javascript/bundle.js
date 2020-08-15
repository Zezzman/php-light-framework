/* Scans for codes and inputs given values into them e.i. like the PHP QueryHelper::scanCodes */
function CodeScanner() {
    var content = "";
    var Scan = function () {
    };
    return {
        Scan: Scan,
    };
}
System.register("dateFormatter", [], function (exports_1, context_1) {
    "use strict";
    var DateFormatter;
    var __moduleName = context_1 && context_1.id;
    return {
        setters: [],
        execute: function () {
            DateFormatter = /** @class */ (function () {
                function DateFormatter(date) {
                    this.date = (function () { if (date == null)
                        return new Date();
                    else
                        return new Date(date); })();
                }
                DateFormatter.prototype.format = function (fallback) {
                    var dayIndex = this.date.getDate();
                    var monthIndex = this.date.getMonth();
                    var day = (dayIndex > 9) ? this.date.getDate().toString() : "0" + dayIndex;
                    var month = (monthIndex > 8) ? (this.date.getMonth() + 1).toString() : "0" + (monthIndex + 1);
                    var year = this.date.getFullYear();
                    return fallback(day, month, year, dayIndex, monthIndex);
                };
                DateFormatter.prototype.toString = function () {
                    return this.date.toString();
                };
                DateFormatter.month = function (month) {
                    var months = [
                        "January", "February", "March",
                        "April", "May", "June", "July",
                        "August", "September", "October",
                        "November", "December"
                    ];
                    return months[parseInt(month, 10)];
                };
                DateFormatter.monthShort = function (month) {
                    var monthShorts = [
                        "Jan", "Feb", "Mar", "Apr",
                        "May", "Jun", "Jul", "Aug",
                        "Sep", "Oct", "Nov", "Dec"
                    ];
                    return monthShorts[parseInt(month, 10)];
                };
                return DateFormatter;
            }());
            exports_1("DateFormatter", DateFormatter);
        }
    };
});
System.register("remote", [], function (exports_2, context_2) {
    "use strict";
    var Remote, remote;
    var __moduleName = context_2 && context_2.id;
    function setupForms() {
        $('form.remote-submit').submit(function (event) {
            var _this = this;
            var form = $(this);
            var formMethod = form.prop('method');
            var formAction = form.prop('action');
            var formData = new FormData(this);
            remote.call(formAction, formData, formMethod, {
                before: function () {
                    form.trigger('submit-before', { this: _this, action: formAction, data: formData });
                },
                complete: function () {
                    form.trigger('submit-complete', _this);
                },
                done: function () {
                    form.trigger('submit-done', _this);
                },
                success: function (data, response, status) {
                    form.trigger('submit-success', { data: data, response: response, status: status, this: _this });
                },
                error: function (response) {
                    form.trigger('submit-error', response);
                }
            });
            event.preventDefault();
        });
    }
    exports_2("setupForms", setupForms);
    return {
        setters: [],
        execute: function () {
            Remote = /** @class */ (function () {
                function Remote(domain) {
                    this.token = $('meta[name="token"]').attr('content');
                    this.domain = domain;
                }
                Remote.prototype.call = function (uri, data, method, callbacks, headers, dataType, domain) {
                    if (method === void 0) { method = "GET"; }
                    if (callbacks === void 0) { callbacks = null; }
                    if (headers === void 0) { headers = {}; }
                    if (dataType === void 0) { dataType = "json"; }
                    if (domain === void 0) { domain = null; }
                    if (domain == null) {
                        domain = this.domain;
                    }
                    headers['token'] = this.token;
                    $.ajax({
                        headers: headers,
                        url: domain + uri,
                        type: method,
                        dataType: dataType,
                        data: data,
                        beforeSend: function () {
                            if (callbacks.hasOwnProperty("before")
                                && (callbacks.before instanceof Function))
                                callbacks.before();
                        },
                        complete: function () {
                            if (callbacks.hasOwnProperty("complete")
                                && (callbacks.complete instanceof Function))
                                callbacks.complete();
                        },
                        success: function (data, status, response) {
                            if (callbacks.hasOwnProperty("success")
                                && (callbacks.success instanceof Function))
                                callbacks.success(data, response, status);
                            if (data['redirect'] !== undefined) {
                                window.location.href = data['redirect'];
                            }
                            return true;
                        },
                        error: function (response) {
                            if (callbacks.hasOwnProperty("error")
                                && (callbacks.error instanceof Function))
                                callbacks.error(response);
                            // if (data['redirect'] !== undefined)
                            // {
                            //     window.location.href = data['redirect'];
                            // }
                            return false;
                        }
                    }).done(function () {
                        if (callbacks.hasOwnProperty("done")
                            && (callbacks.done instanceof Function))
                            callbacks.done();
                    });
                };
                Remote.prototype.listen = function (uri, data, method, interval, callbacks, keepAlive, waitForResponse, threshold) {
                    if (method === void 0) { method = "GET"; }
                    if (interval === void 0) { interval = 5000; }
                    if (callbacks === void 0) { callbacks = null; }
                    if (keepAlive === void 0) { keepAlive = false; }
                    if (waitForResponse === void 0) { waitForResponse = true; }
                    if (threshold === void 0) { threshold = 5; }
                    var Action = function (fallback) {
                        var _this = this;
                        if (waitForResponse) {
                            this.listeners[uri] = true;
                        }
                        remote.call(uri, data, method, {
                            before: callbacks.before,
                            complete: callbacks.complete,
                            done: callbacks.done,
                            success: function (data, response, status) {
                                _this.listeners[uri] = fallback;
                                if (callbacks.hasOwnProperty("success")
                                    && (callbacks.success instanceof Function))
                                    callbacks.success(data, response, status);
                            },
                            error: function (response) {
                                if (!keepAlive) {
                                    _this.closeListener(uri);
                                }
                                else {
                                    _this.listeners[uri] = fallback;
                                }
                                if (callbacks.hasOwnProperty("error")
                                    && (callbacks.error instanceof Function))
                                    callbacks.error(response);
                            }
                        });
                    };
                    var thresholdCount = threshold;
                    if (this.listeners.hasOwnProperty(uri)) {
                        // console.log('Executing');
                        Action(null);
                        this.listeners[uri] = Action;
                    }
                    else {
                        this.listeners[uri] = Action;
                        this.wait(interval, function () {
                            if (this.listeners.hasOwnProperty(uri)
                                && this.listeners[uri]) {
                                if (this.listeners[uri] !== true) {
                                    // console.log('Responded');
                                    thresholdCount = threshold;
                                    this.listeners[uri](Action);
                                }
                                else {
                                    // console.log('Waiting');
                                    if (thresholdCount > 0) {
                                        thresholdCount--;
                                    }
                                    else {
                                        // console.log('Threshold Exceeded');
                                        this.listeners[uri] = false;
                                        if (callbacks.hasOwnProperty("error")
                                            && (callbacks.error instanceof Function))
                                            callbacks.error(false);
                                        return false;
                                    }
                                }
                                return true;
                            }
                            else {
                                return false;
                            }
                        }, true);
                    }
                };
                Remote.prototype.closeListener = function (uri) {
                    if (this.listeners.hasOwnProperty(uri)) {
                        this.listeners[uri] = false;
                        return true;
                    }
                    else {
                        return false;
                    }
                };
                ;
                Remote.prototype.wait = function (interval, fallback, startExecute) {
                    if (startExecute === void 0) { startExecute = false; }
                    if (startExecute) {
                        if (fallback() === false) {
                            return false;
                        }
                    }
                    setTimeout(function () {
                        if (fallback() !== false) {
                            this.wait(interval, fallback, false);
                        }
                        else {
                            return false;
                        }
                    }, interval);
                };
                ;
                Remote.prototype.getListeners = function () {
                    return this.listeners;
                };
                ;
                return Remote;
            }());
            exports_2("Remote", Remote);
            exports_2("remote", remote = new Remote(remoteDomain));
        }
    };
});

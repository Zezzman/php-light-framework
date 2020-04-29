function RemoteAPI() {
    var token = $('meta[name="token"]').attr('content');
    var listeners = {};
    var Call = function (uri, data, method, success, error) {
        if (success === void 0) { success = null; }
        if (error === void 0) { error = null; }
        $.ajax({
            headers: {
                'token': token,
            },
            url: remoteDomain + uri,
            type: method,
            dataType: "json",
            data: data,
            success: function (data, status, response) {
                if (success)
                    success(data, response, status);
                return true;
            },
            error: function (response) {
                if (error)
                    error(response);
                return false;
            }
        });
    };
    var Listen = function (uri, data, method, interval, success, error, keepAlive, waitForResponse, threshold) {
        if (method === void 0) { method = "GET"; }
        if (interval === void 0) { interval = 5000; }
        if (success === void 0) { success = null; }
        if (error === void 0) { error = null; }
        if (keepAlive === void 0) { keepAlive = false; }
        if (waitForResponse === void 0) { waitForResponse = true; }
        if (threshold === void 0) { threshold = 5; }
        var Action = function (fallback) {
            if (waitForResponse) {
                listeners[uri] = true;
            }
            Call(uri, data, method, function (data, response, status) {
                listeners[uri] = fallback;
                if (success)
                    success(data, response, status);
            }, function (response) {
                if (!keepAlive) {
                    CloseListener(uri);
                }
                else {
                    listeners[uri] = fallback;
                }
                if (error)
                    error(response);
            });
        };
        var thresholdCount = threshold;
        if (listeners.hasOwnProperty(uri)) {
            // console.log('Executing');
            Action(null);
            listeners[uri] = Action;
        }
        else {
            listeners[uri] = Action;
            Wait(interval, function () {
                if (listeners.hasOwnProperty(uri)
                    && listeners[uri]) {
                    if (listeners[uri] !== true) {
                        // console.log('Responded');
                        thresholdCount = threshold;
                        listeners[uri](Action);
                    }
                    else {
                        // console.log('Waiting');
                        if (thresholdCount > 0) {
                            thresholdCount--;
                        }
                        else {
                            // console.log('Threshold Exceeded');
                            if (error)
                                error(false);
                            listeners[uri] = false;
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
    var CloseListener = function (uri) {
        if (listeners.hasOwnProperty(uri)) {
            listeners[uri] = false;
            return true;
        }
        else {
            return false;
        }
    };
    var Wait = function (interval, fallback, startExecute) {
        if (startExecute === void 0) { startExecute = false; }
        if (startExecute) {
            if (fallback() === false) {
                return false;
            }
        }
        setTimeout(function () {
            if (fallback() !== false) {
                Wait(interval, fallback, false);
            }
            else {
                return false;
            }
        }, interval);
    };
    return {
        Call: Call,
        Listen: Listen,
        CloseListener: CloseListener,
        listeners: listeners,
    };
}

var Remote = /** @class */ (function () {
    function Remote(domain) {
        this.token = $('meta[name="token"]').attr('content');
        this.domain = domain;
    }
    Remote.prototype.call = function (uri, data, method, success, error, domain, headers, dataType) {
        if (method === void 0) { method = "GET"; }
        if (success === void 0) { success = null; }
        if (error === void 0) { error = null; }
        if (domain === void 0) { domain = null; }
        if (headers === void 0) { headers = {}; }
        if (dataType === void 0) { dataType = "json"; }
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
            success: function (data, status, response) {
                if (success)
                    success(data, response, status);
                if (data['redirect'] !== undefined) {
                    window.location.href = data['redirect'];
                }
                return true;
            },
            error: function (response) {
                if (error)
                    error(response);
                return false;
            }
        });
    };
    Remote.prototype.listen = function (uri, data, method, interval, success, error, keepAlive, waitForResponse, threshold) {
        if (method === void 0) { method = "GET"; }
        if (interval === void 0) { interval = 5000; }
        if (success === void 0) { success = null; }
        if (error === void 0) { error = null; }
        if (keepAlive === void 0) { keepAlive = false; }
        if (waitForResponse === void 0) { waitForResponse = true; }
        if (threshold === void 0) { threshold = 5; }
        var Action = function (fallback) {
            if (waitForResponse) {
                this.listeners[uri] = true;
            }
            this.call(uri, data, method, function (data, response, status) {
                this.listeners[uri] = fallback;
                if (success)
                    success(data, response, status);
            }, function (response) {
                if (!keepAlive) {
                    this.closeListener(uri);
                }
                else {
                    this.listeners[uri] = fallback;
                }
                if (error)
                    error(response);
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
                            if (error)
                                error(false);
                            this.listeners[uri] = false;
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
export { Remote };
export var remote = new Remote(remoteDomain);
function formCollection(selector) {
    var data = {};
    var element = $(selector);
    var inputs = element.find('input, textarea, button[type="submit"]');
    inputs.each(function (index, item) {
        var tag = $(item).prop('tagName');
        var name = $(item).attr('name');
        var type = $(item).attr('type');
        var value = $(item).val();
        data[name] = {
            tag: tag,
            name: name,
            type: type,
            value: value
        };
    });
    return data;
}
$(document).ready(function () {
    $('form.remote-form').submit(function (event) {
        var form = $(this);
        var formAction = $(this).attr('action');
        var formData = formCollection(this);
        form.trigger('form-sending', {
            url: formAction,
            data: formData
        });
        remote.call(formAction, formData, 'POST', function (response) {
            form.trigger('form-success', response);
        }, function (response) {
            form.trigger('form-failed', response);
        }, '');
        event.preventDefault();
    });
});

export declare var remoteDomain: string;

export class Remote
{
    token: string;
    domain: string;
    listeners: {};

    constructor(domain: string)
    {
        this.token = $('meta[name="token"]').attr('content');
        this.domain = domain;
    }

    public call(uri: string, data: [], method: string = "GET", success = null, error = null, headers: {} = {}, dataType: string = "json")
    {
        headers['token'] = this.token;
        $.ajax({
            headers : headers,
            url: this.domain + uri,
            type: method,
            dataType: dataType,
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
    }
    public listen(uri: string, data: [], method: string = "GET", interval: number = 5000,
     success = null, error = null, keepAlive: boolean = false, waitForResponse: boolean = true, threshold: number = 5)
    {
        var Action = function (fallback) {
            if (waitForResponse) {
                this.listeners[uri] = true;
            }
            this.call(uri, data, method, function (data, response, status) {
                this.listeners[uri] = fallback;
                if (success)
                    success(data, response, status);
            }, function (response) {
                if (! keepAlive) {
                    this.closeListener(uri);
                } else {
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
        } else {
            this.listeners[uri] = Action;
            this.wait(interval, function () {
                if (this.listeners.hasOwnProperty(uri)
                && this.listeners[uri]) {
                    if (this.listeners[uri] !== true) {
                        // console.log('Responded');
                        thresholdCount = threshold;
                        this.listeners[uri](Action);
                    } else {
                        // console.log('Waiting');
                        if (thresholdCount > 0) {
                            thresholdCount--;
                        } else {
                            // console.log('Threshold Exceeded');
                            if (error)
                                error(false);
                            this.listeners[uri] = false;
                            return false;
                        }
                    }
                    return true;
                } else {
                    return false;
                }
            }, true);
        }
    }
    public closeListener(uri: string) {
        if (this.listeners.hasOwnProperty(uri)) {
            this.listeners[uri] = false;
            return true;
        } else {
            return false;
        }
    };
    public wait(interval: number, fallback, startExecute: boolean = false) {
        if (startExecute) {
            if (fallback() === false) {
                return false;
            }
        }
        setTimeout(function () {
            if (fallback() !== false) {
                this.wait(interval, fallback, false);
            } else {
                return false;
            }
        }, interval);
    };
    public getListeners() {
        return this.listeners;
    };
}

export var remote = new Remote(remoteDomain);
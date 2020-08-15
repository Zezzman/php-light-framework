export declare var remoteDomain: string;
export interface RemoteCallbacks
{
    before?: () => void;
    complete?: () => void;
    done?: () => void;
    success?: (data: {}, response: JQuery.jqXHR, status: JQuery.Ajax.SuccessTextStatus) => void;
    error?: (response: JQuery.jqXHR | boolean) => void;
}

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

    public call(uri: string, data: {}, method: string = "GET", callbacks: RemoteCallbacks = null,
    headers: {} = {}, dataType: string = "json", domain: string = null)
    {
        if (domain == null)
        {
            domain = this.domain;
        }
        headers['token'] = this.token;
        $.ajax({
            headers: headers,
            url: domain + uri,
            type: method,
            dataType: dataType,
            data: data,
            beforeSend: function ()
            {
                if (callbacks.hasOwnProperty("before")
                && (callbacks.before instanceof Function))
                    callbacks.before();
            },
            complete: function ()
            {
                if (callbacks.hasOwnProperty("complete")
                && (callbacks.complete instanceof Function))
                    callbacks.complete();
            },
            success: function (data, status, response) {
                if (callbacks.hasOwnProperty("success")
                && (callbacks.success instanceof Function))
                    callbacks.success(data, response, status);
                if (data['redirect'] !== undefined)
                {
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
        }).done(function() {
            if (callbacks.hasOwnProperty("done")
                && (callbacks.done instanceof Function))
                    callbacks.done();
        });
    }
    public listen(uri: string, data: {}, method: string = "GET", interval: number = 5000,
    callbacks: RemoteCallbacks = null, keepAlive: boolean = false, waitForResponse: boolean = true, threshold: number = 5)
    {
        var Action = function (fallback) {
            if (waitForResponse) {
                this.listeners[uri] = true;
            }
            remote.call(uri, data, method, {
                before: callbacks.before,
                complete: callbacks.complete,
                done: callbacks.done,
                success: (data, response, status) => {
                    this.listeners[uri] = fallback;
                    if (callbacks.hasOwnProperty("success")
                    && (callbacks.success instanceof Function))
                        callbacks.success(data, response, status);
                },
                error: (response) => {
                    if (! keepAlive) {
                        this.closeListener(uri);
                    } else {
                        this.listeners[uri] = fallback;
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
                            this.listeners[uri] = false;
                            if (callbacks.hasOwnProperty("error")
                            && (callbacks.error instanceof Function))
                                callbacks.error(false);
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

export function setupForms () {
    $('form.remote-submit').submit(function (event)
    {
        var form = $(this);
        var formMethod = form.prop('method');
        var formAction = form.prop('action');
        var formData = new FormData(this as HTMLFormElement);
        remote.call(formAction, formData, formMethod, {
            before: () => {
                form.trigger('submit-before', { this:this, action: formAction, data: formData });
            },
            complete: () => {
                form.trigger('submit-complete', this);
            },
            done: () => {
                form.trigger('submit-done', this);
            },
            success: (data, response, status) => {
                form.trigger('submit-success', { data, response, status, this: this });
            },
            error: (response) => {
                form.trigger('submit-error', response);
            }
        });
        event.preventDefault();
    });
}
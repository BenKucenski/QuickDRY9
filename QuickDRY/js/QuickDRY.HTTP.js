/**
 * HTTP — talking to the server from the browser.
 *
 *   HTTP.Send(url, fields, files, options)   Promise-based (fetch): resolves with the JSON, rejects
 *                                            with an Error whose message is the server's error and
 *                                            whose .data is the JSON. Uploads files. Shows nothing:
 *                                            the caller decides what to tell the user.
 *   HTTP.Post / HTTP.Get                     The older jQuery + callbacks style, with optional wait
 *                                            and error dialogs.
 *
 * The dialogs (NoticeDialog, WaitDialog) come from QuickDRY/controls (ctl_*, BS5/ or SWAL/), which a
 * page includes only if it wants them. Without them, errors fall back to SweetAlert if the page has
 * it, else alert(), and the wait dialog is skipped.
 */
class HTTP {
    /**
     * POST (or GET) a form and get the JSON back.
     *
     * A request failed if the HTTP status isn't OK, the reply isn't JSON, it says success: false,
     * or it has an error (HTTP::ExitJSON(['error' => ...])). The Error's message is the reply's
     * error, message or notice (whichever is text), else "HTTP <status>".
     *
     * @param {string} url
     * @param {Object} [fields]    sent as text; null and undefined as ''
     * @param {Blob|Object} [files] a Blob/File (sent as "file"), or {name: Blob | [Blob, filename]}
     * @param {{method?: string}} [options] method: 'POST' (default) or 'GET' (fields go in the query string; no files)
     * @returns {Promise<Object>}
     */
    static async Send(url, fields = {}, files = null, options = {}) {
        const method = (options.method || 'POST').toUpperCase();
        const init = { method, credentials: 'same-origin', headers: { Accept: 'application/json' } };
        if (method === 'GET') {
            const q = new URLSearchParams();
            Object.entries(fields || {}).forEach(([k, v]) => q.append(k, v == null ? '' : v));
            const qs = q.toString();
            if (qs) url += (url.includes('?') ? '&' : '?') + qs;
        } else {
            const fd = new FormData();
            Object.entries(fields || {}).forEach(([k, v]) => fd.append(k, v == null ? '' : v));
            if (files instanceof Blob) {
                fd.append('file', files, files.name || 'upload.png');
            } else {
                Object.entries(files || {}).forEach(([k, f]) => {
                    if (!f) return;
                    const [blob, name] = Array.isArray(f) ? f : [f, f.name];
                    fd.append(k, blob, name || blob.name || k);
                });
            }
            init.body = fd;
        }
        const res = await fetch(url, init);
        let data;
        try {
            data = await res.json();
        } catch (e) {
            const err = new Error('Server returned HTTP ' + res.status + ' (not JSON).');
            err.data = {};
            err.status = res.status;
            throw err;
        }
        if (!res.ok || !data || data.success === false || data.error) {
            const said = data && [data.error, data.message, data.notice].find(v => typeof v === 'string' && v);
            const err = new Error(said || ('HTTP ' + res.status));
            err.data = data || {};
            err.status = res.status;
            throw err;
        }
        return data;
    }

    /** An error or notice for the user: the page's NoticeDialog if it has one, else SweetAlert, else alert(). */
    static Notice(title, text) {
        if (typeof NoticeDialog === 'function') return NoticeDialog(title, text);
        if (window.Swal) return Swal.fire({ title, html: text == null ? '' : String(text), icon: title === 'Error' ? 'error' : undefined });
        const d = document.createElement('div');
        d.innerHTML = text == null ? '' : String(text);
        alert(title + (d.textContent ? ': ' + d.textContent : ''));
    }

    /** The page's wait dialog, if it has one; nothing otherwise. A callback runs either way. */
    static Wait(title, text, callback) {
        if (typeof WaitDialog === 'function') return WaitDialog(title, text, callback);
        if (typeof callback === 'function') callback();
    }

    static DelayedReloadPage(seconds) {
        setTimeout(() => HTTP.ReloadPage(), seconds * 1000);
    }

    static ReloadPage(title = "Reloading Page", text = "Please Wait") {
        HTTP.Wait(title, text);
        setTimeout(() => {
            location.reload(true);
        }, 1000);
    }

    static RedirectPage(url, title = "Reloading Page", text = "Please Wait") {
        HTTP.Wait(title, text);
        location.href = url;
    }

    static Get(url, vars = {}, callback, error_callback, dialog) {
        return HTTP.Post(url, vars, callback, error_callback, dialog, 'GET');
    }

    static Post(url, vars = {}, callback, error_callback, dialog, method = 'POST') {
        let modalClass = dialog ? dialog.replace('_dialog', '') : null;

        $.ajax({
            method: method,
            url: url,
            data: vars,
            dataType: "json",
            async: true,
            beforeSend: () => {
                if (dialog) HTTP.Wait("Loading", "Please wait...");
            },
            success: (data) => {
                QuickDRY.CloseDialogIfOpen('wait_dialog');
                if (modalClass && window[modalClass]) {
                    window[modalClass]._active = true;
                }

                if (data && data.error) {
                    if (typeof error_callback === "function") {
                        error_callback(data);
                    } else {
                        HTTP.Notice('Error', data.error);
                    }
                } else {
                    if (data && data.success && $.n) {
                        $.n.success(data.success);
                    }
                    if (dialog) {
                        QuickDRY.CloseDialogIfOpen(dialog);
                    }
                    if (typeof callback === "function") {
                        callback(data);
                    }
                }
            },
            error: (xhr) => {
                QuickDRY.CloseDialogIfOpen('wait_dialog');
                if (modalClass && window[modalClass]) {
                    window[modalClass]._active = true;
                }
                let json;
                try {
                    json = JSON.parse(xhr.responseText);
                } catch {
                    json = null;
                }
                if (!json || !json.error) json = Object.assign({}, json, { error: "An unknown error occurred (HTTP " + xhr.status + ")." });
                if (typeof error_callback === "function") {
                    error_callback(json);
                } else {
                    HTTP.Notice('Error', json.error);
                }
            }
        });
    }

    static PostNoJSON(url, vars = {}, callback, error_callback, dialog, method = 'POST') {
        $.ajax({
            method: method,
            url: url,
            data: vars,
            dataType: "json",
            async: true,
            success: (data) => {
                if (typeof callback === "function") {
                    callback(data);
                }
            },
            error: (xhr) => {
                const data = xhr.responseText;
                if (typeof callback === "function") {
                    callback(data);
                }
            }
        });
    }

    static getUrlParams() {
        const params = {};
        const queryString = window.location.search.substring(1);
        queryString.split("&").forEach(part => {
            const [key, value] = part.split("=");
            if (key) params[decodeURIComponent(key)] = decodeURIComponent(value || "");
        });
        return params;
    }

    static IsMobile() {
        const ua = navigator.userAgent || navigator.vendor || window.opera;
        return /android|webos|iphone|ipad|ipod|blackberry|iemobile|opera mini/i.test(ua.toLowerCase());
    }
}

let QueryString = function () {
    // This function is anonymous, is executed immediately and
    // the return value is assigned to QueryString!
    let query_string = {};
    let query = window.location.search.substring(1);
    let vars = query.split("&");
    for (let i = 0; i < vars.length; i++) {
        let pair = vars[i].split("=");
        // If first entry with this name
        if (typeof query_string[pair[0]] === "undefined") {
            query_string[pair[0]] = pair[1];
            // If second entry with this name
        } else if (typeof query_string[pair[0]] === "string") {
            query_string[pair[0]] = [query_string[pair[0]], pair[1]];
            // If third or later entry with this name
        } else {
            query_string[pair[0]].push(pair[1]);
        }
    }
    query_string['base_url'] = window.location.href.split('?')[0];
    return query_string;
}();

function CheckAll(elem, elem_class) {
    $('.' + elem_class).prop('checked', elem.checked);
}

function NewTab(url) {
    window.open(url, '_blank');
}

function scrollToElement(elem) {
    $('html, body').animate({
        scrollTop: $("#" + elem).offset().top
    }, 0);
}


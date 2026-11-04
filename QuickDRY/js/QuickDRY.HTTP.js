class HTTP {
    static DelayedReloadPage(seconds) {
        setTimeout(() => HTTP.ReloadPage(), seconds * 1000);
    }

    static ReloadPage(title = "Reloading Page", text = "Please Wait") {
        WaitDialog(title, text);
        setTimeout(() => {
            location.reload(true);
        }, 1000);
    }

    static RedirectPage(url, title = "Reloading Page", text = "Please Wait") {
        WaitDialog(title, text);
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
                if (dialog) WaitDialog("Loading", "Please wait...");
            },
            success: (data) => {
                QuickDRY.CloseDialogIfOpen('wait_dialog');
                if (modalClass) {
                    window[modalClass]._active = true;
                }

                if (data.error) {
                    if (typeof error_callback === "function") {
                        error_callback(data);
                    } else {
                        NoticeDialog('Error', data.error);
                    }
                } else {
                    if (data.success && $.n) {
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
                if (modalClass) {
                    window[modalClass]._active = true;
                }
                let json;
                try {
                    json = JSON.parse(xhr.responseText);
                } catch {
                    json = { error: "An unknown error occurred." };
                }
                if (typeof error_callback === "function") {
                    error_callback(json);
                } else {
                    NoticeDialog('Error', json.error);
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


/**
 * Sends the session's CSRF token (from <meta name="csrf-token">, see QuickDRY\Web\Csrf::MetaTag())
 * with everything that can change data. Load it after jQuery.
 * The server rejects state-changing requests without it when Web::$RequireCSRFToken is on.
 *
 *   - jQuery requests to this site get an X-CSRF-Token header (QuickDRY.Create/Update/Delete, HTTP.Post, ...)
 *   - fetch() calls to this site get the same header (QuickDRY's HTTP.Send)
 *   - every <form method="post"> gets a hidden csrf_token field
 *   - PostTo(url) / PostLink(a) submit a POST to a URL, for buttons that used to be plain links
 */
(function () {
    'use strict';

    const meta = document.querySelector('meta[name="csrf-token"]');
    const token = meta ? meta.getAttribute('content') : '';
    window.CSRF_TOKEN = token;

    function sameOrigin(url) {
        try {
            return new URL(url, window.location.href).origin === window.location.origin;
        } catch (e) {
            return false;
        }
    }

    if (window.jQuery) {
        jQuery.ajaxPrefilter(function (options, original, xhr) {
            if (!options.crossDomain) {
                xhr.setRequestHeader('X-CSRF-Token', token);
            }
        });
    }

    if (window.fetch) {
        const nativeFetch = window.fetch.bind(window);
        window.fetch = function (input, init) {
            const url = typeof input === 'string' ? input : (input && input.url) || '';
            if (sameOrigin(url)) {
                init = Object.assign({}, init);
                const headers = new Headers(init.headers || (input instanceof Request ? input.headers : undefined));
                headers.set('X-CSRF-Token', token);
                init.headers = headers;
            }
            return nativeFetch(input, init);
        };
    }

    function addToken(form) {
        if (!form || form.tagName !== 'FORM') {
            return;
        }
        if ((form.getAttribute('method') || 'get').toLowerCase() !== 'post') {
            return;
        }
        if (!sameOrigin(form.action || window.location.href)) {
            return;
        }
        let input = form.querySelector('input[name="csrf_token"]');
        if (!input) {
            input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'csrf_token';
            form.appendChild(input);
        }
        input.value = token;
    }

    // normal submits, and forms already on the page (covers form.submit(), which fires no submit event)
    document.addEventListener('submit', function (e) {
        addToken(e.target);
    }, true);
    document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('form').forEach(addToken);
    });

    /** Submit a POST to url (the query string carries the parameters). */
    window.PostTo = function (url) {
        const form = document.createElement('form');
        form.method = 'post';
        form.action = url;
        form.style.display = 'none';
        document.body.appendChild(form);
        addToken(form);
        form.submit();
    };

    /** onclick="return PostLink(this);" turns a link into a POST to its href. */
    window.PostLink = function (link) {
        window.PostTo(link.href);
        return false;
    };
})();

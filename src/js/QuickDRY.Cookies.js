var Cookies = {
    Set: function (name, value, expires) {
        if (expires * 1.0 === 0.0)
            expires = 365;

        $.cookie(name, value, {
            expires: expires,
            path: '/',
            domain: DOMAIN
        });
    },

    Clear: function (name) {
        $.cookie(name, null, {
            expires: -1,
            path: '/',
            domain: DOMAIN
        });
    },

    Get: function (name) {
        return $.cookie(name);
    }

};
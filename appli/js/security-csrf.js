/* Shared transport for the existing web pages and Android WebViews. */
(function () {
    'use strict';
    var config = window.bridgetteSecurity;
    var base = new URL(config.base, location.origin);
    function action(url) {
        var target = new URL(url, location.href);
        if (target.origin !== base.origin) return null;
        // Installed Android pages use /action.php even in a nested installation.
        // Resolve those known action names inside this application before adding a secret.
        var rootName = target.pathname.replace(/^\//, '').replace(/\/$/, '');
        if (config.routes.indexOf(rootName) !== -1) target.pathname = base.pathname + rootName;
        var relative = target.pathname.slice(base.pathname.length).replace(/\/$/, '');
        return target.origin === base.origin && target.pathname.indexOf(base.pathname) === 0 &&
            config.routes.indexOf(relative) !== -1 ? target : null;
    }
    function postNavigation(target, destination) {
        var form = document.createElement('form');
        form.method = 'post';
        if (destination) form.target = destination;
        var params = new URLSearchParams(target.search);
        params.set('_csrf', config.token);
        target.search = '';
        form.action = target.href;
        params.forEach(function (value, name) {
            var input = document.createElement('input');
            input.type = 'hidden'; input.name = name; input.value = value;
            form.appendChild(input);
        });
        document.body.appendChild(form);
        form.submit();
    }
    window.bridgetteNavigate = function (url) {
        var target = action(url);
        if (target) postNavigation(target);
        else location.replace(url);
    };
    document.addEventListener('click', function (event) {
        var link = event.target.closest('a[href]');
        var target = link && action(link.href);
        if (target) { event.preventDefault(); postNavigation(target, link.target); }
    });
    // Loaded after jQuery, before each page's application scripts.
    if (window.jQuery) {
        jQuery.ajaxPrefilter(function (options) {
            var target = action(options.url);
            if (!target) return;
            var query = target.search.slice(1);
            target.search = '';
            options.url = target.href;
            options.type = 'POST';
            options.data = [query, options.data || ''].filter(Boolean).join('&');
            options.headers = Object.assign({}, options.headers, {'X-CSRF-Token': config.token});
        });
    }
    window.bridgetteFinish = function (tournament, pair) {
        var body = new URLSearchParams({idtournoi: tournament, table: pair, _csrf: config.token});
        return fetch(new URL('setcnxfin.php', base).href, {
            method: 'POST', credentials: 'same-origin', keepalive: true, body: body
        }).then(function (response) {
            if (!response.ok) throw new Error('Fin de connexion refusée (' + response.status + ')');
        }).catch(function (error) {
            var message = document.createElement('p');
            message.textContent = error.message + '. Rechargez la page du club.';
            document.body.appendChild(message);
        });
    };
}());

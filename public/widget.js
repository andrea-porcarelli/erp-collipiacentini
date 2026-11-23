(function () {
    'use strict';

    var MITICKO_BASE = (document.currentScript && new URL(document.currentScript.src).origin) || 'https://widget.miticko.com';

    function mount(container) {
        var productId = container.getAttribute('data-miticko-product-id');
        var partnerToken = container.getAttribute('data-miticko-token');

        if (!productId) {
            console.error('[Miticko] data-miticko-product-id mancante sul contenitore widget');
            return;
        }

        var iframe = document.createElement('iframe');
        iframe.src = MITICKO_BASE + '/widget/booking/' + encodeURIComponent(productId) +
            (partnerToken ? '?t=' + encodeURIComponent(partnerToken) : '');
        iframe.style.width = '100%';
        iframe.style.border = '0';
        iframe.style.minHeight = '640px';
        iframe.setAttribute('allow', 'payment');
        iframe.setAttribute('loading', 'lazy');
        iframe.setAttribute('title', 'Prenotazione Miticko');

        container.replaceChildren(iframe);

        window.addEventListener('message', function (event) {
            if (event.source !== iframe.contentWindow) return;
            if (!event.data || event.data.source !== 'miticko-widget') return;

            if (event.data.type === 'resize' && typeof event.data.height === 'number') {
                iframe.style.height = event.data.height + 'px';
            }
        });
    }

    function init() {
        var nodes = document.querySelectorAll('[data-miticko-widget]');
        for (var i = 0; i < nodes.length; i++) mount(nodes[i]);
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();

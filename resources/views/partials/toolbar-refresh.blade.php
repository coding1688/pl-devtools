<script data-devtools-toolbar-refresh>
(function () {
    var widget = document.querySelector('[data-sql-debug][data-refresh-url]');
    if (!widget || !window.fetch) { return; }
    var inIframe = window.top !== window.self;
    @if(! config('pl.devtools.toolbar_in_iframes', true))
    if (inIframe) { widget.remove(); return; }
    @endif
    if (inIframe) { return; }
    var interval = parseInt('{{ (int) config('pl.devtools.toolbar_refresh', 2) }}', 10);
    if (!interval || interval < 1) { return; }
    var pinned = null;
    var busy = false;

    var select = function () { return widget.querySelector('[data-devtools-select]'); };
    var url = function () {
        var base = widget.getAttribute('data-refresh-url');
        return pinned ? base + '&request=' + encodeURIComponent(pinned) : base;
    };

    var apply = function (html, wasOpen, innerOpen) {
        var holder = document.createElement('div');
        holder.innerHTML = html.trim();
        var next = holder.querySelector('[data-sql-debug]');
        if (!next) { return; }
        next.open = wasOpen;
        var nextInner = next.querySelector('.sql-debug-request');
        if (nextInner && innerOpen !== null) { nextInner.open = innerOpen; }
        widget.replaceWith(next);
        widget = next;
        bindSelect();
        if (window.devtoolsBindTabs) { window.devtoolsBindTabs(); }
    };

    var load = function () {
        if (busy || document.hidden) { return; }
        busy = true;
        var wasOpen = widget.open;
        var inner = widget.querySelector('.sql-debug-request');
        var innerOpen = inner ? inner.open : null;
        fetch(url(), { headers: { 'Accept': 'text/html' }, credentials: 'same-origin' })
            .then(function (response) { return response.ok ? response.text() : null; })
            .then(function (html) {
                if (!html) { return; }
                var holder = document.createElement('div');
                holder.innerHTML = html.trim();
                var next = holder.querySelector('[data-sql-debug]');
                if (!next || next.getAttribute('data-signature') === widget.getAttribute('data-signature')) { return; }
                apply(html, wasOpen, innerOpen);
            })
            .catch(function () {})
            .finally(function () { busy = false; });
    };

    function bindSelect() {
        var el = select();
        if (!el) { return; }
        el.value = pinned || '';
        el.addEventListener('change', function (event) {
            pinned = event.target.value || null;
            load();
        });
    }

    bindSelect();
    setInterval(function () {
        if (pinned) { return; }
        load();
    }, interval * 1000);
})();
</script>

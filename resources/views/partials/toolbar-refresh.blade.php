<script data-devtools-toolbar-refresh>
(function () {
    var widget = document.querySelector('[data-sql-debug][data-refresh-url]');
    if (!widget || !window.fetch || !document.currentScript) { return; }
    var inIframe = window.top !== window.self;
    @if(! config('pl.devtools.toolbar_in_iframes', true))
    if (inIframe) { widget.remove(); return; }
    @endif
    if (inIframe) { return; }
    var interval = parseInt('{{ (int) config('pl.devtools.toolbar_refresh', 2) }}', 10);
    if (!interval || interval < 1) { return; }
    var busy = false;
    var refresh = function () {
        if (document.hidden || busy) { return; }
        busy = true;
        var wasOpen = widget.open;
        var inner = widget.querySelector('.sql-debug-request');
        var innerOpen = inner ? inner.open : null;
        fetch(widget.getAttribute('data-refresh-url'), { headers: { 'Accept': 'text/html' }, credentials: 'same-origin' })
            .then(function (response) { return response.ok ? response.text() : null; })
            .then(function (html) {
                if (!html) { return; }
                var holder = document.createElement('div');
                holder.innerHTML = html.trim();
                var next = holder.querySelector('[data-sql-debug]');
                if (!next || next.getAttribute('data-signature') === widget.getAttribute('data-signature')) { return; }
                next.open = wasOpen;
                var nextInner = next.querySelector('.sql-debug-request');
                if (nextInner && innerOpen !== null) { nextInner.open = innerOpen; }
                widget.replaceWith(next);
                widget = next;
                if (window.devtoolsBindTabs) { window.devtoolsBindTabs(); }
            })
            .catch(function () {})
            .finally(function () { busy = false; });
    };
    setInterval(refresh, interval * 1000);
})();
</script>

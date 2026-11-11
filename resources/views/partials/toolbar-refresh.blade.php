<script data-devtools-toolbar-refresh>
(function () {
    var widget = document.querySelector('[data-sql-debug][data-refresh-url]');
    if (!widget || !window.fetch || !document.currentScript) { return; }
    var busy = false;
    var interval = parseInt('{{ (int) config('pl.devtools.toolbar_refresh', 2) }}', 10);
    if (!interval || interval < 1) { return; }
    var refresh = function () {
        if (document.hidden || busy) { return; }
        busy = true;
        fetch(widget.getAttribute('data-refresh-url'), { headers: { 'Accept': 'text/html' }, credentials: 'same-origin' })
            .then(function (response) { return response.ok ? response.text() : null; })
            .then(function (html) {
                if (!html) { return; }
                var holder = document.createElement('div');
                holder.innerHTML = html.trim();
                var next = holder.querySelector('[data-sql-debug]');
                if (!next || next.getAttribute('data-signature') === widget.getAttribute('data-signature')) { return; }
                next.open = widget.open;
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

<script data-devtools-tabs-script>
document.querySelectorAll('[data-devtools-tabs]').forEach(function (root) {
    if (root.dataset.ready === '1') { return; }
    root.dataset.ready = '1';
    var tabs = root.querySelectorAll('[data-devtools-tab]');
    var panels = root.querySelectorAll('[data-devtools-panel]');
    tabs.forEach(function (tab) {
        tab.addEventListener('click', function (event) {
            event.preventDefault();
            event.stopPropagation();
            tabs.forEach(function (other) { other.classList.toggle('is-active', other === tab); });
            panels.forEach(function (panel) { panel.hidden = panel.getAttribute('data-devtools-panel') !== tab.getAttribute('data-devtools-tab'); });
        });
    });
});
</script>

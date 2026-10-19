{{-- Applies the text size and theme chosen in Display Settings before the page paints (saved per device) --}}
<script>
    (function () {
        try {
            var size = localStorage.getItem('fleet_text_size');
            if (size === 'large' || size === 'larger' || size === 'largest') {
                document.documentElement.setAttribute('data-text-size', size);
            }
            if (localStorage.getItem('fleet_theme') === 'light') {
                document.documentElement.setAttribute('data-theme', 'light');
            }
        } catch (e) {
            // Storage blocked (private mode, policy): keep the standard size and the green theme
        }
    })();
</script>

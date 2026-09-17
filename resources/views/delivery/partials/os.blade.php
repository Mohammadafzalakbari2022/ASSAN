<script>
    (function () {
        try {
            var ua = navigator.userAgent || '';
            var mobile = /Android|iPhone|iPad|iPod|Mobile|Silk|Kindle/i.test(ua);

            if (!mobile && /Macintosh/.test(ua) && navigator.maxTouchPoints > 1) {
                mobile = true;
            }

            if (navigator.userAgentData && typeof navigator.userAgentData.mobile === 'boolean') {
                mobile = navigator.userAgentData.mobile;
            }

            if (!mobile) {
                document.documentElement.classList.add('os-desktop');
            }
        } catch (e) {}
    })();
</script>

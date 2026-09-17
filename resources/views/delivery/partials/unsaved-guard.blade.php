<script>
    (function () {
        var dirty = document.body.hasAttribute('data-unsaved');

        document.querySelectorAll('form input, form textarea, form select').forEach(function (field) {
            var mark = function () { dirty = true; };
            field.addEventListener('input', mark);
            field.addEventListener('change', mark);
        });

        document.querySelectorAll('form').forEach(function (form) {
            form.addEventListener('submit', function () { dirty = false; });
        });

        window.addEventListener('beforeunload', function (event) {
            if (!dirty) { return undefined; }

            event.preventDefault();
            event.returnValue = '';
            return '';
        });

        var firstError = document.querySelector('.err');
        if (firstError) {
            var field = firstError.parentNode
                ? firstError.parentNode.querySelector('input, textarea, select')
                : null;

            if (field && typeof field.focus === 'function') {
                try { field.focus({ preventScroll: true }); } catch (e) { field.focus(); }
            }

            if (typeof firstError.scrollIntoView === 'function') {
                firstError.scrollIntoView({ block: 'center', behavior: 'smooth' });
            }
        }
    })();
</script>

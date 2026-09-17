<div class="history-nav" role="group" aria-label="History">
    <button type="button" class="btn" data-history-back aria-label="Go back">Back</button>
    <button type="button" class="btn" data-history-forward aria-label="Go forward">Forward</button>
</div>

<script>
    (function () {
        var KEY = 'asan.nav.history';
        var NAV = 'asan.nav.via';

        function here() { return location.pathname + location.search; }

        function read() {
            try {
                var parsed = JSON.parse(sessionStorage.getItem(KEY) || 'null');
                if (parsed && Array.isArray(parsed.stack) && typeof parsed.index === 'number'
                    && parsed.index >= 0 && parsed.index < parsed.stack.length) {
                    return parsed;
                }
            } catch (e) {}
            return null;
        }

        function write(state) {
            try { sessionStorage.setItem(KEY, JSON.stringify(state)); } catch (e) {}
        }

        var current = here();
        var viaButton = false;
        try {
            viaButton = sessionStorage.getItem(NAV) === '1';
            sessionStorage.removeItem(NAV);
        } catch (e) {}

        var state = read();

        if (!state) {
            state = { stack: [current], index: 0 };
        } else if (state.stack[state.index] !== current) {
            var found = state.stack.lastIndexOf(current);

            if (viaButton && found !== -1) {
                state.index = found;
            } else {
                state.stack = state.stack.slice(0, state.index + 1);
                if (state.stack[state.stack.length - 1] !== current) { state.stack.push(current); }
                state.index = state.stack.length - 1;
            }
        }

        write(state);

        function paint() {
            document.querySelectorAll('[data-history-back]').forEach(function (button) {
                button.disabled = state.index <= 0;
            });
            document.querySelectorAll('[data-history-forward]').forEach(function (button) {
                button.disabled = state.index >= state.stack.length - 1;
            });
        }

        function go(delta) {
            var next = state.index + delta;
            if (next < 0 || next >= state.stack.length) { return; }

            state.index = next;
            write(state);
            try { sessionStorage.setItem(NAV, '1'); } catch (e) {}
            location.assign(state.stack[next]);
        }

        document.querySelectorAll('[data-history-back]').forEach(function (button) {
            button.addEventListener('click', function () { go(-1); });
        });

        document.querySelectorAll('[data-history-forward]').forEach(function (button) {
            button.addEventListener('click', function () { go(1); });
        });

        document.addEventListener('keydown', function (event) {
            if (!document.documentElement.classList.contains('os-desktop')) { return; }

            if (event.altKey && event.key === 'ArrowLeft') { event.preventDefault(); go(-1); }
            else if (event.altKey && event.key === 'ArrowRight') { event.preventDefault(); go(1); }
            else if (event.key === 'Escape' && !event.shiftKey) { go(-1); }
        });

        paint();
    })();
</script>

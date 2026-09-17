@auth
    @if (auth()->user()->isDelivery())
        <div class="tracker" id="tracker" data-url="{{ route('delivery.location') }}" role="status" aria-live="polite">
            <div class="tracker-inner">
                <span class="tracker-text" id="tracker-text">Location sharing is off</span>
                <button class="btn primary" id="tracker-btn" type="button">Share my location</button>
            </div>
        </div>

        <script>
            (function () {
                var el = document.getElementById('tracker');
                var text = document.getElementById('tracker-text');
                var btn = document.getElementById('tracker-btn');
                if (!el || !text || !btn) return;

                document.body.classList.add('has-tracker');

                if (!('geolocation' in navigator)) {
                    btn.style.display = 'none';
                    text.textContent = 'This phone cannot share its location.';
                    return;
                }

                var url = el.getAttribute('data-url');
                var tokenMeta = document.querySelector('meta[name="csrf-token"]');
                var token = tokenMeta ? tokenMeta.getAttribute('content') : '';
                var watchId = null;
                var lastSentAt = 0;
                var lastLat = null;
                var lastLng = null;

                function label(value) {
                    text.textContent = value;
                }

                function setButton(show) {
                    btn.style.display = show ? '' : 'none';
                }

                function remember(on) {
                    try { localStorage.setItem('delivery.tracking', on ? '1' : '0'); } catch (e) {}
                }

                function send(position) {
                    var c = position.coords;
                    var now = Date.now();

                    var moved = 0;
                    if (lastLat !== null) {
                        var dLat = c.latitude - lastLat;
                        var dLng = c.longitude - lastLng;
                        moved = Math.sqrt((dLat * dLat) + (dLng * dLng));
                    }

                    if (now - lastSentAt < 15000 && moved < 0.00025) {
                        return;
                    }

                    lastSentAt = now;
                    lastLat = c.latitude;
                    lastLng = c.longitude;

                    var body = {
                        latitude: c.latitude,
                        longitude: c.longitude,
                        accuracy: c.accuracy,
                        speed: c.speed
                    };

                    fetch(url, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': token
                        },
                        credentials: 'same-origin',
                        body: JSON.stringify(body)
                    }).then(function (response) {
                        if (!response.ok) { throw new Error('failed'); }
                        label('Sharing location · sent ' + new Date().toLocaleTimeString());
                    }).catch(function () {
                        label('Could not send your location — check the connection.');
                    });
                }

                function onError(error) {
                    if (error && error.code === 1) {
                        label('Location is blocked. Allow location access to appear on the map.');
                        setButton(true);
                        remember(false);
                    } else {
                        label('Location is unavailable right now. Trying again…');
                    }
                }

                function start() {
                    if (watchId !== null) { return; }

                    setButton(false);
                    label('Starting location sharing…');
                    remember(true);

                    try {
                        watchId = navigator.geolocation.watchPosition(send, onError, {
                            enableHighAccuracy: true,
                            maximumAge: 5000,
                            timeout: 20000
                        });
                    } catch (e) {
                        label('Location is unavailable right now.');
                        setButton(true);
                        remember(false);
                    }
                }

                btn.addEventListener('click', start);

                var wanted = null;
                try { wanted = localStorage.getItem('delivery.tracking'); } catch (e) {}

                if (wanted === '1') {
                    start();
                } else {
                    label('Turn on location sharing so the office can see you.');
                }
            })();
        </script>
    @endif
@endauth

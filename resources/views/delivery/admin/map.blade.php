@extends('delivery.admin.layout')

@section('title', 'Live map')

@push('head')
    <link rel="stylesheet" href="{{ asset('leaflet/leaflet.css') }}">
    <style>
        .map-grid { display:grid; grid-template-columns:1fr; gap:1rem; }
        @media (min-width: 60rem) { .map-grid { grid-template-columns:2fr 1fr; } }
        #map { height:65vh; min-height:22rem; border-radius:0.75rem; border:1px solid var(--line); background:#e8ebe9; }
        .side { display:flex; flex-direction:column; gap:0.6rem; }
        .person { display:flex; justify-content:space-between; gap:0.75rem; align-items:flex-start; padding:0.6rem 0.75rem; border:1px solid var(--line); border-radius:0.6rem; background:#fff; cursor:pointer; text-align:start; width:100%; font:inherit; color:inherit; }
        .person:hover { border-color:var(--brand); }
        .person .who { font-weight:600; }
        .person .meta { color:var(--muted); font-size:0.82rem; }
        .dot { display:inline-block; width:0.65rem; height:0.65rem; border-radius:999px; margin-inline-end:0.4rem; }
        .dot.live { background:#1a7f37; }
        .dot.stale { background:#b26a00; }
        .dot.offline { background:#9aa1a9; }
        .map-status { color:var(--muted); font-size:0.85rem; }
        .legend { display:flex; gap:1rem; flex-wrap:wrap; font-size:0.82rem; color:var(--muted); margin-block-start:0.5rem; }
    </style>
@endpush

@section('content')
    <div class="toolbar">
        <h1>Live map</h1>
        <div>
            <span class="map-status" id="map-status">Loading…</span>
        </div>
    </div>

    <div class="map-grid">
        <div>
            <div id="map"></div>
            <div class="legend">
                <span><span class="dot live"></span>Live (seen in the last 2 minutes)</span>
                <span><span class="dot stale"></span>Stale (up to 15 minutes)</span>
                <span><span class="dot offline"></span>Offline</span>
            </div>
            <p class="hint">
                A phone shows here only while the delivery page is open on it. If the screen is
                locked or the page is closed, the dot greys out until it opens again.
            </p>
        </div>

        <div class="side" id="people">
            <p class="muted">No delivery people are on duty.</p>
        </div>
    </div>
@endsection

@push('scripts')
    <script src="{{ asset('leaflet/leaflet.js') }}"></script>
    <script>
        (function () {
            var dataUrl = @json(route('delivery.admin.map.locations'));
            var mapEl = document.getElementById('map');
            var peopleEl = document.getElementById('people');
            var statusEl = document.getElementById('map-status');
            if (!mapEl || typeof L === 'undefined') { return; }

            var colors = { live: '#1a7f37', stale: '#b26a00', offline: '#9aa1a9' };

            var map = L.map(mapEl).setView([34.5553, 69.2075], 12);

            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                maxZoom: 19,
                attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
            }).addTo(map);

            var markers = {};

            function escapeHtml(value) {
                var div = document.createElement('div');
                div.textContent = value === null || value === undefined ? '' : String(value);
                return div.innerHTML;
            }

            function popupHtml(person) {
                var lines = ['<strong>' + escapeHtml(person.name) + '</strong>'];
                if (person.phone) { lines.push('<a href="tel:' + escapeHtml(person.phone) + '">' + escapeHtml(person.phone) + '</a>'); }
                lines.push('<span class="muted">' + escapeHtml(person.status) + '</span>');
                if (person.last_seen_human) { lines.push('Last seen ' + escapeHtml(person.last_seen_human)); }
                if (person.accuracy) { lines.push('Accuracy ±' + Math.round(person.accuracy) + ' m'); }
                lines.push(person.active_orders + ' active order(s)');
                return lines.join('<br>');
            }

            function draw(people) {
                var seen = {};

                people.forEach(function (person) {
                    seen[person.id] = true;

                    if (person.lat === null || person.lng === null) {
                        if (markers[person.id]) { map.removeLayer(markers[person.id]); delete markers[person.id]; }
                        return;
                    }

                    var color = colors[person.status] || colors.offline;
                    var point = [person.lat, person.lng];

                    if (markers[person.id]) {
                        markers[person.id].setLatLng(point).setStyle({ color: color });
                        markers[person.id].setPopupContent(popupHtml(person));
                    } else {
                        markers[person.id] = L.circleMarker(point, {
                            radius: 8, color: color, fillColor: color, fillOpacity: 0.9, weight: 2
                        }).addTo(map).bindPopup(popupHtml(person));
                    }

                    markers[person.id]._trail = person.trail;

                    if (person.trail && person.trail.length > 1) {
                        if (markers[person.id]._line) {
                            markers[person.id]._line.setLatLngs(person.trail).setStyle({ color: color });
                        } else {
                            markers[person.id]._line = L.polyline(person.trail, {
                                color: color, weight: 3, opacity: 0.6, dashArray: '6 6'
                            }).addTo(map);
                        }
                    } else if (markers[person.id]._line) {
                        map.removeLayer(markers[person.id]._line);
                        markers[person.id]._line = null;
                    }
                });

                Object.keys(markers).forEach(function (id) {
                    if (!seen[id]) {
                        if (markers[id]._line) { map.removeLayer(markers[id]._line); }
                        map.removeLayer(markers[id]);
                        delete markers[id];
                    }
                });
            }

            function renderList(people) {
                if (!people.length) {
                    peopleEl.innerHTML = '<p class="muted">No delivery people are on duty.</p>';
                    return;
                }

                peopleEl.innerHTML = '';

                people.forEach(function (person) {
                    var button = document.createElement('button');
                    button.type = 'button';
                    button.className = 'person';
                    button.innerHTML =
                        '<span><span class="who"><span class="dot ' + escapeHtml(person.status) + '"></span>' +
                        escapeHtml(person.name) + '</span>' +
                        '<div class="meta">' +
                        (person.last_seen_human ? 'Last seen ' + escapeHtml(person.last_seen_human) : 'Not seen yet') +
                        ' · ' + person.active_orders + ' active</div></span>';

                    button.addEventListener('click', function () {
                        if (person.lat !== null && person.lng !== null) {
                            map.setView([person.lat, person.lng], 16);
                            if (markers[person.id]) { markers[person.id].openPopup(); }
                        }
                    });

                    peopleEl.appendChild(button);
                });
            }

            function load() {
                fetch(dataUrl, { headers: { 'Accept': 'application/json' }, credentials: 'same-origin' })
                    .then(function (response) {
                        if (!response.ok) { throw new Error('failed'); }
                        return response.json();
                    })
                    .then(function (data) {
                        draw(data.staff || []);
                        renderList(data.staff || []);
                        statusEl.textContent = 'Updated ' + new Date().toLocaleTimeString();
                    })
                    .catch(function () {
                        statusEl.textContent = 'Could not refresh — showing the last known positions.';
                    });
            }

            load();
            setInterval(load, 10000);
        })();
    </script>
@endpush

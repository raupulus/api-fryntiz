import 'ol/ol.css';
import Feature from 'ol/Feature';
import Map from 'ol/Map';
import View from 'ol/View';
import LineString from 'ol/geom/LineString';
import Point from 'ol/geom/Point';
import TileLayer from 'ol/layer/Tile';
import VectorLayer from 'ol/layer/Vector';
import { fromLonLat } from 'ol/proj';
import OSM from 'ol/source/OSM';
import VectorSource from 'ol/source/Vector';
import { Circle as CircleStyle, Fill, RegularShape, Stroke, Style, Text } from 'ol/style';

const EARTH_RADIUS_KM = 6371;
const NM_PER_KM = 0.539957;

function toRadians(deg) {
    return (deg * Math.PI) / 180;
}

function haversineKm(lat1, lon1, lat2, lon2) {
    const dLat = toRadians(lat2 - lat1);
    const dLon = toRadians(lon2 - lon1);
    const a = Math.sin(dLat / 2) ** 2
        + Math.cos(toRadians(lat1)) * Math.cos(toRadians(lat2)) * Math.sin(dLon / 2) ** 2;

    return EARTH_RADIUS_KM * 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
}

function formatAltitudeMetersToFeet(altitudeMeters) {
    if (altitudeMeters === null || altitudeMeters === undefined) {
        return '—';
    }
    return `${Math.round(Number(altitudeMeters) * 3.28084)} ft`;
}

function formatSpeedMsToKnots(speedMs) {
    if (speedMs === null || speedMs === undefined) {
        return '—';
    }
    return `${Math.round(Number(speedMs) * 1.94384)} kt`;
}

function buildTextCell(value, className = '') {
    const td = document.createElement('td');
    td.className = `px-2.5 py-2 whitespace-nowrap ${className}`.trim();
    td.textContent = (value === null || value === undefined || value === '') ? '—' : String(value);

    return td;
}

function createPlaneStyle(plane, isSelected) {
    const label = String(plane.flight || plane.icao || '').trim();
    const rotation = plane.track !== null && plane.track !== undefined
        ? (Number(plane.track) * Math.PI) / 180
        : 0;

    const hasEmergency = ['7500', '7600', '7700'].includes(String(plane.squawk || ''));
    const fillColor = hasEmergency
        ? '#dc2626'
        : (isSelected ? '#f59e0b' : '#2563eb');

    return new Style({
        image: new RegularShape({
            points: 3,
            radius: isSelected ? 10 : 8,
            rotation,
            rotateWithView: true,
            fill: new Fill({ color: fillColor }),
            stroke: new Stroke({ color: '#ffffff', width: 1.5 }),
        }),
        text: new Text({
            text: label,
            offsetY: -16,
            font: '600 11px "Inter Variable", Inter, sans-serif',
            fill: new Fill({ color: isSelected ? '#92400e' : '#0f172a' }),
            stroke: new Stroke({ color: '#ffffff', width: 3 }),
        }),
    });
}

function initAirflightRadar() {
    const box = document.getElementById('box-airflight');
    const mapCanvas = document.getElementById('map_canvas');

    if (!box || !mapCanvas) {
        return;
    }

    const aircraftsUrl = box.dataset.aircraftsUrl || '';
    const receiverUrl = box.dataset.receiverUrl || '';

    const state = {
        receiver: { lat: 36.7381, lon: -6.4301, refresh: 5000 },
        planes: [],
        selectedIcao: null,
        followSelected: false,
        sortKey: 'seen',
        sortAsc: true,
        timerId: null,
    };

    const receiverSource = new VectorSource();
    const trailSource = new VectorSource();
    const planeSource = new VectorSource();

    const receiverLayer = new VectorLayer({
        source: receiverSource,
        style: new Style({
            image: new CircleStyle({
                radius: 6,
                fill: new Fill({ color: '#0ea5e9' }),
                stroke: new Stroke({ color: '#ffffff', width: 2 }),
            }),
        }),
    });

    const trailLayer = new VectorLayer({
        source: trailSource,
        style: new Style({
            stroke: new Stroke({ color: '#2563eb', width: 2 }),
        }),
    });

    const planeLayer = new VectorLayer({
        source: planeSource,
    });

    const view = new View({
        center: fromLonLat([state.receiver.lon, state.receiver.lat]),
        zoom: 9,
        maxZoom: 16,
        minZoom: 4,
    });

    const map = new Map({
        target: mapCanvas,
        layers: [
            new TileLayer({
                source: new OSM({ crossOrigin: 'anonymous' }),
            }),
            receiverLayer,
            trailLayer,
            planeLayer,
        ],
        view,
    });

    function setReceiverMarker(lat, lon) {
        receiverSource.clear();
        receiverSource.addFeature(new Feature({
            geometry: new Point(fromLonLat([lon, lat])),
        }));
    }

    setReceiverMarker(state.receiver.lat, state.receiver.lon);

    function setText(id, value) {
        const el = document.getElementById(id);
        if (el) {
            el.textContent = value;
        }
    }

    function toggleHidden(id, hide) {
        const el = document.getElementById(id);
        if (el) {
            el.hidden = hide;
        }
    }

    function updateSelectedPanel() {
        const selected = state.planes.find((p) => p.icao === state.selectedIcao) || null;
        if (!selected) {
            toggleHidden('selected_infoblock', true);
            return;
        }

        toggleHidden('selected_infoblock', false);

        const callsign = String(selected.flight || selected.icao || '—').trim();
        setText('selected_callsign', callsign);
        setText('selected_registration', selected.registration || '');
        setText('selected_icaotype', selected.aircraft_type ? `(${selected.aircraft_type})` : '');
        setText('selected_emergency', selected.emergency && selected.emergency !== 'none' ? ` [${selected.emergency}]` : '');
        setText('selected_country', selected.country || 'Desconocido');
        setText('selected_altitude', formatAltitudeMetersToFeet(selected.altitude));
        setText('selected_squawk', selected.squawk || '—');
        setText('selected_speed', formatSpeedMsToKnots(selected.speed));
        setText('selected_rssi', selected.rssi !== null && selected.rssi !== undefined ? `${Number(selected.rssi).toFixed(1)} dBFS` : '—');
        setText('selected_track', selected.track !== null && selected.track !== undefined ? `${selected.track}°` : '—');
        setText('selected_seen', selected.seen !== null && selected.seen !== undefined ? `${selected.seen} s` : '—');

        const hasCoords = selected.lat !== null && selected.lat !== undefined
            && selected.lon !== null && selected.lon !== undefined;

        if (hasCoords) {
            const latNum = Number(selected.lat);
            const lonNum = Number(selected.lon);
            const distKm = haversineKm(state.receiver.lat, state.receiver.lon, latNum, lonNum);
            setText('selected_position', `${latNum.toFixed(4)}, ${lonNum.toFixed(4)}`);
            setText('selected_sitedist', `${distKm.toFixed(1)} km (${(distKm * NM_PER_KM).toFixed(1)} nm)`);

            if (state.followSelected) {
                view.animate({ center: fromLonLat([lonNum, latNum]), duration: 300 });
            }
        } else {
            setText('selected_position', 'Sin coordenadas recientes');
            setText('selected_sitedist', '—');
        }

        const encodedCallsign = encodeURIComponent(callsign);
        const encodedIcao = encodeURIComponent(String(selected.icao || ''));

        const fa = document.getElementById('selected_flightaware_link');
        const fr = document.getElementById('selected_fr24_link');
        const fs = document.getElementById('selected_flightstats_link');
        const pf = document.getElementById('selected_planefinder_link');

        if (fa) fa.href = `https://www.flightaware.com/live/flight/${encodedCallsign}`;
        if (fr) fr.href = `https://www.flightradar24.com/${encodedCallsign}`;
        if (fs) fs.href = `https://www.flightstats.com/v2/flight-tracker/search?query=${encodedCallsign}`;
        if (pf) pf.href = `https://planefinder.net/flight/${encodedIcao}`;
    }

    function selectPlane(icao) {
        state.selectedIcao = icao;
        renderMapFeatures();
        renderTableRows();
        updateSelectedPanel();
    }

    function renderMapFeatures() {
        planeSource.clear();
        trailSource.clear();

        let withPositionCount = 0;
        let trailPointsTotal = 0;

        state.planes.forEach((plane) => {
            if (plane.lat !== null && plane.lat !== undefined && plane.lon !== null && plane.lon !== undefined) {
                withPositionCount += 1;
                const feature = new Feature({
                    geometry: new Point(fromLonLat([Number(plane.lon), Number(plane.lat)])),
                    icao: plane.icao,
                });
                feature.setStyle(createPlaneStyle(plane, plane.icao === state.selectedIcao));
                planeSource.addFeature(feature);
            }

            if (plane.icao === state.selectedIcao && Array.isArray(plane.trail) && plane.trail.length > 1) {
                const coords = plane.trail
                    .filter((pair) => Array.isArray(pair) && pair.length >= 2)
                    .map((pair) => fromLonLat([Number(pair[0]), Number(pair[1])]));

                if (coords.length > 1) {
                    trailPointsTotal += coords.length;
                    trailSource.addFeature(new Feature({
                        geometry: new LineString(coords),
                    }));
                }
            }
        });

        setText('dump1090_total_ac', String(state.planes.length));
        setText('dump1090_total_ac_positions', String(withPositionCount));
        setText('dump1090_total_history', String(trailPointsTotal));
    }

    function getSortValue(plane, key) {
        switch (key) {
            case 'icao':
                return String(plane.icao || '');
            case 'flight':
                return String(plane.flight || '');
            case 'squawk':
                return String(plane.squawk || '');
            case 'altitude':
                return Number(plane.altitude ?? -1);
            case 'speed':
                return Number(plane.speed ?? -1);
            case 'distance': {
                if (plane.lat == null || plane.lon == null) return 999999;
                return haversineKm(state.receiver.lat, state.receiver.lon, Number(plane.lat), Number(plane.lon));
            }
            case 'track':
                return Number(plane.track ?? -1);
            case 'msgs':
                return Number(plane.messages ?? -1);
            case 'seen':
            default:
                return Number(plane.seen ?? 999999);
        }
    }

    function renderTableRows() {
        const tbody = document.getElementById('radar-planes-tbody');
        if (!tbody) {
            return;
        }

        tbody.textContent = '';

        const sorted = [...state.planes].sort((a, b) => {
            const va = getSortValue(a, state.sortKey);
            const vb = getSortValue(b, state.sortKey);

            if (va < vb) return state.sortAsc ? -1 : 1;
            if (va > vb) return state.sortAsc ? 1 : -1;
            return 0;
        });

        if (sorted.length === 0) {
            const emptyRow = document.createElement('tr');
            const td = document.createElement('td');
            td.colSpan = 9;
            td.className = 'px-3 py-4 text-center text-on-surface-variant text-xs';
            td.textContent = 'Sin aeronaves activas en los últimos minutos.';
            emptyRow.appendChild(td);
            tbody.appendChild(emptyRow);
            return;
        }

        sorted.forEach((plane) => {
            const tr = document.createElement('tr');
            const isSelected = plane.icao === state.selectedIcao;
            tr.className = isSelected
                ? 'bg-primary/10 border-b border-outline-variant/25 cursor-pointer'
                : 'bg-surface-container-lowest hover:bg-surface-container border-b border-outline-variant/20 cursor-pointer';

            tr.addEventListener('click', () => selectPlane(plane.icao));

            const distNm = (plane.lat != null && plane.lon != null)
                ? (haversineKm(state.receiver.lat, state.receiver.lon, Number(plane.lat), Number(plane.lon)) * NM_PER_KM).toFixed(1)
                : '—';

            tr.appendChild(buildTextCell(plane.icao, 'font-mono font-semibold'));
            tr.appendChild(buildTextCell(plane.flight ? String(plane.flight).trim() : '—', 'font-bold'));
            tr.appendChild(buildTextCell(plane.squawk));
            tr.appendChild(buildTextCell(plane.altitude != null ? Math.round(Number(plane.altitude) * 3.28084) : null));
            tr.appendChild(buildTextCell(plane.speed != null ? Math.round(Number(plane.speed) * 1.94384) : null));
            tr.appendChild(buildTextCell(distNm));
            tr.appendChild(buildTextCell(plane.track != null ? `${plane.track}°` : null));
            tr.appendChild(buildTextCell(plane.messages));
            tr.appendChild(buildTextCell(plane.seen));

            tbody.appendChild(tr);
        });
    }

    async function fetchAircrafts() {
        if (!aircraftsUrl) {
            return;
        }

        toggleHidden('loader', false);

        try {
            const response = await fetch(aircraftsUrl, { headers: { Accept: 'application/json' } });
            if (!response.ok) {
                throw new Error(`HTTP ${response.status}`);
            }

            const payload = await response.json();
            state.planes = Array.isArray(payload.data) ? payload.data : [];

            const hasSpecialSquawk = state.planes.some((p) => ['7500', '7600', '7700'].includes(String(p.squawk || '')));
            toggleHidden('SpecialSquawkWarning', !hasSpecialSquawk);
            toggleHidden('update_error', true);

            renderMapFeatures();
            renderTableRows();
            updateSelectedPanel();
        } catch (error) {
            setText('update_error_detail', error instanceof Error ? error.message : 'Error de red');
            toggleHidden('update_error', false);
        } finally {
            toggleHidden('loader', true);
        }
    }

    async function loadReceiverConfig() {
        if (!receiverUrl) {
            return;
        }

        try {
            const response = await fetch(receiverUrl, { headers: { Accept: 'application/json' } });
            if (!response.ok) {
                return;
            }
            const payload = await response.json();
            const cfg = payload?.data;

            if (cfg && typeof cfg.lat === 'number' && typeof cfg.lon === 'number') {
                state.receiver.lat = cfg.lat;
                state.receiver.lon = cfg.lon;
                state.receiver.refresh = typeof cfg.refresh === 'number' && cfg.refresh >= 2000 ? cfg.refresh : 5000;
                setReceiverMarker(state.receiver.lat, state.receiver.lon);
                view.setCenter(fromLonLat([state.receiver.lon, state.receiver.lat]));
            }
        } catch {
            // Usar coordenadas por defecto de Chipiona si falla receiverUrl
        }
    }

    map.on('singleclick', (evt) => {
        let hitIcao = null;
        map.forEachFeatureAtPixel(evt.pixel, (feature) => {
            const icao = feature.get('icao');
            if (icao) {
                hitIcao = icao;
                return true;
            }
            return false;
        });
        if (hitIcao) {
            selectPlane(hitIcao);
        }
    });

    document.querySelectorAll('.btn-reset-map').forEach((btn) => {
        btn.addEventListener('click', () => {
            state.followSelected = false;
            view.animate({
                center: fromLonLat([state.receiver.lon, state.receiver.lat]),
                zoom: 9,
                duration: 250,
            });
        });
    });

    document.querySelectorAll('.btn-reload-map').forEach((btn) => {
        btn.addEventListener('click', () => {
            fetchAircrafts();
        });
    });

    document.querySelectorAll('.btn-downto-map').forEach((btn) => {
        btn.addEventListener('click', () => {
            document.getElementById('planes_table')?.scrollIntoView({ behavior: 'smooth', block: 'start' });
        });
    });

    document.querySelectorAll('.btn-upto-map').forEach((btn) => {
        btn.addEventListener('click', () => {
            box.scrollIntoView({ behavior: 'smooth', block: 'start' });
        });
    });

    document.querySelectorAll('.map-selected-callsign, .map-selected-follow').forEach((el) => {
        el.addEventListener('click', () => {
            state.followSelected = !state.followSelected;
            updateSelectedPanel();
        });
    });

    document.querySelectorAll('#tableinfo thead th[data-sort-key]').forEach((th) => {
        th.addEventListener('click', () => {
            const key = th.dataset.sortKey || 'seen';
            if (state.sortKey === key) {
                state.sortAsc = !state.sortAsc;
            } else {
                state.sortKey = key;
                state.sortAsc = true;
            }
            renderTableRows();
        });
    });

    loadReceiverConfig().then(() => {
        fetchAircrafts();
        state.timerId = setInterval(fetchAircrafts, state.receiver.refresh);
    });
}

document.addEventListener('DOMContentLoaded', initAirflightRadar);

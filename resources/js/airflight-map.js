import 'ol/ol.css';
import Feature from 'ol/Feature';
import OlMap from 'ol/Map';
import View from 'ol/View';
import LineString from 'ol/geom/LineString';
import Point from 'ol/geom/Point';
import TileLayer from 'ol/layer/Tile';
import VectorLayer from 'ol/layer/Vector';
import { fromLonLat } from 'ol/proj';
import OSM from 'ol/source/OSM';
import VectorSource from 'ol/source/Vector';
import { Circle as CircleStyle, Fill, Icon, Stroke, Style, Text } from 'ol/style';

const EARTH_RADIUS_KM = 6371;
const NM_PER_KM = 0.539957;

// Siluetas SVG de aeronaves (procedentes de los marcadores originales ADS-B / dump1090).
const GENERIC_PLANE_PATH = 'M 1.9565564,41.694305 C 1.7174505,40.497708 1.6419973,38.448747 1.8096508,37.70494 1.8936398,37.332056 2.0796653,36.88191 2.222907,36.70461 2.4497603,36.423844 4.087816,35.47248 14.917931,29.331528 l 12.434577,-7.050718 -0.04295,-7.613412 c -0.03657,-6.4844888 -0.01164,-7.7625804 0.168134,-8.6194061 0.276129,-1.3160905 0.762276,-2.5869575 1.347875,-3.5235502 l 0.472298,-0.7553719 1.083746,-0.6085497 c 1.194146,-0.67053522 1.399524,-0.71738842 2.146113,-0.48960552 1.077005,0.3285939 2.06344,1.41299352 2.797602,3.07543322 0.462378,1.0469993 0.978731,2.7738408 1.047635,3.5036272 0.02421,0.2570284 0.06357,3.78334 0.08732,7.836246 0.02375,4.052905 0.0658,7.409251 0.09345,7.458546 0.02764,0.04929 5.600384,3.561772 12.38386,7.805502 l 12.333598,7.715871 0.537584,0.959688 c 0.626485,1.118378 0.651686,1.311286 0.459287,3.516442 -0.175469,2.011604 -0.608966,2.863924 -1.590344,3.127136 -0.748529,0.200763 -1.293144,0.03637 -10.184829,-3.07436 C 48.007733,41.72562 44.793806,40.60197 43.35084,40.098045 l -2.623567,-0.916227 -1.981212,-0.06614 c -1.089663,-0.03638 -1.985079,-0.05089 -1.989804,-0.03225 -0.0052,0.01863 -0.02396,2.421278 -0.04267,5.339183 -0.0395,6.147742 -0.143635,7.215456 -0.862956,8.845475 l -0.300457,0.680872 2.91906,1.361455 c 2.929379,1.366269 3.714195,1.835385 4.04589,2.41841 0.368292,0.647353 0.594634,2.901439 0.395779,3.941627 -0.0705,0.368571 -0.106308,0.404853 -0.765159,0.773916 L 41.4545,62.83158 39.259237,62.80426 c -6.030106,-0.07507 -16.19508,-0.495041 -16.870991,-0.697033 -0.359409,-0.107405 -0.523792,-0.227482 -0.741884,-0.541926 -0.250591,-0.361297 -0.28386,-0.522402 -0.315075,-1.52589 -0.06327,-2.03378 0.23288,-3.033615 1.077963,-3.639283 0.307525,-0.2204 4.818478,-2.133627 6.017853,-2.552345 0.247872,-0.08654 0.247455,-0.102501 -0.01855,-0.711959 -0.330395,-0.756986 -0.708622,-2.221756 -0.832676,-3.224748 -0.05031,-0.406952 -0.133825,-3.078805 -0.185533,-5.937448 -0.0517,-2.858644 -0.145909,-5.208974 -0.209316,-5.222958 -0.06341,-0.01399 -0.974464,-0.0493 -2.024551,-0.07845 L 23.247235,38.61921 18.831373,39.8906 C 4.9432155,43.88916 4.2929558,44.057819 3.4954426,43.86823 2.7487826,43.690732 2.2007966,42.916622 1.9565564,41.694305 z';

const AIRCRAFT_SHAPES = {
    a320: {
        scale: 0.52,
        path: 'm 32,1 2,1 2,3 0,18 4,1 0,-4 3,0 0,5 17,6 0,3 -15,-2 -9,0 0,12 -2,6 7,3 0,2 -8,-1 -1,2 -1,-2 -8,1 0,-2 7,-3 -2,-6 0,-12 -9,0 -15,2 0,-3 17,-6 0,-5 3,0 0,4 4,-1 0,-18 2,-3 2,-1z',
    },
    b738: {
        scale: 0.54,
        path: 'm 32,61 -1,-1 -9,2 -2,1 0,-2 9,-6 1,-1 -1,-9 0,-11 -7,0 -1,1 0,-1 -3,1 -1,1 0,-1 -3,1 -9,3 -1,1 0,-2 1,-2 17,-9 1,-1 -1,-2 0,-3 1,-1 2,0 1,1 0,3 3,-2 0,-13 1,-5 1,-3 1,-1 1,1 1,3 1,5 0,13 3,2 0,-3 1,-1 2,0 1,1 0,3 -1,2 1,1 17,9 1,2 0,2 -1,-1 -9,-3 -3,-1 0,1 -1,-1 -3,-1 0,1 -1,-1 -7,0 0,11 -1,9 1,1 9,6 0,2 -2,-1 -9,-2 -1,1 z',
    },
    b777: {
        scale: 0.64,
        path: 'm 32,1 2,1 1,2 0,20 4,4 0,-4 3,0 0,4 -1,2 17,12 0,2 -16,-5 -7,0 0,13 -1,5 7,5 0,2 -8,-2 -1,2 -1,-2 -8,2 0,-2 7,-5 -1,-5 0,-13 -7,0 -16,5 0,-2 17,-12 -1,-2 0,-4 3,0 0,4 4,-4 0,-20 1,-2 2,-1z',
    },
    a380: {
        scale: 0.68,
        path: 'm 32,59 -1,-4 -4,1 -7,3 -1,0 1,-3 1,-1 7,-6 2,-2 -1,-5 0,-9 -1,-2 -2,0 -6,2 -5,2 -5,2 -9,4 0,1 0,-3 1,-2 9,-7 -1,-1 0,-4 1,-1 1,0 1,1 0,3 1,0 5,-4 0,-5 1,-1 1,0 1,1 0,3 6,-5 1,-2 0,-7 1,-5 1,-2 1,-1 1,1 1,2 1,5 0,7 1,2 6,5 0,-3 1,-1 1,0 1,1 0,5 5,4 1,0 0,-3 1,-1 1,0 1,1 0,4 -1,1 9,7 1,2 0,3 0,-1 -9,-4 -5,-2 -5,-2 -6,-2 -2,0 -1,2 0,9 -1,5 2,2 7,6 1,1 1,3 -1,0 -7,-3 -4,-1 -1,4 z',
    },
    heavy: {
        scale: 0.62,
        path: 'm28.64874,12.035023l0,8.801421l-4.585627,3.066495c0.126825,-0.257055 0.094102,-0.531839 0.095802,-0.802796l-0.015437,-3.087446l-2.230453,-0.012673l0.019009,3.599141c0.000513,0.577993 0.076338,0.923195 0.589296,1.241956l-5.533809,3.630512c0.166511,-0.256275 0.153699,-0.551367 0.153699,-0.841892l-0.005929,-3.270195l-2.160751,-0.012672l-0.006337,3.637159c0.016349,0.5301 0.096662,1.090947 0.576623,1.419379l-11.976014,7.825597c-2.106287,1.48859 -1.705322,3.044253 -1.56512,4.587637l26.645047,-9.048544l0,13.750239l0.722364,5.062875l-8.681027,6.387208c-1.239945,1.059417 -1.080616,2.171837 -0.842757,3.256969l11.278998,-2.946479c0.130159,3.116897 1.559821,3.171571 1.780561,0.006336l11.278998,2.94648c0.23786,-1.085133 0.397189,-2.197552 -0.842756,-3.256969l-8.681026,-6.387207l0.722362,-5.062875l0,-13.750239l26.645048,9.042207c0.140203,-1.543381 0.541167,-3.092711 -1.56512,-4.581301l-11.976015,-7.825597c0.47996,-0.328434 0.553938,-0.889279 0.570286,-1.419379l0,-3.63716l-2.160751,0.012673l-0.005378,3.328244c-0.002334,0.294243 0.007077,0.545056 0.178191,0.817583l-5.565189,-3.664251c0.512962,-0.318761 0.59512,-0.663963 0.595633,-1.241956l0.019009,-3.599141l-2.230454,0.012673l-0.015793,3.100403c0.001462,0.282341 -0.019949,0.535579 0.124839,0.794638l-4.614307,-3.071294l0,-8.801421c-1.111672,-11.152869 -5.489391,-11.217579 -6.735717,-0.006336z',
    },
    prop: {
        scale: 0.48,
        path: 'm 32,1 3,4 0,20 4,0 0,-5 1,-1 1,1 0,5 17,2 0,3 -17,2 0,3 -1,1 -1,-1 0,-3 -4,0 0,15 -1,8 6,0 1,1 0,3 -8,0 -1,1 -1,-1 -8,0 0,-3 1,-1 6,0 -1,-8 0,-15 -4,0 0,3, -1,1 -1,-1 0,-3 -17,-2 0,-3 17,-2 0,-5 1,-1 1,1 0,5 4,0 0,-20 3,-4z',
    },
    rotorcraft: {
        scale: 0.5,
        path: 'M 43.89309,0.4301 c -0.60546,-0.60546 -1.62623,-0.56506 -2.2813,0.0897 L 25.82444,16.3061 C 24.95171,-1.27473 21.64491,1.24212 21.64491,1.24212 c 0,0 -3.20153,-2.80873 -4.13518,14.07519 L 2.71103,0.51862 C 2.05636,-0.13606 1.03533,-0.17646 0.43,0.42902 c -0.60546,0.6052 -0.56506,1.6261 0.0896,2.28104 l 16.81957,16.81931 c -0.0454,1.63425 -0.072,3.41089 -0.0796,5.34281 l -0.90497,0.90496 h -1.94113 v 1.94113 L 0.51882,41.61319 c -0.6548,0.65454 -0.69533,1.67531 -0.09,2.28077 0.60533,0.60546 1.62636,0.5648 2.28104,-0.0896 L 14.41335,32.10074 v 1.94073 h 3.09928 c 0,0 1.25961,6.97312 2.03417,8.65159 0.77495,1.67913 0.032,17.17487 2.09799,17.17487 0.38346,0 0.66928,-0.53374 0.88615,-1.41331 l 6.34515,-2.71897 v -1.03314 h -5.85155 c 0.34017,-4.67077 0.24161,-10.97316 0.71942,-12.00945 0.77416,-1.67847 2.03285,-8.65159 2.03285,-8.65159 h 3.09928 v -2.974 l 12.73545,12.73689 c 0.65507,0.65442 1.67584,0.69495 2.2813,0.0896 0.60546,-0.60533 0.56479,-1.62623 -0.0901,-2.28077 L 28.876,26.68527 v -0.90813 h -0.90799 l -1.94284,-1.9431 c -0.009,-1.15407 -0.0263,-2.25524 -0.0496,-3.29693 l 17.82849,-17.8281 c 0.65493,-0.65494 0.6956,-1.67597 0.0901,-2.28144 z',
        size: [44, 64],
    },
    generic: {
        scale: 0.48,
        path: GENERIC_PLANE_PATH,
    },
};

const iconUriCache = new Map();

function resolvePlaneShape(plane) {
    const type = String(plane.aircraft_type || '').toUpperCase();
    const category = String(plane.category || '').toUpperCase();

    if (/^A31[89]|^A32[01]|^A20N|^A21N/.test(type)) return AIRCRAFT_SHAPES.a320;
    if (/^B73[0-9]|^B38M|^B39M/.test(type)) return AIRCRAFT_SHAPES.b738;
    if (/^B77|^B78|^A33|^A35/.test(type)) return AIRCRAFT_SHAPES.b777;
    if (/^A38|^B74/.test(type)) return AIRCRAFT_SHAPES.a380;
    if (/^DH8|^AT4|^AT7|^BE20|^C130/.test(type)) return AIRCRAFT_SHAPES.prop;
    if (category === 'A5') return AIRCRAFT_SHAPES.heavy;
    if (category === 'A7') return AIRCRAFT_SHAPES.rotorcraft;

    return AIRCRAFT_SHAPES.generic;
}

function resolveAltitudeColor(altitudeMeters) {
    if (altitudeMeters === null || altitudeMeters === undefined) {
        return '#38bdf8';
    }
    const feet = Number(altitudeMeters) * 3.28084;
    if (feet <= 1000) return '#f59e0b';
    if (feet <= 6000) return '#22c55e';
    if (feet <= 16000) return '#06b6d4';
    if (feet <= 28000) return '#2563eb';
    return '#7c3aed';
}

function getPlaneIconDataUri(shape, fillColor, strokeColor) {
    const cacheKey = `${shape.path.length}:${fillColor}:${strokeColor}`;
    if (iconUriCache.has(cacheKey)) {
        return iconUriCache.get(cacheKey);
    }

    const [w, h] = shape.size || [64, 64];
    const svg = `<svg xmlns="http://www.w3.org/2000/svg" width="${w}" height="${h}" viewBox="0 0 ${w} ${h}"><path d="${shape.path}" fill="${fillColor}" stroke="${strokeColor}" stroke-width="2.2" stroke-linejoin="round"/></svg>`;
    const uri = `data:image/svg+xml;charset=utf-8,${encodeURIComponent(svg)}`;
    iconUriCache.set(cacheKey, uri);

    return uri;
}

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
        : (isSelected ? '#f59e0b' : resolveAltitudeColor(plane.altitude));
    const strokeColor = isSelected ? '#0f172a' : '#111827';
    const shape = resolvePlaneShape(plane);

    return new Style({
        image: new Icon({
            src: getPlaneIconDataUri(shape, fillColor, strokeColor),
            scale: shape.scale * (isSelected ? 1.25 : 1),
            rotation,
            rotateWithView: true,
            anchor: [0.5, 0.5],
        }),
        text: new Text({
            text: label,
            offsetY: -22,
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

    const map = new OlMap({
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

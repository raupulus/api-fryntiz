<x-filament-panels::page>
    <div id="admin-airflight-map" class="h-[70vh] w-full rounded-lg border border-gray-200 dark:border-white/10"></div>

    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"/>
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const planes = @json($planes);
            const map = L.map('admin-airflight-map').setView([36.7417, -6.4376], 9);
            L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '&copy; OpenStreetMap'
            }).addTo(map);

            planes.forEach(p => {
                if (p.lat && p.lon) {
                    L.marker([p.lat, p.lon]).addTo(map)
                        .bindPopup(`<b>${p.icao ?? '?'}</b><br>${p.flight ?? ''}<br>${p.altitude ?? '?'} m`);
                }
            });
        });
    </script>
</x-filament-panels::page>

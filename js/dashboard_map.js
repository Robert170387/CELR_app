document.addEventListener('DOMContentLoaded', function () {
    // Initialize Map centered on Bogota
    // Using OpenStreetMap provider via Leaflet (must be included in index.php)

    console.log('Dashboard Map: Initializing...');

    if (!document.getElementById('gps-map')) {
        console.warn('Dashboard Map: Element #gps-map not found');
        return;
    }

    // Check if Leaflet is loaded
    if (typeof L === 'undefined') {
        console.error('Dashboard Map: Leaflet (L) is not loaded!');
        return;
    }

    console.log('Dashboard Map: Leaflet loaded, creating map...');

    // Small delay to ensure container is fully rendered
    setTimeout(function () {
        try {
            console.log('Dashboard Map: Creating map instance...');
            var map = L.map('gps-map').setView([4.7110, -74.0721], 11);

            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors',
                maxZoom: 19
            }).addTo(map);

            // Force recalculation of map size
            setTimeout(() => {
                map.invalidateSize();
                console.log('Dashboard Map: Size refreshed');
            }, 500);

            console.log('Dashboard Map: Map created successfully');

            // Start loading locations
            updateLocations(map);

            // Refresh every 60 seconds
            setInterval(() => updateLocations(map), 60000);

        } catch (error) {
            console.error('Dashboard Map: Error creating map:', error);
        }
    }, 300);

    function updateLocations(mapInstance) {
        if (!mapInstance) return;
        fetch('api.php?action=fetchSatrackLocations')
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Simple hack: clear all layers except tile layer
                    mapInstance.eachLayer(function (layer) {
                        if (!layer._url) { // Identify markers vs tile layer
                            mapInstance.removeLayer(layer);
                        }
                    });

                    data.locations.forEach(v => {
                        var color = v.status === 'En Movimiento' ? '#10b981' : '#ef4444';
                        var markerHtml = `<div style="background-color: ${color}; width: 14px; height: 14px; border-radius: 50%; border: 3px solid white; box-shadow: 0 0 10px rgba(0,0,0,0.2); animation: pulse 2s infinite;"></div>`;
                        var icon = L.divIcon({
                            className: 'custom-div-icon',
                            html: markerHtml,
                            iconSize: [16, 16],
                            iconAnchor: [8, 8]
                        });

                        L.marker([v.lat, v.lng], { icon: icon }).addTo(mapInstance)
                            .bindPopup(`
                                <div class="p-1">
                                    <p class="font-black text-brand-700 text-sm mb-1">${v.placa}</p>
                                    <p class="text-[10px] text-gray-500 font-bold uppercase tracking-tight">${v.details}</p>
                                    <hr class="my-2 border-gray-100">
                                    <p class="text-[11px] font-medium"><span class="text-gray-400">Estado:</span> <span class="font-bold" style="color:${color}">${v.status}</span></p>
                                    <p class="text-[11px] font-medium"><span class="text-gray-400">Velocidad:</span> <span class="font-bold text-gray-800">${v.speed}</span></p>
                                    <p class="text-[11px] font-medium mt-1"><span class="text-gray-400 italic">Actualizado: ${v.last_update}</span></p>
                                </div>
                            `);
                    });
                }
            })
            .catch(error => console.error('Error fetching SATRACK locations:', error));
    }
});

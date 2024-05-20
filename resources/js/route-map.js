import L from 'leaflet';

export class RouteMap {
    constructor(element, options = {}) {
        this.element = element;
        this.stops = options.stops ?? [];
        this.geometryUrl = options.geometryUrl ?? null;
        this.drivers = options.drivers ?? [];
        this.map = null;
        this.layers = { route: null, driverMarkers: {} };
    }

    async init() {
        this.map = L.map(this.element).setView([-1.95, 30.07], 12);

        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '&copy; OpenStreetMap contributors',
            maxZoom: 19,
        }).addTo(this.map);

        await this.drawRoute();
        this.drawStops();
        this.drawDrivers();

        if (this.stops.length) {
            const bounds = L.latLngBounds(this.stops.map((s) => [s.latitude, s.longitude]));
            this.map.fitBounds(bounds, { padding: [30, 30] });
        }
    }

    async drawRoute() {
        let coordinates = null;

        if (this.geometryUrl) {
            try {
                const response = await fetch(this.geometryUrl);
                const json = await response.json();
                coordinates = json.data?.geometry;
            } catch (e) {
                console.warn('Could not load route geometry', e);
            }
        }

        if (!coordinates?.length) {
            coordinates = this.stops.map((s) => [s.latitude, s.longitude]);
        }

        if (coordinates.length < 2) return;

        this.layers.route = L.polyline(coordinates, {
            color: '#0d6e4f',
            weight: 5,
            opacity: 0.8,
        }).addTo(this.map);
    }

    drawStops() {
        this.stops.forEach((stop) => {
            const isPassed = stop.is_passed ?? false;
            const isNext = stop.is_next ?? false;

            const icon = L.divIcon({
                className: '',
                html: `<div class="stop-marker ${isPassed ? 'passed' : ''} ${isNext ? 'next' : ''}"></div>`,
                iconSize: [16, 16],
                iconAnchor: [8, 8],
            });

            L.marker([stop.latitude, stop.longitude], { icon })
                .addTo(this.map)
                .bindPopup(`<strong>${stop.order}. ${stop.name}</strong>`);
        });
    }

    drawDrivers() {
        this.drivers.forEach((driver) => this.updateDriver(driver));
    }

    updateDriver(driver) {
        const location = driver.current_location;
        if (!location) return;

        const key = driver.trip_id ?? driver.driver_id ?? driver.driver?.vehicle_plate;
        const latLng = [location.latitude, location.longitude];

        const icon = L.divIcon({
            className: '',
            html: '<div class="driver-marker"></div>',
            iconSize: [18, 18],
            iconAnchor: [9, 9],
        });

        const label = driver.driver?.name ?? 'Driver';
        const plate = driver.driver?.vehicle_plate ?? '';

        if (this.layers.driverMarkers[key]) {
            this.layers.driverMarkers[key].setLatLng(latLng);
        } else {
            this.layers.driverMarkers[key] = L.marker(latLng, { icon, zIndexOffset: 1000 })
                .addTo(this.map)
                .bindPopup(`<strong>${label}</strong><br>${plate}`);
        }
    }

    updateFromProgress(progress) {
        this.updateDriver(progress);

        progress.stops?.forEach((stop) => {
            const existing = this.stops.find((s) => s.id === stop.id);
            if (existing) {
                existing.is_passed = stop.is_passed;
                existing.is_next = stop.is_next;
            }
        });
    }
}

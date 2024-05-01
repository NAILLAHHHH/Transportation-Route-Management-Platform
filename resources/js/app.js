import './echo';
import { RouteMap } from './route-map';

window.RouteMap = RouteMap;

document.addEventListener('DOMContentLoaded', () => {
    const mapEl = document.getElementById('route-map');
    if (!mapEl) return;

    const stops = JSON.parse(mapEl.dataset.stops || '[]');
    const drivers = JSON.parse(mapEl.dataset.drivers || '[]');
    const geometryUrl = mapEl.dataset.geometryUrl || null;

    const routeMap = new RouteMap(mapEl, { stops, drivers, geometryUrl });
    routeMap.init();
    window.routeMap = routeMap;

    if (window.Echo && mapEl.dataset.live === 'true') {
        window.Echo.channel('trips').listen('.TripProgressUpdated', (event) => {
            if (event.progress) {
                routeMap.updateFromProgress(event.progress);
                document.dispatchEvent(new CustomEvent('trip-progress-updated', {
                    detail: event.progress,
                }));
            }
        });

        const tripId = mapEl.dataset.tripId;
        if (tripId) {
            window.Echo.channel(`trip.${tripId}`).listen('.TripProgressUpdated', (event) => {
                if (event.progress) {
                    routeMap.updateFromProgress(event.progress);
                    document.dispatchEvent(new CustomEvent('trip-progress-updated', {
                        detail: event.progress,
                    }));
                }
            });
        }
    }
});

# Transportation Route Management Platform

A Laravel application for coordinating daily minibus routes in Kigali, Rwanda — from **Nyabugogo Bus Park** to **Kimironko Market**, with passenger pickups at five stops along the way.

## Features

- **Route management** — Pre-configured Nyabugogo → Kimironko route with 7 stops
- **Authentication** — Role-based login (driver, passenger, admin)
- **Interactive maps** — Leaflet + OpenStreetMap with route polyline and live driver markers
- **Road routing ETAs** — OSRM (default) or OpenRouteService for real driving distance & duration
- **Real-time WebSockets** — Laravel Reverb + Echo; instant updates when drivers move
- **Driver dashboard** — Start trips, update GPS, mark arrivals at each stop
- **Passenger coordination** — Join a waitlist and view live driver ETAs
- **REST API** — JSON endpoints for mobile apps or integrations

## Route Stops

| # | Stop |
|---|------|
| 1 | Nyabugogo Bus Park (origin) |
| 2 | Nyamirambo |
| 3 | Kigali City Center |
| 4 | Remera |
| 5 | Kacyiru |
| 6 | Gisimenti |
| 7 | Kimironko Market (destination) |

## Requirements

- PHP 8.2+
- Composer
- Node.js 18+ and npm
- SQLite (default) or MySQL

## Setup

```bash
# Install PHP dependencies
composer install

# Install frontend dependencies
npm install

# Configure environment
cp .env.example .env
php artisan key:generate

# Create SQLite database
touch database/database.sqlite

# Run migrations and seed sample data
php artisan migrate --seed

# Build frontend assets
npm run build
```

## Running the app (3 terminals)

**Terminal 1 — Web server**
```bash
php artisan serve
```

**Terminal 2 — WebSocket server (Reverb)**
```bash
php artisan reverb:start
```

**Terminal 3 — Frontend dev (optional, during development)**
```bash
npm run dev
```

Visit [http://localhost:8000](http://localhost:8000)

> Set `BROADCAST_CONNECTION=reverb` in `.env` for live WebSocket updates.

## Demo Accounts

| Role | Email | Password |
|------|-------|----------|
| Admin | admin@kigaliroutes.rw | password |
| Driver | jean@kigaliroutes.rw | password |
| Passenger | passenger@kigaliroutes.rw | password |

## How the new features work

### Authentication
- `/login` — session-based login with role redirect
- Drivers → driver dashboard; Passengers → passenger view; Admin → full access
- Driver routes are protected; drivers can only manage their own vehicle

### Maps (Leaflet)
- Route polyline fetched from OSRM via `/api/routes/{id}/geometry`
- Stop markers and live driver position on driver & passenger pages

### Road routing
- `RoutingService` calls OSRM for driving distance/duration (cached 60s)
- Optional `ORS_API_KEY` in `.env` for OpenRouteService
- Falls back to Haversine if routing API is unavailable

### WebSockets
- `TripProgressUpdated` event broadcasts on location update, stop advance, trip start
- Passengers and drivers see instant ETA/map updates via Laravel Echo

## API Endpoints

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/routes` | List active routes |
| GET | `/api/routes/{id}` | Route details with stops |
| GET | `/api/routes/{id}/geometry` | Road polyline for map |
| GET | `/api/trips/active` | All in-progress trips with ETAs |
| GET | `/api/trips/{id}` | Single trip progress |
| POST | `/api/trips/start` | Start a trip (`driver_id`) |
| POST | `/api/trips/{id}/location` | Update driver GPS |
| POST | `/api/trips/{id}/advance` | Mark arrival at next stop |

## Project Structure

```
app/
├── Events/TripProgressUpdated.php
├── Http/Controllers/Auth/LoginController.php
├── Http/Middleware/EnsureUserHasRole.php
└── Services/
    ├── GeoService.php           # Haversine fallback
    ├── RoutingService.php       # OSRM / OpenRouteService
    ├── TripEtaService.php       # ETA to stops
    ├── TripBroadcastService.php # WebSocket broadcasts
    └── TripService.php          # Trip lifecycle
resources/js/
├── app.js                       # Echo + map init
├── echo.js                      # Laravel Echo / Reverb
└── route-map.js                 # Leaflet map component
```

## License

MIT

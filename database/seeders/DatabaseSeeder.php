<?php

namespace Database\Seeders;

use App\Models\Driver;
use App\Models\Stop;
use App\Models\TransportRoute;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::create([
            'name' => 'Platform Admin',
            'email' => 'admin@kigaliroutes.rw',
            'phone' => '+250788000001',
            'role' => 'admin',
            'password' => Hash::make('password'),
        ]);

        $driverUsers = [
            ['name' => 'Jean Claude', 'email' => 'jean@kigaliroutes.rw', 'plate' => 'RAB 123A'],
            ['name' => 'Emmanuel', 'email' => 'emmanuel@kigaliroutes.rw', 'plate' => 'RAB 456B'],
            ['name' => 'Patrick', 'email' => 'patrick@kigaliroutes.rw', 'plate' => 'RAB 789C'],
        ];

        foreach ($driverUsers as $driverData) {
            $user = User::create([
                'name' => $driverData['name'],
                'email' => $driverData['email'],
                'phone' => '+250788'.random_int(100000, 999999),
                'role' => 'driver',
                'password' => Hash::make('password'),
            ]);

            Driver::create([
                'user_id' => $user->id,
                'vehicle_plate' => $driverData['plate'],
                'vehicle_type' => 'minibus',
                'capacity' => 14,
                'is_available' => true,
            ]);
        }

        User::create([
            'name' => 'Marie Uwase',
            'email' => 'passenger@kigaliroutes.rw',
            'phone' => '+250788000099',
            'role' => 'passenger',
            'password' => Hash::make('password'),
        ]);

        $route = TransportRoute::create([
            'name' => 'Nyabugogo – Kimironko',
            'origin' => 'Nyabugogo Bus Park',
            'destination' => 'Kimironko Market',
            'description' => 'Daily minibus route connecting Nyabugogo to Kimironko via five passenger pickup stops across Kigali.',
            'is_active' => true,
        ]);

        $stops = [
            ['name' => 'Nyabugogo Bus Park', 'order' => 1, 'lat' => -1.9395, 'lng' => 30.0588],
            ['name' => 'Nyamirambo', 'order' => 2, 'lat' => -1.9736, 'lng' => 30.0431],
            ['name' => 'Kigali City Center', 'order' => 3, 'lat' => -1.9500, 'lng' => 30.0589],
            ['name' => 'Remera', 'order' => 4, 'lat' => -1.9597, 'lng' => 30.1045],
            ['name' => 'Kacyiru', 'order' => 5, 'lat' => -1.9361, 'lng' => 30.0822],
            ['name' => 'Gisimenti', 'order' => 6, 'lat' => -1.9285, 'lng' => 30.0950],
            ['name' => 'Kimironko Market', 'order' => 7, 'lat' => -1.9260, 'lng' => 30.1065],
        ];

        foreach ($stops as $stop) {
            Stop::create([
                'transport_route_id' => $route->id,
                'name' => $stop['name'],
                'order' => $stop['order'],
                'latitude' => $stop['lat'],
                'longitude' => $stop['lng'],
                'is_pickup_point' => true,
            ]);
        }
    }
}

<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\TransportRoute;
use App\Services\RoutingService;
use Illuminate\Http\JsonResponse;

class RouteApiController extends Controller
{
    public function show(TransportRoute $route): JsonResponse
    {
        $route->load('stops');

        return response()->json(['data' => $route]);
    }

    public function index(): JsonResponse
    {
        $routes = TransportRoute::with('stops')->where('is_active', true)->get();

        return response()->json(['data' => $routes]);
    }

    public function geometry(TransportRoute $route, RoutingService $routing): JsonResponse
    {
        $route->load('stops');

        return response()->json([
            'data' => [
                'route_id' => $route->id,
                'stops' => $route->stops,
                'geometry' => $routing->getRouteGeometry($route->stops),
            ],
        ]);
    }
}

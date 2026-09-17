<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Support\DeliveryTracking;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;

class DeliveryMapController extends Controller
{
    public function __construct(protected DeliveryTracking $tracking)
    {
    }

    public function index(): View
    {
        return view('delivery.admin.map');
    }

    /**
     * The positions every on-duty delivery person, refreshed by the map page.
     * Only admins can reach this (enforced by the route group).
     */
    public function locations(): JsonResponse
    {
        return response()->json($this->tracking->snapshot());
    }
}

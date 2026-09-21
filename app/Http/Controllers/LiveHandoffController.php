<?php

namespace App\Http\Controllers;

use App\Models\Visit;
use App\Services\UnifiedVisitService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LiveHandoffController extends Controller
{
    public function __construct(private readonly UnifiedVisitService $visitService) {}

    /**
     * GET /api/live/state
     * Real-time polling & state endpoint for all portals
     */
    public function state(): JsonResponse
    {
        return response()->json($this->visitService->getLiveState());
    }

    /**
     * POST /api/live/visits
     * Reception creates a live visit and dispatches
     */
    public function storeVisit(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'visitor' => 'required|string|max:255',
            'guest_type' => 'nullable|string',
            'company' => 'nullable|string|max:255',
            'visitor_phone' => 'nullable|string|max:50',
            'visitor_email' => 'nullable|email|max:255',
            'id_type' => 'nullable|string',
            'id_number' => 'nullable|string',
            'address' => 'nullable|string',
            
            'purpose' => 'nullable|string|max:500',
            'host' => 'nullable|string|max:255',
            'expected_duration' => 'nullable|string|max:100',
            'visitor_count' => 'nullable|integer',
            'notes' => 'nullable|string|max:1000',
            
            'requests' => 'nullable|array',
            'requests.*.department' => 'required|string',
            'requests.*.title' => 'required|string',
            'requests.*.priority' => 'nullable|string',
        ]);

        $visit = $this->visitService->createVisit($validated);

        return response()->json([
            'success' => true,
            'message' => "Live visit #{$visit->id} created and dispatched.",
            'visit' => $visit->load(['services', 'timelines']),
        ]);
    }

    /**
     * POST /api/live/visits/{id}/services
     * Forward visit to another department (e.g. Reception -> IT or Sales)
     */
    public function addService(Request $request, int $id): JsonResponse
    {
        $visit = Visit::findOrFail($id);

        $validated = $request->validate([
            'department' => 'required|string|in:IT,SALES,ACCOUNTS',
            'service_name' => 'required|string|max:255',
            'request_description' => 'nullable|string',
            'price' => 'nullable|integer|min:0',
            'quantity' => 'nullable|integer|min:1',
        ]);

        $service = $this->visitService->addServiceToVisit($visit, $validated);

        return response()->json([
            'success' => true,
            'message' => "Visit #{$visit->id} forwarded to {$service->department} successfully.",
            'service' => $service,
            'visit' => $visit->fresh(['services', 'timelines']),
        ]);
    }

    /**
     * POST /api/live/services/{id}/accept
     * IT or Sales clicks [ ACCEPT TASK ]
     */
    public function acceptService(int $id): JsonResponse
    {
        $service = $this->visitService->acceptService($id, auth()->id());

        return response()->json([
            'success' => true,
            'message' => "Task accepted. Status updated to IN PROGRESS.",
            'service' => $service,
            'visit' => $service->visit->fresh(['services', 'timelines']),
        ]);
    }

    /**
     * POST /api/live/services/{id}/complete
     * IT or Sales clicks [ COMPLETE TASK ] & enters price
     */
    public function completeService(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate([
            'price' => 'required|integer|min:0',
            'resolution_notes' => 'nullable|string|max:1000',
        ]);

        $service = $this->visitService->completeService(
            serviceId: $id,
            price: $validated['price'],
            resolutionNotes: $validated['resolution_notes'] ?? null,
            userId: auth()->id()
        );

        return response()->json([
            'success' => true,
            'message' => "Task completed. Added charge TZS " . number_format($validated['price']) . " to visit.",
            'service' => $service,
            'visit' => $service->visit->fresh(['services', 'timelines']),
        ]);
    }

    /**
     * POST /api/live/visits/{id}/checkout
     * Reception checks out customer with full summary & payment status
     */
    public function checkout(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate([
            'payment_status' => 'required|string|in:paid,pending,credit',
            'notes' => 'nullable|string|max:500',
        ]);

        $visit = $this->visitService->checkoutVisit($id, $validated);

        return response()->json([
            'success' => true,
            'message' => "Visit #{$visit->id} checked out successfully.",
            'visit' => $visit->load(['services', 'timelines']),
        ]);
    }

    /**
     * GET /api/live/visits/{id}/timeline
     * Returns full live audit trail for a visit
     */
    public function timeline(int $id): JsonResponse
    {
        $visit = Visit::with(['services', 'timelines.user'])->findOrFail($id);

        return response()->json([
            'visit' => $visit,
            'timelines' => $visit->timelines,
            'services' => $visit->services,
        ]);
    }
}

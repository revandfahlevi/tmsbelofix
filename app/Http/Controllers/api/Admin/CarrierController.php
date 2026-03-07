<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Carrier\CreateCarrierRequest;
use App\Http\Requests\Carrier\UpdateCarrierRequest;
use App\Http\Requests\Carrier\CreateVehicleRequest;
use App\Http\Requests\Carrier\CreateAssignmentRequest;
use App\Http\Requests\Carrier\UpdateAssignmentStatusRequest;
use App\Models\Carrier;
use App\Models\CarrierAssignment;
use App\Models\CarrierVehicle;
use App\Services\CarrierAssignmentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CarrierController extends Controller
{
    public function __construct(private CarrierAssignmentService $service) {}

    // ── Carriers ───────────────────────────────────────────────────

    public function index(Request $request): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data'    => $this->service->listCarriers($request->all()),
        ]);
    }

    public function store(CreateCarrierRequest $request): JsonResponse
    {
        $carrier = $this->service->createCarrier($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Carrier created.',
            'data'    => $carrier,
        ], 201);
    }

    public function show(Carrier $carrier): JsonResponse
    {
        $carrier->load(['vehicles', 'assignments' => fn($q) => $q->latest()->limit(10)]);

        return response()->json(['success' => true, 'data' => $carrier]);
    }

    public function update(UpdateCarrierRequest $request, Carrier $carrier): JsonResponse
    {
        $carrier = $this->service->updateCarrier($carrier, $request->validated());

        return response()->json(['success' => true, 'message' => 'Carrier updated.', 'data' => $carrier]);
    }

    public function destroy(Carrier $carrier): JsonResponse
    {
        if ($carrier->assignments()->whereNotIn('status', ['completed', 'cancelled'])->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete carrier with active assignments.',
            ], 422);
        }

        $carrier->delete();

        return response()->json(['success' => true, 'message' => 'Carrier deleted.']);
    }

    // ── Vehicles ───────────────────────────────────────────────────

    public function storeVehicle(CreateVehicleRequest $request, Carrier $carrier): JsonResponse
    {
        $vehicle = $this->service->addVehicle($carrier, $request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Vehicle added.',
            'data'    => $vehicle,
        ], 201);
    }

    public function updateVehicle(Request $request, Carrier $carrier, CarrierVehicle $vehicle): JsonResponse
    {
        $vehicle = $this->service->updateVehicle($vehicle, $request->all());

        return response()->json(['success' => true, 'data' => $vehicle]);
    }

    // ── Assignments ────────────────────────────────────────────────

    public function assignments(Request $request): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data'    => $this->service->listAssignments($request->all()),
        ]);
    }

    public function createAssignment(CreateAssignmentRequest $request): JsonResponse
    {
        try {
            $assignment = $this->service->createAssignment($request->validated(), $request->user());

            return response()->json([
                'success' => true,
                'message' => 'Assignment created.',
                'data'    => $assignment,
            ], 201);
        } catch (\InvalidArgumentException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    public function showAssignment(CarrierAssignment $assignment): JsonResponse
    {
        $assignment->load(['jobOrder', 'carrier', 'vehicle', 'assignedBy:id,name']);

        return response()->json(['success' => true, 'data' => $assignment]);
    }

    public function updateAssignmentStatus(UpdateAssignmentStatusRequest $request, CarrierAssignment $assignment): JsonResponse
    {
        try {
            $result = $this->service->updateStatus(
                $assignment,
                $request->validated('status'),
                $request->validated(),
                $request->user()
            );

            return response()->json(['success' => true, 'message' => 'Status updated.', 'data' => $result]);
        } catch (\InvalidArgumentException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    public function sendSpk(CarrierAssignment $assignment, Request $request): JsonResponse
    {
        try {
            $result = $this->service->sendSpk($assignment, $request->user());

            return response()->json(['success' => true, 'message' => 'SPK sent.', 'data' => $result]);
        } catch (\InvalidArgumentException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }
}
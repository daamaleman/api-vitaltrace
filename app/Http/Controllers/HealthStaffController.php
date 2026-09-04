<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\StoreHealthStaffRequest;
use App\Http\Requests\UpdateHealthStaffRequest;
use App\Http\Resources\HealthStaffResource;
use App\Http\Resources\ProfessionalRegistrationResource;
use App\Models\HealthStaff;
use App\Models\User;
use App\Services\ProfessionalRegistrationService;
use App\Exceptions\ProfessionalRegistrationConflict;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

/**
 * CRUD endpoints for health professionals (doctors and nurses).
 */
class HealthStaffController extends Controller
{
    /**
     * List paginated health staff with their person and specialty.
     */
    public function index(): AnonymousResourceCollection
    {
        $healthStaff = HealthStaff::latest('id')->paginate(15);

        return HealthStaffResource::collection($healthStaff);
    }

    /**
     * Register a new health professional.
     */
    public function store(StoreHealthStaffRequest $request, ProfessionalRegistrationService $service): JsonResponse
    {
        $user = User::query()->findOrFail($request->integer('user_id'));

        try {
            $healthStaff = $service->register($user, $request->validated(), $request->user());
        } catch (ProfessionalRegistrationConflict $exception) {
            return response()->json([
                'data' => null,
                'message' => $exception->getMessage(),
                'errors' => ['professional' => [$exception->getMessage()]],
            ], Response::HTTP_CONFLICT);
        }

        $user->load('roles');
        $healthStaff->load(['person', 'specialty']);

        return response()->json([
            'data' => new ProfessionalRegistrationResource(['user' => $user, 'health_staff' => $healthStaff]),
            'message' => 'Personal de salud registrado correctamente.',
            'errors' => null,
        ], Response::HTTP_CREATED);
    }

    /**
     * Show a single health professional.
     */
    public function show(HealthStaff $healthStaff): JsonResponse
    {
        return response()->json([
            'data' => new HealthStaffResource($healthStaff->load(['person', 'specialty'])),
            'message' => null,
            'errors' => null,
        ], Response::HTTP_OK);
    }

    /**
     * Update an existing health professional.
     */
    public function update(UpdateHealthStaffRequest $request, HealthStaff $healthStaff, ProfessionalRegistrationService $service): JsonResponse
    {
        try {
            $healthStaff = $service->update($healthStaff, $request->validated(), $request->user());
        } catch (ProfessionalRegistrationConflict $exception) {
            return response()->json([
                'data' => null,
                'message' => $exception->getMessage(),
                'errors' => ['professional' => [$exception->getMessage()]],
            ], Response::HTTP_CONFLICT);
        }

        $user = User::query()->where('person_id', $healthStaff->person_id)->first();
        $healthStaff->load(['person', 'specialty']);
        if ($user !== null) {
            $user->load('roles');
        }

        return response()->json([
            'data' => $user === null
                ? null
                : new ProfessionalRegistrationResource(['user' => $user, 'health_staff' => $healthStaff]),
            'message' => 'Personal de salud actualizado correctamente.',
            'errors' => null,
        ], Response::HTTP_OK);
    }

    /**
     * Soft-delete a health professional.
     */
    public function destroy(HealthStaff $healthStaff): JsonResponse
    {
        $healthStaff->delete();

        return response()->json([
            'data' => null,
            'message' => 'Personal de salud eliminado correctamente.',
            'errors' => null,
        ], Response::HTTP_OK);
    }
}

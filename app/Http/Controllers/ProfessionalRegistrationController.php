<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Exceptions\ProfessionalRegistrationConflict;
use App\Http\Requests\StoreHealthStaffRequest;
use App\Http\Resources\ProfessionalRegistrationResource;
use App\Models\User;
use App\Services\ProfessionalRegistrationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;

final class ProfessionalRegistrationController extends Controller
{
    public function store(StoreHealthStaffRequest $request, ProfessionalRegistrationService $service): JsonResponse
    {
        $user = User::query()->findOrFail($request->integer('user_id'));

        try {
            $staff = $service->register($user, $request->validated(), $request->user());
        } catch (ProfessionalRegistrationConflict $exception) {
            return response()->json([
                'data' => null,
                'message' => $exception->getMessage(),
                'errors' => ['professional' => [$exception->getMessage()]],
            ], Response::HTTP_CONFLICT);
        }

        $user->load('roles');
        $staff->load(['person', 'specialty']);

        return response()->json([
            'data' => new ProfessionalRegistrationResource(['user' => $user, 'health_staff' => $staff]),
            'message' => 'Profesional registrado correctamente.',
            'errors' => null,
        ], Response::HTTP_CREATED);
    }
}

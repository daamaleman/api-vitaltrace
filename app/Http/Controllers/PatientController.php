<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\StorePatientRequest;
use App\Http\Requests\UpdatePatientRequest;
use App\Http\Resources\PatientResource;
use App\Models\Patient;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

class PatientController extends Controller
{
    /**
     * Display a paginated listing of patients.
     *
     * @return AnonymousResourceCollection
     */
    public function index(): AnonymousResourceCollection
    {
        $patients = Patient::with('person')->latest('id')->paginate(15);

        return PatientResource::collection($patients);
    }

    /**
     * Store a newly created patient.
     *
     * @param  StorePatientRequest  $request
     * @return JsonResponse
     */
    public function store(StorePatientRequest $request): JsonResponse
    {
        $patient = Patient::create($request->validated());

        return response()->json([
            'data' => new PatientResource($patient->load('person')),
            'message' => 'Patient registered successfully.',
            'errors' => null,
        ], Response::HTTP_CREATED);
    }

    /**
     * Display the specified patient.
     *
     * @param  Patient  $patient
     * @return JsonResponse
     */
    public function show(Patient $patient): JsonResponse
    {
        return response()->json([
            'data' => new PatientResource($patient->load('person')),
            'message' => null,
            'errors' => null,
        ], Response::HTTP_OK);
    }

    /**
     * Update the specified patient.
     *
     * @param  UpdatePatientRequest  $request
     * @param  Patient  $patient
     * @return JsonResponse
     */
    public function update(UpdatePatientRequest $request, Patient $patient): JsonResponse
    {
        $patient->update($request->validated());

        return response()->json([
            'data' => new PatientResource($patient->load('person')),
            'message' => 'Patient updated successfully.',
            'errors' => null,
        ], Response::HTTP_OK);
    }

    /**
     * Archive the specified patient.
     *
     * @param  Patient  $patient
     * @return JsonResponse
     */
    public function destroy(Patient $patient): JsonResponse
    {
        $patient->delete();

        return response()->json([
            'data' => null,
            'message' => 'Patient archived successfully.',
            'errors' => null,
        ], Response::HTTP_OK);
    }
}

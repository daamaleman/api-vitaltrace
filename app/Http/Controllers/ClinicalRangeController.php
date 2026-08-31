<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ResolvesAssignedPatients;
use App\Http\Requests\StoreClinicalRangeRequest;
use App\Http\Requests\UpdateClinicalRangeRequest;
use App\Http\Resources\ClinicalRangeResource;
use App\Models\ClinicalRange;
use App\Models\Patient;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

/**
 * CRUD endpoints for clinical ranges.
 */
class ClinicalRangeController extends Controller
{
    use ResolvesAssignedPatients;

    /**
     * List paginated clinical ranges with patient and measurement type.
     */
    public function index(): AnonymousResourceCollection
    {
        $ranges = ClinicalRange::with(['patient', 'measurementType'])->latest('id')->paginate(15);

        return ClinicalRangeResource::collection($ranges);
    }

    /**
     * Define a new clinical range for an assigned patient (RN-06 scoped).
     */
    public function storeForPatient(Request $request, Patient $patient): JsonResponse
    {
        if (! $this->assignedPatientIds($request)->contains($patient->id)) {
            return response()->json([
                'data' => null,
                'message' => 'No tienes permiso para registrar en este paciente.',
                'errors' => null,
            ], Response::HTTP_FORBIDDEN);
        }

        $data = $request->validate([
            'measurement_type_id' => ['required', 'integer', 'exists:measurement_types,id'],
            'min_value' => ['nullable', 'numeric'],
            'max_value' => ['nullable', 'numeric'],
            'severity' => ['required', 'string', 'in:INFORMATIONAL,MODERATE,HIGH,CRITICAL'],
            'start_date' => ['required', 'date'],
            'end_date' => ['nullable', 'date'],
        ]);

        $range = ClinicalRange::create([
            'patient_id' => $patient->id,
            'defined_by' => $request->user()->id,
            'measurement_type_id' => $data['measurement_type_id'],
            'min_value' => $data['min_value'] ?? null,
            'max_value' => $data['max_value'] ?? null,
            'severity' => $data['severity'],
            'start_date' => $data['start_date'],
            'end_date' => $data['end_date'] ?? null,
        ]);

        return response()->json([
            'data' => new ClinicalRangeResource($range),
            'message' => 'Rango clínico registrado correctamente.',
            'errors' => null,
        ], Response::HTTP_CREATED);
    }

    /**
     * Define a new clinical range.
     */
    public function store(StoreClinicalRangeRequest $request): JsonResponse
    {
        $range = ClinicalRange::create($request->validated());

        return response()->json([
            'data' => new ClinicalRangeResource($range->load(['patient', 'measurementType'])),
            'message' => 'Rango clínico creado correctamente.',
            'errors' => null,
        ], Response::HTTP_CREATED);
    }

    /**
     * Show a single clinical range.
     */
    public function show(ClinicalRange $clinicalRange): JsonResponse
    {
        return response()->json([
            'data' => new ClinicalRangeResource($clinicalRange->load(['patient', 'measurementType'])),
            'message' => null,
            'errors' => null,
        ], Response::HTTP_OK);
    }

    /**
     * Update an existing clinical range.
     */
    public function update(UpdateClinicalRangeRequest $request, ClinicalRange $clinicalRange): JsonResponse
    {
        $clinicalRange->update($request->validated());

        return response()->json([
            'data' => new ClinicalRangeResource($clinicalRange->load(['patient', 'measurementType'])),
            'message' => 'Rango clínico actualizado correctamente.',
            'errors' => null,
        ], Response::HTTP_OK);
    }

    /**
     * Soft-delete a clinical range.
     */
    public function destroy(ClinicalRange $clinicalRange): JsonResponse
    {
        $clinicalRange->delete();

        return response()->json([
            'data' => null,
            'message' => 'Rango clínico eliminado correctamente.',
            'errors' => null,
        ], Response::HTTP_OK);
    }
}
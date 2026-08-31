<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ResolvesAssignedPatients;
use App\Http\Requests\StoreTreatmentRequest;
use App\Http\Requests\UpdateTreatmentRequest;
use App\Http\Resources\TreatmentResource;
use App\Models\Patient;
use App\Models\Treatment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;

/**
 * CRUD endpoints for treatments.
 */
class TreatmentController extends Controller
{
    use ResolvesAssignedPatients;

    /**
     * List paginated treatments with patient and diagnosis.
     */
    public function index(): AnonymousResourceCollection
    {
        $treatments = Treatment::with(['patient', 'diagnosis'])->latest('id')->paginate(15);

        return TreatmentResource::collection($treatments);
    }

    /**
     * Prescribe a new treatment for an assigned patient (RN-06 scoped).
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
            'indications' => ['required', 'string', 'max:2000'],
            'start_date' => ['required', 'date'],
            'end_date' => ['nullable', 'date'],
            'status' => ['required', 'string', 'in:ACTIVE,FINISHED,SUSPENDED'],
            'diagnosis_id' => ['nullable', 'integer', 'exists:diagnoses,id'],
            'medications' => ['array'],
            'medications.*.medication_id' => ['required', 'integer', 'exists:medications,id'],
            'medications.*.dose' => ['required', 'string', 'max:80'],
            'medications.*.route' => ['required', 'string', 'max:50'],
            'medications.*.frequency' => ['required', 'string', 'max:80'],
        ]);

        $treatment = DB::transaction(function () use ($data, $patient, $request) {
            $treatment = Treatment::create([
                'patient_id' => $patient->id,
                'prescribed_by' => $request->user()->id,
                'diagnosis_id' => $data['diagnosis_id'] ?? null,
                'indications' => $data['indications'],
                'start_date' => $data['start_date'],
                'end_date' => $data['end_date'] ?? null,
                'status' => $data['status'],
            ]);

            foreach ($data['medications'] ?? [] as $m) {
                DB::table('treatment_medication')->insert([
                    'treatment_id' => $treatment->id,
                    'medication_id' => $m['medication_id'],
                    'dose' => $m['dose'],
                    'route' => $m['route'],
                    'frequency' => $m['frequency'],
                    'start_date' => $data['start_date'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            return $treatment;
        });

        return response()->json([
            'data' => new TreatmentResource($treatment),
            'message' => 'Tratamiento registrado correctamente.',
            'errors' => null,
        ], Response::HTTP_CREATED);
    }

    /**
     * Prescribe a new treatment.
     */
    public function store(StoreTreatmentRequest $request): JsonResponse
    {
        $treatment = Treatment::create($request->validated());

        return response()->json([
            'data' => new TreatmentResource($treatment->load(['patient', 'diagnosis'])),
            'message' => 'Treatment prescribed successfully.',
            'errors' => null,
        ], Response::HTTP_CREATED);
    }

    /**
     * Show a single treatment.
     */
    public function show(Treatment $treatment): JsonResponse
    {
        return response()->json([
            'data' => new TreatmentResource($treatment->load(['patient', 'diagnosis'])),
            'message' => null,
            'errors' => null,
        ], Response::HTTP_OK);
    }

    /**
     * Update an existing treatment.
     */
    public function update(UpdateTreatmentRequest $request, Treatment $treatment): JsonResponse
    {
        $treatment->update($request->validated());

        return response()->json([
            'data' => new TreatmentResource($treatment->load(['patient', 'diagnosis'])),
            'message' => 'Treatment updated successfully.',
            'errors' => null,
        ], Response::HTTP_OK);
    }

    /**
     * Soft-delete a treatment.
     */
    public function destroy(Treatment $treatment): JsonResponse
    {
        $treatment->delete();

        return response()->json([
            'data' => null,
            'message' => 'Treatment removed successfully.',
            'errors' => null,
        ], Response::HTTP_OK);
    }
}
<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Resources\PatientResource;
use App\Models\Patient;
use App\Models\Person;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

/**
 * Admission-side patient management (§8.1, RN-01).
 *
 * Registers and updates a patient together with its underlying person in a
 * single transaction. Only the Admission role reaches these endpoints.
 */
class AdmissionPatientController extends Controller
{
    /**
     * Paginated list of patients with their person data.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $query = Patient::with('person')->latest('id');

        if ($search = $request->query('search')) {
            $query->whereHas('person', function ($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                    ->orWhere('first_last_name', 'like', "%{$search}%");
            })->orWhere('record_number', 'like', "%{$search}%");
        }

        return PatientResource::collection($query->paginate(15));
    }

    /**
     * Show a single patient.
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
     * Register a new patient together with its person, transactionally.
     */
    public function store(Request $request): JsonResponse
    {
        $data = $this->validatePayload($request);

        $patient = DB::transaction(function () use ($data, $request) {
            $person = Person::create([
                'first_name' => $data['first_name'],
                'middle_name' => $data['middle_name'] ?? null,
                'first_last_name' => $data['first_last_name'],
                'second_last_name' => $data['second_last_name'] ?? null,
                'date_of_birth' => $data['date_of_birth'],
                'gender' => $data['gender'],
                'identity_document' => $data['identity_document'] ?? null,
                'phone' => $data['phone'] ?? null,
                'address' => $data['address'] ?? null,
            ]);

            return Patient::create([
                'person_id' => $person->id,
                'record_number' => $data['record_number'],
                'admission_date' => $data['admission_date'],
                'administrative_status' => $data['administrative_status'] ?? 'PRE_REGISTERED',
                'emergency_contact_name' => $data['emergency_contact_name'] ?? null,
                'emergency_contact_phone' => $data['emergency_contact_phone'] ?? null,
                'administrative_notes' => $data['administrative_notes'] ?? null,
                'registered_by' => $request->user()->id,
            ]);
        });

        return response()->json([
            'data' => new PatientResource($patient->load('person')),
            'message' => 'Patient registered successfully.',
            'errors' => null,
        ], Response::HTTP_CREATED);
    }

    /**
     * Update an existing patient and its person, transactionally.
     */
    public function update(Request $request, Patient $patient): JsonResponse
    {
        $data = $this->validatePayload($request, $patient);

        DB::transaction(function () use ($data, $patient) {
            $patient->person->update([
                'first_name' => $data['first_name'],
                'middle_name' => $data['middle_name'] ?? null,
                'first_last_name' => $data['first_last_name'],
                'second_last_name' => $data['second_last_name'] ?? null,
                'date_of_birth' => $data['date_of_birth'],
                'gender' => $data['gender'],
                'identity_document' => $data['identity_document'] ?? null,
                'phone' => $data['phone'] ?? null,
                'address' => $data['address'] ?? null,
            ]);

            $patient->update([
                'record_number' => $data['record_number'],
                'admission_date' => $data['admission_date'],
                'administrative_status' => $data['administrative_status'] ?? $patient->administrative_status,
                'emergency_contact_name' => $data['emergency_contact_name'] ?? null,
                'emergency_contact_phone' => $data['emergency_contact_phone'] ?? null,
                'administrative_notes' => $data['administrative_notes'] ?? null,
            ]);
        });

        return response()->json([
            'data' => new PatientResource($patient->fresh()->load('person')),
            'message' => 'Patient updated successfully.',
            'errors' => null,
        ], Response::HTTP_OK);
    }

    /**
     * Validate person + patient payload. On update, ignores the current
     * records for uniqueness checks.
     */
    private function validatePayload(Request $request, ?Patient $patient = null): array
    {
        $personId = $patient?->person_id;
        $patientId = $patient?->id;

        return $request->validate([
            // Person
            'first_name' => ['required', 'string', 'max:80'],
            'middle_name' => ['nullable', 'string', 'max:80'],
            'first_last_name' => ['required', 'string', 'max:80'],
            'second_last_name' => ['nullable', 'string', 'max:80'],
            'date_of_birth' => ['required', 'date', 'before:today'],
            'gender' => ['required', 'string', 'max:30'],
            'identity_document' => [
                'nullable', 'string', 'max:40',
                Rule::unique('people', 'identity_document')->ignore($personId),
            ],
            'phone' => ['nullable', 'string', 'max:25'],
            'address' => ['nullable', 'string'],
            // Patient
            'record_number' => [
                'required', 'string', 'max:30',
                Rule::unique('patients', 'record_number')->ignore($patientId),
            ],
            'admission_date' => ['required', 'date'],
            'administrative_status' => ['sometimes', 'string', 'in:PRE_REGISTERED,ACTIVE,INACTIVE,DISCHARGED,ARCHIVED'],
            'emergency_contact_name' => ['nullable', 'string', 'max:160'],
            'emergency_contact_phone' => ['nullable', 'string', 'max:25'],
            'administrative_notes' => ['nullable', 'string'],
        ]);
    }
}
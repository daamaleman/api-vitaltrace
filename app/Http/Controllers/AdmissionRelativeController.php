<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Patient;
use App\Models\PatientRelative;
use App\Models\Person;
use App\Models\Relative;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use App\Models\Role;
use App\Models\User;
use App\Services\ActivationService;

/**
 * Admission-side relative management (§8.2, RN-03).
 *
 * Registers a relative (person + relative + patient link) transactionally,
 * enforcing a maximum of two active relatives per patient.
 */
class AdmissionRelativeController extends Controller
{
    /**
     * List the relatives linked to a given patient.
     */
    public function __construct(private ActivationService $activationService)
    {
    }

    public function index(Patient $patient): JsonResponse
    {
        $links = PatientRelative::with('relative.person')
            ->where('patient_id', $patient->id)
            ->latest('id')
            ->get();

        return response()->json([
            'data' => $links,
            'message' => null,
            'errors' => null,
        ], Response::HTTP_OK);
    }

    /**
     * Register a new relative for a patient, enforcing RN-03.
     */
    public function store(Request $request, Patient $patient): JsonResponse
    {
        $data = $request->validate([
            'first_name' => ['required', 'string', 'max:80'],
            'middle_name' => ['nullable', 'string', 'max:80'],
            'first_last_name' => ['required', 'string', 'max:80'],
            'second_last_name' => ['nullable', 'string', 'max:80'],
            'date_of_birth' => ['required', 'date', 'before:today'],
            'gender' => ['required', 'string', 'max:30'],
            'identity_document' => ['nullable', 'string', 'max:40'],
            'phone' => ['nullable', 'string', 'max:25'],
            'relationship' => ['required', 'string', 'max:50'],
            'email' => ['required', 'email', 'max:180', 'unique:users,email'],
        ]);

        // RN-03: maximum two active (PENDING/ACTIVE) relatives per patient.
        $activeCount = PatientRelative::where('patient_id', $patient->id)
            ->whereIn('status', PatientRelative::ACTIVE_STATUSES)
            ->count();
        if ($activeCount >= 2) {
            return response()->json([
                'data' => null,
                'message' => 'El paciente ya tiene el máximo de dos familiares activos.',
                'errors' => ['patient_id' => ['Maximum of two active relatives reached.']],
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $link = DB::transaction(function () use ($data, $patient, $request) {
            $person = Person::create([
                'first_name' => $data['first_name'],
                'middle_name' => $data['middle_name'] ?? null,
                'first_last_name' => $data['first_last_name'],
                'second_last_name' => $data['second_last_name'] ?? null,
                'date_of_birth' => $data['date_of_birth'],
                'gender' => $data['gender'],
                'identity_document' => $data['identity_document'] ?? null,
                'phone' => $data['phone'] ?? null,
            ]);

            $relative = Relative::create(['person_id' => $person->id]);

            $link = PatientRelative::create([
                'patient_id' => $patient->id,
                'relative_id' => $relative->id,
                'relationship' => $data['relationship'],
                'status' => 'PENDING',
                'start_date' => now()->toDateString(),
                'registered_by' => $request->user()->id,
            ]);

            // Create the relative's login account and send the activation code.
            $user = User::create([
                'person_id' => $person->id,
                'email' => mb_strtolower(trim($data['email'])),
                'password' => null,
                'status' => 'PENDING',
            ]);

            $relativeRole = Role::query()->where('name', 'RELATIVE')->firstOrFail();
            $user->roles()->attach($relativeRole->id, [
                'active' => true,
                'assigned_at' => now(),
                'assigned_by' => $request->user()?->id,
            ]);

            $this->activationService->issueFor($user);

            return $link;
        });

        return response()->json([
            'data' => $link->load('relative.person'),
            'message' => 'Familiar registrado. Se envió el código de activación a su correo.',
            'errors' => null,
        ], Response::HTTP_CREATED);
    }    


    /**
     * Revoke a patient-relative link (soft state change, keeps history).
     */
    public function revoke(PatientRelative $patientRelative): JsonResponse
    {
        $patientRelative->update([
            'status' => 'REVOKED',
            'end_date' => now()->toDateString(),
        ]);

        return response()->json([
            'data' => $patientRelative->fresh()->load('relative.person'),
            'message' => 'Acceso del familiar revocado.',
            'errors' => null,
        ], Response::HTTP_OK);
    }
}

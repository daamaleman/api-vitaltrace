<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\ActivationController;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Auth\PasswordResetController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\PersonController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\PermissionController;
use App\Http\Controllers\UserRoleController;
use App\Http\Controllers\RolePermissionController;
use App\Http\Controllers\PatientController;
use App\Http\Controllers\RelativeController;
use App\Http\Controllers\PatientRelativeController;
use App\Http\Controllers\AdministrativeStaffController;
use App\Http\Controllers\SpecialtyController;
use App\Http\Controllers\HealthStaffController;
use App\Http\Controllers\ProfessionalAssignmentController;
use App\Http\Controllers\AccountActivationController;
use App\Http\Controllers\CorrectionRequestController;
use App\Http\Controllers\DiagnosisController;
use App\Http\Controllers\ClinicalEvolutionController;
use App\Http\Controllers\TreatmentController;
use App\Http\Controllers\MedicationController;
use App\Http\Controllers\TreatmentMedicationController;
use App\Http\Controllers\MeasurementTypeController;
use App\Http\Controllers\MeasurementController;
use App\Http\Controllers\ClinicalRangeController;
use App\Http\Controllers\AppointmentController;
use App\Http\Controllers\AlertController;
use App\Http\Controllers\AlertActionController;
use App\Http\Controllers\AppNotificationController;
use App\Http\Controllers\IntegrationLogController;
use App\Http\Controllers\AuditLogController;
use App\Http\Controllers\Portal\PatientPortalController;
use App\Http\Controllers\Portal\PatientNotificationController;
use App\Http\Controllers\Portal\PatientRelativeAccessController;
use Illuminate\Http\Request;
use App\Http\Controllers\AdmissionPatientController;
use App\Http\Controllers\ClinicalPatientController;
use App\Http\Controllers\AdmissionRelativeController;
use App\Http\Controllers\AdmissionAssignmentController;
use App\Http\Controllers\AdmissionAccountController;
use App\Http\Controllers\AdmissionCorrectionController;
use App\Http\Controllers\AdminUserController;
use App\Http\Controllers\AdminUserRoleController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::prefix('v1')->group(function () {
    // Public authentication and activation, rate-limited against brute force.
    Route::middleware('throttle:6,1')->group(function () {
        Route::post('auth/login', [AuthController::class, 'login']);
        Route::post('auth/activate-account', [ActivationController::class, 'activate']);
        Route::post('auth/resend-code', [ActivationController::class, 'resend']);
        Route::post('auth/activation/verify-code', [ActivationController::class, 'verifyCode']);
        Route::post('auth/activation/set-password', [ActivationController::class, 'setPassword']);
        Route::post('auth/activation/resend-code', [ActivationController::class, 'resend']);
        Route::post('auth/forgot-password', [PasswordResetController::class, 'forgot']);
        Route::post('auth/reset-password', [PasswordResetController::class, 'reset']);
    });

    // Authenticated session.
    Route::middleware('auth:sanctum')->group(function () {
        Route::get('auth/me', [AuthController::class, 'me']);
        Route::post('auth/logout', [AuthController::class, 'logout']);

        // Patient self-service portal: reads and writes scoped to own record.
        Route::middleware('role:PATIENT')->prefix('patient')->group(function () {
            // Reads.
            Route::get('summary', [PatientPortalController::class, 'summary']);
            Route::get('profile', [PatientPortalController::class, 'profile']);
            Route::get('clinical-history', [PatientPortalController::class, 'clinicalHistory']);
            Route::get('appointments', [PatientPortalController::class, 'appointments']);
            Route::get('measurements', [PatientPortalController::class, 'measurements']);
            Route::get('treatments', [PatientPortalController::class, 'treatments']);
            Route::get('notifications', [PatientNotificationController::class, 'index']);
            Route::get('notifications/unread-count', [PatientNotificationController::class, 'unreadCount']);
            Route::patch('notifications/read-all', [PatientNotificationController::class, 'markAllAsRead']);
            Route::patch('notifications/{notification}/read', [PatientNotificationController::class, 'markAsRead']);

            // Writes.
            Route::post('measurements', [PatientPortalController::class, 'storeMeasurement']);
            Route::post('correction-requests', [PatientPortalController::class, 'storeCorrectionRequest']);

            // Relative access management by the patient.
            Route::get('relatives', [PatientRelativeAccessController::class, 'index']);
            Route::put('relatives/{patientRelative}/authorize', [PatientRelativeAccessController::class, 'grant']);
            Route::delete('relatives/{patientRelative}/authorize', [PatientRelativeAccessController::class, 'revoke']);
        });

        // Alert workflow: nurses and doctors classify/escalate; only doctors close.
        Route::middleware('role:DOCTOR,NURSE')->group(function () {
            Route::post('alerts/{alert}/classify', [AlertActionController::class, 'classify']);
            Route::post('alerts/{alert}/escalate', [AlertActionController::class, 'escalate']);
        });

        Route::middleware('role:DOCTOR')->group(function () {
            Route::post('alerts/{alert}/close', [AlertActionController::class, 'close']);
        });
    });

    // Define API resource routes for various controllers
    // Admission-only administrative management.
    Route::middleware('role:ADMISSION')->group(function () {
        // People catalog: managed by admission only (RN-01, RN-06). Other roles
        // reach person data through their own scoped endpoints (e.g. assigned
        // patients), never through this generic CRUD.
        Route::apiResource('people', PersonController::class);
        Route::apiResource('patients', PatientController::class);
        Route::apiResource('relatives', RelativeController::class);
        Route::apiResource('patient-relatives', PatientRelativeController::class);
        Route::apiResource('professional-assignments', ProfessionalAssignmentController::class);
        Route::apiResource('correction-requests', CorrectionRequestController::class);
        Route::get('admission/patients', [AdmissionPatientController::class, 'index']);
        Route::get('admission/patients/{patient}', [AdmissionPatientController::class, 'show']);
        Route::post('admission/patients', [AdmissionPatientController::class, 'store']);
        Route::put('admission/patients/{patient}', [AdmissionPatientController::class, 'update']);
        Route::get('admission/patients/{patient}/relatives', [AdmissionRelativeController::class, 'index']);
        Route::post('admission/patients/{patient}/relatives', [AdmissionRelativeController::class, 'store']);
        Route::post('admission/relative-links/{patientRelative}/revoke', [AdmissionRelativeController::class, 'revoke']);
        Route::get('admission/staff', [AdmissionAssignmentController::class, 'availableStaff']);
        Route::get('admission/patients/{patient}/assignments', [AdmissionAssignmentController::class, 'index']);
        Route::post('admission/patients/{patient}/assignments', [AdmissionAssignmentController::class, 'store']);
        Route::post('admission/assignments/{professionalAssignment}/finish', [AdmissionAssignmentController::class, 'finish']);
        Route::get('admission/accounts', [AdmissionAccountController::class, 'index']);
        Route::get('admission/accounts/available-people', [AdmissionAccountController::class, 'peopleWithoutAccount']);
        Route::post('admission/accounts', [AdmissionAccountController::class, 'store']);
        Route::post('admission/accounts/{user}/resend', [AdmissionAccountController::class, 'resend']);
        Route::post('admission/accounts/{user}/block', [AdmissionAccountController::class, 'block']);
        Route::post('admission/accounts/{user}/unblock', [AdmissionAccountController::class, 'unblock']);
        Route::get('admission/corrections', [AdmissionCorrectionController::class, 'index']);
        Route::post('admission/corrections/{correctionRequest}/approve', [AdmissionCorrectionController::class, 'approve']);
        Route::post('admission/corrections/{correctionRequest}/reject', [AdmissionCorrectionController::class, 'reject']);
    });

    // Clinical work for doctors and nurses.
    Route::middleware('role:DOCTOR,NURSE')->group(function () {
        Route::apiResource('diagnoses', DiagnosisController::class);
        Route::apiResource('clinical-evolutions', ClinicalEvolutionController::class);
        Route::apiResource('treatments', TreatmentController::class);
        Route::apiResource('treatment-medications', TreatmentMedicationController::class);
        Route::apiResource('measurements', MeasurementController::class);
        Route::apiResource('clinical-ranges', ClinicalRangeController::class);
        Route::apiResource('appointments', AppointmentController::class);
        Route::apiResource('alerts', AlertController::class);
        Route::post('alerts/{alert}/classify', [AlertActionController::class, 'classify']);
        Route::post('alerts/{alert}/escalate', [AlertActionController::class, 'escalate']);
        Route::post('alerts/{alert}/close', [AlertActionController::class, 'close']);
        Route::apiResource('alert-history', AlertHistoryController::class)->only(['index', 'store', 'show']);
        Route::get('clinical/patients', [ClinicalPatientController::class, 'index']);
        Route::get('clinical/patients/{patient}', [ClinicalPatientController::class, 'show']);
        Route::get('clinical/patients/{patient}/summary', [ClinicalPatientController::class, 'summary']);
        Route::get('clinical/appointments', [ClinicalPatientController::class, 'appointments']);
    });

    // System administration and catalogs.
    Route::middleware(['auth:sanctum', 'role:SYSTEM_ADMIN'])->group(function () {
        Route::apiResource('roles', RoleController::class);
        Route::apiResource('permissions', PermissionController::class);
        Route::apiResource('role-permissions', RolePermissionController::class);
        Route::apiResource('users', UserController::class);
        Route::apiResource('user-roles', UserRoleController::class);
        Route::apiResource('administrative-staff', AdministrativeStaffController::class);
        Route::apiResource('specialties', SpecialtyController::class);
        Route::apiResource('health-staff', HealthStaffController::class);
        Route::apiResource('medications', MedicationController::class);
        Route::apiResource('measurement-types', MeasurementTypeController::class);
        Route::apiResource('account-activations', AccountActivationController::class);
        Route::apiResource('notifications', AppNotificationController::class)->parameters([
            'notifications' => 'notification',
        ]);
        Route::apiResource('integration-logs', IntegrationLogController::class)->only(['index', 'store', 'show']);
        Route::apiResource('audit-logs', AuditLogController::class)->only(['index', 'store', 'show']);
        Route::get('admin/users', [AdminUserController::class, 'index']);
        Route::post('admin/users/{user}/block', [AdminUserController::class, 'block']);
        Route::post('admin/users/{user}/unblock', [AdminUserController::class, 'unblock']);
                Route::get('admin/users/{user}/roles', [AdminUserRoleController::class, 'index']);
        Route::post('admin/users/{user}/roles', [AdminUserRoleController::class, 'assign']);
        Route::delete('admin/users/{user}/roles/{role}', [AdminUserRoleController::class, 'revoke']);
    });
});

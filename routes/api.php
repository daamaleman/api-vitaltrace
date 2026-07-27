<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
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
    Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
        return $request->user();
    });

    // Define API resource routes for various controllers
    Route::apiResource('roles', RoleController::class);
    Route::apiResource('permissions', PermissionController::class);
    Route::apiResource('role-permissions', RolePermissionController::class);
    Route::apiResource('people', PersonController::class);
    Route::apiResource('users', UserController::class);
    Route::apiResource('user-roles', UserRoleController::class);
    Route::apiResource('patients', PatientController::class);
    Route::apiResource('relatives', RelativeController::class);
    Route::apiResource('patient-relatives', PatientRelativeController::class);
    Route::apiResource('administrative-staff', AdministrativeStaffController::class);
    Route::apiResource('specialties', SpecialtyController::class);
    Route::apiResource('health-staff', HealthStaffController::class);
    Route::apiResource('professional-assignments', ProfessionalAssignmentController::class);
    Route::apiResource('account-activations', AccountActivationController::class);
    Route::apiResource('correction-requests', CorrectionRequestController::class);
    Route::apiResource('diagnoses', DiagnosisController::class);
    Route::apiResource('clinical-evolutions', ClinicalEvolutionController::class);
    Route::apiResource('treatments', TreatmentController::class);
    Route::apiResource('medications', MedicationController::class);
    Route::apiResource('treatment-medications', TreatmentMedicationController::class);
    Route::apiResource('measurement-types', MeasurementTypeController::class);
    Route::apiResource('measurements', MeasurementController::class);
    Route::apiResource('clinical-ranges', ClinicalRangeController::class);
    Route::apiResource('appointments', AppointmentController::class);
    Route::apiResource('alerts', AlertController::class);
});

<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Schema;

use App\Observers\AuditableObserver;
use App\Models\Patient;
use App\Models\Person;
use App\Models\Relative;
use App\Models\PatientRelative;
use App\Models\ProfessionalAssignment;
use App\Models\Diagnosis;
use App\Models\ClinicalEvolution;
use App\Models\Treatment;
use App\Models\ClinicalRange;
use App\Models\Measurement;
use App\Models\Appointment;
use App\Models\Alert;
use App\Models\CorrectionRequest;
use App\Models\User;
use App\Models\Specialty;
use App\Models\Medication;
use App\Models\MeasurementType;
use App\Models\HealthStaff;
use App\Models\AdministrativeStaff;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Schema::defaultStringLength(191);

        $auditable = [
            Patient::class, Person::class, Relative::class, PatientRelative::class,
            ProfessionalAssignment::class, Diagnosis::class, ClinicalEvolution::class,
            Treatment::class, ClinicalRange::class, Measurement::class,
            Appointment::class, Alert::class, CorrectionRequest::class, User::class,
            Specialty::class, Medication::class, MeasurementType::class,
            HealthStaff::class, AdministrativeStaff::class,
        ];

        foreach ($auditable as $model) {
            $model::observe(AuditableObserver::class);
        }
    }
}
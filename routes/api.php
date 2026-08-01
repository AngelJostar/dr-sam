<?php

use App\Http\Controllers\Api\DashboardSummaryController;
use App\Http\Controllers\Api\Insurance\InsuranceApiController;
use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'auth'])->get('/dashboard-summary', DashboardSummaryController::class);

Route::middleware(['web', 'auth'])
    ->prefix('insurance')
    ->name('api.insurance.')
    ->group(function (): void {
        Route::get('dashboard', [InsuranceApiController::class, 'dashboard'])->name('dashboard');
        Route::get('reports', [InsuranceApiController::class, 'reports'])->name('reports');
        Route::get('patients', [InsuranceApiController::class, 'patients'])->name('patients.index');
        Route::post('patients', [InsuranceApiController::class, 'storePatient'])->name('patients.store');
        Route::get('patients/{patient}', [InsuranceApiController::class, 'patient'])->name('patients.show');
        Route::post('diagnoses', [InsuranceApiController::class, 'storeDiagnosis'])->name('diagnoses.store');
        Route::post('treatments', [InsuranceApiController::class, 'storeTreatment'])->name('treatments.store');
        Route::post('deliveries', [InsuranceApiController::class, 'storeDelivery'])->name('deliveries.store');
        Route::patch('deliveries/{delivery}', [InsuranceApiController::class, 'updateDelivery'])->name('deliveries.update');
        Route::post('hospitalizations', [InsuranceApiController::class, 'storeHospitalization'])->name('hospitalizations.store');
        Route::post('hospitalization-notes', [InsuranceApiController::class, 'storeDailyNote'])->name('hospitalization-notes.store');
        Route::post('invoices', [InsuranceApiController::class, 'storeInvoice'])->name('invoices.store');
        Route::post('authorizations', [InsuranceApiController::class, 'storeAuthorization'])->name('authorizations.store');
        Route::post('documents', [InsuranceApiController::class, 'storeDocument'])->name('documents.store');
    });

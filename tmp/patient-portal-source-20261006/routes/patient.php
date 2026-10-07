<?php

use App\Http\Controllers\Patient\PatientPortalController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'role:superadmin,admin,patient'])->group(function (): void {
    Route::get('/patient', PatientPortalController::class)->name('patient.dashboard');
    Route::post('/patient/register-attachments', [PatientPortalController::class, 'storeRegisterAttachment'])->name('patient.register_attachments.store');
    Route::get('/patient/register-attachments/{document}', [PatientPortalController::class, 'downloadRegisterAttachment'])->name('patient.register_attachments.download');
    Route::patch('/patient/profile', [PatientPortalController::class, 'updateProfile'])->name('patient.profile.update');
    Route::post('/patient/insurance', [PatientPortalController::class, 'saveInsurance'])->name('patient.insurance.save');
    Route::post('/patient/appointments', [PatientPortalController::class, 'storeAppointment'])->name('patient.appointments.store');
});

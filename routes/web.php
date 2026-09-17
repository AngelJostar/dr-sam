<?php

use App\Http\Controllers\Auth\DemoAccessController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\Doctor\DoctorPortalController;
use App\Http\Controllers\ExternalPharmacy\ExternalPharmacyController;
use App\Http\Controllers\Insurance\AdminController as InsuranceAdminController;
use App\Http\Controllers\Insurance\AuthorizationController as InsuranceAuthorizationController;
use App\Http\Controllers\Insurance\DocumentController as InsuranceDocumentController;
use App\Http\Controllers\Insurance\HospitalizationController as InsuranceHospitalizationController;
use App\Http\Controllers\Insurance\HospitalizationDailyNoteController as InsuranceHospitalizationDailyNoteController;
use App\Http\Controllers\Insurance\InsuranceDashboardController;
use App\Http\Controllers\Insurance\InsurancePatientController;
use App\Http\Controllers\Insurance\InvoiceController as InsuranceInvoiceController;
use App\Http\Controllers\Insurance\MedicationDeliveryController as InsuranceMedicationDeliveryController;
use App\Http\Controllers\Insurance\PatientDiagnosisController as InsurancePatientDiagnosisController;
use App\Http\Controllers\Insurance\ReportController as InsuranceReportController;
use App\Http\Controllers\Insurance\TreatmentController as InsuranceTreatmentController;
use App\Http\Controllers\InsuranceAdvisor\InsuranceAdvisorController;
use App\Http\Controllers\Institution\InstitutionDashboardController;
use App\Http\Controllers\Messenger\MessengerController;
use App\Http\Controllers\Operational\OperationalDashboardController;
use App\Http\Controllers\Operational\OutpatientDashboardController;
use App\Http\Controllers\Orders\PatientOrderController;
use App\Http\Controllers\Patient\PatientPortalController;
use App\Http\Controllers\Pharmacy\DigitalPharmacyController;
use App\Http\Controllers\Provider\ProviderPortalController;
use App\Http\Controllers\SuperAdmin\SuperAdminDashboardController;
use App\Http\Controllers\Unit\UnitDashboardController;
use Illuminate\Support\Facades\Route;

Route::get('/', [DemoAccessController::class, 'index'])->name('login');
Route::post('/demo-login', [DemoAccessController::class, 'store'])->name('demo-login.store');
Route::post('/logout', [DemoAccessController::class, 'destroy'])->name('logout');

Route::middleware('auth')->group(function (): void {
    Route::get('/dashboard', DashboardController::class)->name('dashboard');
    Route::get('/doctor', [DoctorPortalController::class, 'index'])
        ->middleware('role:superadmin,admin,doctor')
        ->name('doctor.dashboard');
    Route::post('/doctor/clinics', [DoctorPortalController::class, 'storeClinic'])->middleware('role:superadmin,admin,doctor')->name('doctor.clinics.store');
    Route::patch('/doctor/clinics/{clinic}', [DoctorPortalController::class, 'updateClinic'])->middleware('role:superadmin,admin,doctor')->name('doctor.clinics.update');
    Route::delete('/doctor/clinics/{clinic}', [DoctorPortalController::class, 'destroyClinic'])->middleware('role:superadmin,admin,doctor')->name('doctor.clinics.destroy');
    Route::post('/doctor/availability', [DoctorPortalController::class, 'storeAvailability'])->middleware('role:superadmin,admin,doctor')->name('doctor.availability.store');
    Route::delete('/doctor/availability/{availability}', [DoctorPortalController::class, 'destroyAvailability'])->middleware('role:superadmin,admin,doctor')->name('doctor.availability.destroy');
    Route::post('/doctor/appointments', [DoctorPortalController::class, 'storeAppointment'])->middleware('role:superadmin,admin,doctor')->name('doctor.appointments.store');
    Route::patch('/doctor/appointments/{appointment}/status', [DoctorPortalController::class, 'updateAppointmentStatus'])->middleware('role:superadmin,admin,doctor')->name('doctor.appointments.status');
    Route::post('/doctor/patients', [DoctorPortalController::class, 'storePatient'])->middleware('role:superadmin,admin,doctor')->name('doctor.patients.store');
    Route::patch('/doctor/patients/{patient}', [DoctorPortalController::class, 'updatePatient'])->middleware('role:superadmin,admin,doctor')->name('doctor.patients.update');
    Route::post('/doctor/patients/{patient}/records', [DoctorPortalController::class, 'storePatientRecord'])->middleware('role:superadmin,admin,doctor')->name('doctor.patients.records.store');
    Route::post('/doctor/encounters', [DoctorPortalController::class, 'storeEncounter'])->middleware('role:superadmin,admin,doctor')->name('doctor.encounters.store');
    Route::patch('/doctor/encounters/{encounter}', [DoctorPortalController::class, 'updateEncounter'])->middleware('role:superadmin,admin,doctor')->name('doctor.encounters.update');
    Route::patch('/doctor/encounters/{encounter}/complete', [DoctorPortalController::class, 'completeEncounter'])->middleware('role:superadmin,admin,doctor')->name('doctor.encounters.complete');
    Route::post('/doctor/prescriptions', [DoctorPortalController::class, 'storePrescription'])->middleware('role:superadmin,admin,doctor')->name('doctor.prescriptions.store');
    Route::patch('/doctor/prescriptions/{prescription}', [DoctorPortalController::class, 'updatePrescription'])->middleware('role:superadmin,admin,doctor')->name('doctor.prescriptions.update');
    Route::post('/doctor/service-requests', [DoctorPortalController::class, 'storeServiceRequest'])->middleware('role:superadmin,admin,doctor')->name('doctor.service_requests.store');
    Route::get('/patient', PatientPortalController::class)
        ->middleware('role:superadmin,admin,patient')
        ->name('patient.dashboard');
    Route::patch('/patient/profile', [PatientPortalController::class, 'updateProfile'])
        ->middleware('role:superadmin,admin,patient')
        ->name('patient.profile.update');
    Route::post('/patient/insurance', [PatientPortalController::class, 'saveInsurance'])
        ->middleware('role:superadmin,admin,patient')
        ->name('patient.insurance.save');
    Route::post('/patient/appointments', [PatientPortalController::class, 'storeAppointment'])
        ->middleware('role:superadmin,admin,patient')
        ->name('patient.appointments.store');
    Route::get('/orders', [PatientOrderController::class, 'index'])
        ->middleware('role:superadmin,admin,patient,operational')
        ->name('orders.index');
    Route::post('/orders', [PatientOrderController::class, 'store'])
        ->middleware(['role:superadmin,admin,patient,operational', 'permission:orders.create'])
        ->name('orders.store');
    Route::get('/pharmacy', [DigitalPharmacyController::class, 'index'])
        ->middleware('role:superadmin,admin,operational')
        ->name('pharmacy.dashboard');
    Route::patch('/pharmacy/orders/{order}/status', [DigitalPharmacyController::class, 'updateOrderStatus'])
        ->middleware(['role:superadmin,admin,operational', 'permission:pharmacy.orders.update'])
        ->name('pharmacy.orders.status');
    Route::get('/external-pharmacy', [ExternalPharmacyController::class, 'index'])
        ->middleware('role:superadmin,admin,operational')
        ->name('external-pharmacy.dashboard');
    Route::patch('/external-pharmacy/orders/{order}/status', [ExternalPharmacyController::class, 'updateOrderStatus'])
        ->middleware(['role:superadmin,admin,operational', 'permission:external_pharmacy.orders.update'])
        ->name('external-pharmacy.orders.status');
    Route::patch('/external-pharmacy/items/{item}/dispense', [ExternalPharmacyController::class, 'dispenseItem'])
        ->middleware(['role:superadmin,admin,operational', 'permission:external_pharmacy.orders.update'])
        ->name('external-pharmacy.items.dispense');
    Route::post('/external-pharmacy/inventory/{inventoryItem}/movements', [ExternalPharmacyController::class, 'storeMovement'])
        ->middleware('role:superadmin,admin,operational')
        ->name('external-pharmacy.inventory.movements.store');
    Route::post('/external-pharmacy/warehouses', [ExternalPharmacyController::class, 'storeWarehouse'])
        ->middleware('role:superadmin,admin,operational')
        ->name('external-pharmacy.warehouses.store');
    Route::patch('/external-pharmacy/warehouses/{warehouse}/status', [ExternalPharmacyController::class, 'updateWarehouseStatus'])
        ->middleware('role:superadmin,admin,operational')
        ->name('external-pharmacy.warehouses.status');
    Route::get('/messenger', [MessengerController::class, 'index'])
        ->middleware('role:superadmin,admin,messenger')
        ->name('messenger.dashboard');
    Route::patch('/messenger/routes/{route}/status', [MessengerController::class, 'updateRouteStatus'])
        ->middleware(['role:superadmin,admin,messenger', 'permission:messenger.routes.update'])
        ->name('messenger.routes.status');
    Route::get('/operational', [OperationalDashboardController::class, 'index'])
        ->middleware('role:superadmin,admin,institution,unit,operational')
        ->name('operational.dashboard');
    Route::get('/operational/patients', [OperationalDashboardController::class, 'patientCatalog'])
        ->middleware('role:superadmin,admin,institution,unit,operational')
        ->name('operational.patients.index');
    Route::patch('/operational/provider-requests/{providerRequest}/status', [OperationalDashboardController::class, 'updateProviderRequestStatus'])
        ->middleware(['role:superadmin,admin,institution,unit,operational', 'permission:operational.provider_requests.update'])
        ->name('operational.provider-requests.status');
    Route::patch('/operational/provider-requests/{providerRequest}/authorizations', [OperationalDashboardController::class, 'updateProviderRequestAuthorization'])
        ->middleware(['role:superadmin,admin,institution,unit,operational', 'permission:operational.provider_requests.update'])
        ->name('operational.provider-requests.authorizations.update');
    Route::post('/operational/patients', [OperationalDashboardController::class, 'storePatient'])
        ->middleware(['role:superadmin,admin,institution,unit,operational', 'permission:operational.patients.create'])
        ->name('operational.patients.store');
    Route::patch('/operational/patients/{patient}', [OperationalDashboardController::class, 'updatePatient'])
        ->middleware('role:superadmin,admin,institution,unit,operational')
        ->name('operational.patients.update');
    Route::post('/operational/service-requests', [OperationalDashboardController::class, 'storeServiceRequest'])
        ->middleware(['role:superadmin,admin,institution,unit,operational', 'permission:operational.service_requests.create'])
        ->name('operational.service-requests.store');
    Route::post('/operational/infusion-rooms', [OperationalDashboardController::class, 'storeInfusionRoom'])
        ->middleware('role:superadmin,admin,institution,unit,operational')
        ->name('operational.infusion-rooms.store');
    Route::put('/operational/infusion-rooms/{room}', [OperationalDashboardController::class, 'updateInfusionRoom'])
        ->middleware('role:superadmin,admin,institution,unit,operational')
        ->name('operational.infusion-rooms.update');
    Route::patch('/operational/provider-requests/{providerRequest}/infusion-assignment', [OperationalDashboardController::class, 'assignInfusionRoom'])
        ->middleware('role:superadmin,admin,institution,unit,operational')
        ->name('operational.infusion-assignments.update');
    Route::patch('/operational/provider-requests/{providerRequest}/mixture-schedule', [OperationalDashboardController::class, 'updateMixtureSchedule'])
        ->middleware('role:superadmin,admin,institution,unit,operational')
        ->name('operational.mixture-schedules.update');
    Route::get('/outpatient', [OutpatientDashboardController::class, 'index'])
        ->middleware('role:superadmin,admin,institution,unit,operational')
        ->name('outpatient.dashboard');
    Route::post('/outpatient/appointments', [OutpatientDashboardController::class, 'storeAppointment'])
        ->middleware(['role:superadmin,admin,institution,unit,operational', 'permission:outpatient.appointments.create'])
        ->name('outpatient.appointments.store');
    Route::patch('/outpatient/appointments/{appointment}', [OutpatientDashboardController::class, 'updateAppointment'])
        ->middleware(['role:superadmin,admin,institution,unit,operational', 'permission:outpatient.appointments.update'])
        ->name('outpatient.appointments.update');
    Route::post('/outpatient/patients', [OutpatientDashboardController::class, 'storePatient'])
        ->middleware(['role:superadmin,admin,institution,unit,operational', 'permission:operational.patients.create'])
        ->name('outpatient.patients.store');
    Route::patch('/outpatient/patients/{patient}', [OutpatientDashboardController::class, 'updatePatient'])
        ->middleware(['role:superadmin,admin,institution,unit,operational', 'permission:operational.patients.create'])
        ->name('outpatient.patients.update');
    Route::post('/outpatient/rooms', [OutpatientDashboardController::class, 'storeRoom'])
        ->middleware(['role:superadmin,admin,institution,unit,operational', 'permission:outpatient.rooms.manage'])
        ->name('outpatient.rooms.store');
    Route::patch('/outpatient/rooms/{room}', [OutpatientDashboardController::class, 'updateRoom'])
        ->middleware(['role:superadmin,admin,institution,unit,operational', 'permission:outpatient.rooms.manage'])
        ->name('outpatient.rooms.update');
    Route::delete('/outpatient/rooms/{room}', [OutpatientDashboardController::class, 'destroyRoom'])
        ->middleware(['role:superadmin,admin,institution,unit,operational', 'permission:outpatient.rooms.manage'])
        ->name('outpatient.rooms.destroy');
    Route::patch('/outpatient/calendar/{room}', [OutpatientDashboardController::class, 'updateCalendar'])
        ->middleware(['role:superadmin,admin,institution,unit,operational', 'permission:outpatient.calendar.manage'])
        ->name('outpatient.calendar.update');
    Route::post('/outpatient/prescriptions', [OutpatientDashboardController::class, 'storePrescription'])
        ->middleware(['role:superadmin,admin,institution,unit,operational', 'permission:outpatient.prescriptions.manage'])
        ->name('outpatient.prescriptions.store');
    Route::patch('/outpatient/prescriptions/{prescription}', [OutpatientDashboardController::class, 'updatePrescription'])
        ->middleware(['role:superadmin,admin,institution,unit,operational', 'permission:outpatient.prescriptions.manage'])
        ->name('outpatient.prescriptions.update');
    Route::get('/providers/npt', [ProviderPortalController::class, 'index'])
        ->defaults('type', 'npt')
        ->middleware('role:superadmin,admin,provider')
        ->name('provider.npt.dashboard');
    Route::get('/providers/chemo', [ProviderPortalController::class, 'index'])
        ->defaults('type', 'chemotherapy')
        ->middleware('role:superadmin,admin,provider')
        ->name('provider.chemo.dashboard');
    Route::get('/providers/import', [ProviderPortalController::class, 'index'])
        ->defaults('type', 'import')
        ->middleware('role:superadmin,admin,provider')
        ->name('provider.import.dashboard');
    Route::get('/providers/medicines', [ProviderPortalController::class, 'index'])
        ->defaults('type', 'medicines')
        ->middleware('role:superadmin,admin,provider')
        ->name('provider.medicines.dashboard');
    Route::get('/providers/clinical-labs', [ProviderPortalController::class, 'index'])
        ->defaults('type', 'clinical-labs')
        ->middleware('role:superadmin,admin,provider')
        ->name('provider.clinical-labs.dashboard');
    Route::patch('/providers/{type}/requests/{providerRequest}/status', [ProviderPortalController::class, 'updateRequestStatus'])
        ->middleware(['role:superadmin,admin,provider', 'permission:provider.requests.update'])
        ->name('provider.requests.status');
    Route::patch('/providers/{type}/delivery-routes/{deliveryRoute}/status', [ProviderPortalController::class, 'updateDeliveryRouteStatus'])
        ->middleware(['role:superadmin,admin,provider', 'permission:provider.requests.update'])
        ->name('provider.delivery-routes.status');
    Route::get('/unit', [UnitDashboardController::class, 'index'])
        ->middleware('role:superadmin,admin,institution,unit')
        ->name('unit.dashboard');
    Route::get('/unit/services/{contract}/report', [UnitDashboardController::class, 'downloadServiceReport'])
        ->middleware('role:superadmin,admin,institution,unit')
        ->name('unit.services.report');
    Route::post('/unit/nutrition-requests', [UnitDashboardController::class, 'storeNutritionRequest'])
        ->middleware(['role:superadmin,admin,institution,unit', 'permission:operational.service_requests.create'])
        ->name('unit.nutrition-requests.store');
    Route::post('/unit/appointments', [UnitDashboardController::class, 'storeAppointment'])
        ->middleware(['role:superadmin,admin,institution,unit', 'permission:outpatient.appointments.create'])
        ->name('unit.appointments.store');
    Route::patch('/unit/appointments/{appointment}', [UnitDashboardController::class, 'updateAppointment'])
        ->middleware(['role:superadmin,admin,institution,unit', 'permission:outpatient.appointments.update'])
        ->name('unit.appointments.update');
    Route::patch('/unit/appointments/{appointment}/status', [UnitDashboardController::class, 'updateAppointmentStatus'])
        ->middleware(['role:superadmin,admin,institution,unit', 'permission:unit.appointments.update'])
        ->name('unit.appointments.status');
    Route::patch('/unit/profile', [UnitDashboardController::class, 'updateProfile'])
        ->middleware(['role:superadmin,admin,institution,unit', 'permission:unit.profile.update'])
        ->name('unit.profile.update');
    Route::post('/unit/operational-users', [UnitDashboardController::class, 'storeOperationalUser'])->middleware(['role:superadmin,admin,institution,unit', 'permission:unit.operational_users.manage'])->name('unit.operational-users.store');
    Route::patch('/unit/operational-users/{profile}', [UnitDashboardController::class, 'updateOperationalUser'])->middleware(['role:superadmin,admin,institution,unit', 'permission:unit.operational_users.manage'])->name('unit.operational-users.update');
    Route::delete('/unit/operational-users/{profile}', [UnitDashboardController::class, 'destroyOperationalUser'])->middleware(['role:superadmin,admin,institution,unit', 'permission:unit.operational_users.manage'])->name('unit.operational-users.destroy');
    Route::post('/unit/doctors', [UnitDashboardController::class, 'storeDoctor'])->middleware(['role:superadmin,admin,institution,unit', 'permission:unit.doctors.manage'])->name('unit.doctors.store');
    Route::put('/unit/doctors/{doctor}/authorizations', [UnitDashboardController::class, 'updateDoctorAuthorizations'])->middleware(['role:superadmin,admin,institution,unit', 'permission:unit.doctors.manage'])->name('unit.doctors.authorizations.update');
    Route::delete('/unit/doctors/{doctor}', [UnitDashboardController::class, 'destroyDoctor'])->middleware(['role:superadmin,admin,institution,unit', 'permission:unit.doctors.manage'])->name('unit.doctors.destroy');
    Route::patch('/unit/external-pharmacy/{medication}/status', [UnitDashboardController::class, 'updateExternalPharmacyStatus'])->middleware(['role:superadmin,admin,institution,unit', 'permission:unit.external_pharmacy.update'])->name('unit.external-pharmacy.status');
    Route::patch('/unit/medications/{medication}/status', [UnitDashboardController::class, 'updateMedicationStatus'])->middleware(['role:superadmin,admin,institution,unit', 'permission:unit.medications.update'])->name('unit.medications.status');
    Route::post('/unit/procedure-areas', [UnitDashboardController::class, 'storeProcedureArea'])->middleware(['role:superadmin,admin,institution,unit', 'permission:unit.procedure_areas.manage'])->name('unit.procedure-areas.store');
    Route::put('/unit/procedure-areas/{areaId}', [UnitDashboardController::class, 'updateProcedureArea'])->middleware(['role:superadmin,admin,institution,unit', 'permission:unit.procedure_areas.manage'])->name('unit.procedure-areas.update');
    Route::get('/institution', [InstitutionDashboardController::class, 'index'])
        ->middleware('role:superadmin,admin,institution')
        ->name('institution.dashboard');
    Route::patch('/institution/units/{unit}/status', [InstitutionDashboardController::class, 'updateUnitStatus'])
        ->middleware(['role:superadmin,admin,institution', 'permission:institution.units.update'])
        ->name('institution.units.status');
    Route::post('/institution/services/{service}/units', [InstitutionDashboardController::class, 'syncServiceUnits'])
        ->middleware(['role:superadmin,admin,institution', 'permission:institution.services.manage'])
        ->name('institution.services.units.sync');
    Route::patch('/institution/services/{service}', [InstitutionDashboardController::class, 'updateService'])
        ->middleware(['role:superadmin,admin,institution', 'permission:institution.services.manage'])
        ->name('institution.services.update');
    Route::post('/institution/services', [InstitutionDashboardController::class, 'storeService'])
        ->middleware(['role:superadmin,admin,institution', 'permission:institution.services.manage'])
        ->name('institution.services.store');
    Route::post('/institution/medications', [InstitutionDashboardController::class, 'storeMedication'])
        ->middleware(['role:superadmin,admin,institution', 'permission:institution.medications.manage'])
        ->name('institution.medications.store');
    Route::patch('/institution/medications/{medication}', [InstitutionDashboardController::class, 'updateMedication'])
        ->middleware(['role:superadmin,admin,institution', 'permission:institution.medications.manage'])
        ->name('institution.medications.update');
    Route::patch('/institution/medications/{medication}/status', [InstitutionDashboardController::class, 'updateMedicationStatus'])
        ->middleware(['role:superadmin,admin,institution', 'permission:institution.medications.manage'])
        ->name('institution.medications.status');
    Route::post('/institution/specialties', [InstitutionDashboardController::class, 'storeSpecialty'])
        ->middleware(['role:superadmin,admin,institution', 'permission:institution.specialties.manage'])
        ->name('institution.specialties.store');
    Route::patch('/institution/specialties/{service}', [InstitutionDashboardController::class, 'updateSpecialty'])
        ->middleware(['role:superadmin,admin,institution', 'permission:institution.specialties.manage'])
        ->name('institution.specialties.update');
    Route::patch('/institution/specialties/{service}/status', [InstitutionDashboardController::class, 'updateSpecialtyStatus'])
        ->middleware(['role:superadmin,admin,institution', 'permission:institution.specialties.manage'])
        ->name('institution.specialties.status');
    Route::delete('/institution/specialties/{service}', [InstitutionDashboardController::class, 'destroySpecialty'])
        ->middleware(['role:superadmin,admin,institution', 'permission:institution.specialties.manage'])
        ->name('institution.specialties.destroy');
    Route::post('/institution/units', [InstitutionDashboardController::class, 'storeUnit'])
        ->middleware(['role:superadmin,admin,institution', 'permission:institution.units.create'])
        ->name('institution.units.store');
    Route::patch('/institution/units/{unit}', [InstitutionDashboardController::class, 'updateUnit'])
        ->middleware(['role:superadmin,admin,institution', 'permission:institution.units.update'])
        ->name('institution.units.update');
    Route::patch('/institution/units/{unit}/password', [InstitutionDashboardController::class, 'updateUnitPassword'])
        ->middleware(['role:superadmin,admin,institution', 'permission:institution.units.update'])
        ->name('institution.units.password');
    Route::get('/superadmin', [SuperAdminDashboardController::class, 'index'])
        ->middleware('role:superadmin')
        ->name('superadmin.dashboard');
    Route::get('/superadmin/catalogo/{section}', [SuperAdminDashboardController::class, 'catalog'])
        ->middleware('role:superadmin')
        ->name('superadmin.catalog');
    Route::get('/superadmin/catalogo/{section}/csv', [SuperAdminDashboardController::class, 'exportCatalog'])
        ->middleware('role:superadmin')
        ->name('superadmin.catalog.csv');
    Route::get('/superadmin/suscripciones/{plan}/usuarios.csv', [SuperAdminDashboardController::class, 'exportSubscriptionUsers'])
        ->middleware('role:superadmin')
        ->name('superadmin.subscriptions.users.csv');
    Route::patch('/superadmin/modules/{module}', [SuperAdminDashboardController::class, 'updateModule'])
        ->middleware(['role:superadmin', 'permission:superadmin.modules.update'])
        ->name('superadmin.modules.update');
    Route::patch('/superadmin/modules/{module}/details', [SuperAdminDashboardController::class, 'updateModuleDetails'])
        ->middleware(['role:superadmin', 'permission:superadmin.modules.update'])
        ->name('superadmin.modules.details');
    Route::patch('/superadmin/users/{user}', [SuperAdminDashboardController::class, 'updateUser'])
        ->middleware(['role:superadmin', 'permission:superadmin.users.update'])
        ->name('superadmin.users.update');
    Route::post('/superadmin/institutions', [SuperAdminDashboardController::class, 'storeInstitution'])
        ->middleware(['role:superadmin', 'permission:superadmin.catalogs.manage'])
        ->name('superadmin.institutions.store');
    Route::patch('/superadmin/institutions/{institution}', [SuperAdminDashboardController::class, 'updateInstitution'])
        ->middleware(['role:superadmin', 'permission:superadmin.catalogs.manage'])
        ->name('superadmin.institutions.update');
    Route::post('/superadmin/hospitals', [SuperAdminDashboardController::class, 'storeHospital'])
        ->middleware(['role:superadmin', 'permission:superadmin.catalogs.manage'])
        ->name('superadmin.hospitals.store');
    Route::patch('/superadmin/hospitals/{hospital}', [SuperAdminDashboardController::class, 'updateHospital'])
        ->middleware(['role:superadmin', 'permission:superadmin.catalogs.manage'])
        ->name('superadmin.hospitals.update');
    Route::post('/superadmin/doctors', [SuperAdminDashboardController::class, 'storeDoctor'])
        ->middleware(['role:superadmin', 'permission:superadmin.catalogs.manage'])
        ->name('superadmin.doctors.store');
    Route::patch('/superadmin/doctors/{doctor}', [SuperAdminDashboardController::class, 'updateDoctor'])
        ->middleware(['role:superadmin', 'permission:superadmin.catalogs.manage'])
        ->name('superadmin.doctors.update');
    Route::post('/superadmin/patients', [SuperAdminDashboardController::class, 'storePatient'])
        ->middleware(['role:superadmin', 'permission:superadmin.catalogs.manage'])
        ->name('superadmin.patients.store');
    Route::patch('/superadmin/patients/{patient}', [SuperAdminDashboardController::class, 'updatePatient'])
        ->middleware(['role:superadmin', 'permission:superadmin.catalogs.manage'])
        ->name('superadmin.patients.update');
    Route::post('/superadmin/providers', [SuperAdminDashboardController::class, 'storeProvider'])
        ->middleware(['role:superadmin', 'permission:superadmin.catalogs.manage'])
        ->name('superadmin.providers.store');
    Route::patch('/superadmin/providers/{provider}', [SuperAdminDashboardController::class, 'updateProvider'])
        ->middleware(['role:superadmin', 'permission:superadmin.catalogs.manage'])
        ->name('superadmin.providers.update');
    Route::post('/superadmin/insurance-carriers', [SuperAdminDashboardController::class, 'storeInsuranceCarrier'])
        ->middleware(['role:superadmin', 'permission:superadmin.catalogs.manage'])
        ->name('superadmin.insurance-carriers.store');
    Route::patch('/superadmin/insurance-carriers/{carrier}', [SuperAdminDashboardController::class, 'updateInsuranceCarrier'])
        ->middleware(['role:superadmin', 'permission:superadmin.catalogs.manage'])
        ->name('superadmin.insurance-carriers.update');
    Route::post('/superadmin/insurance-advisors', [SuperAdminDashboardController::class, 'storeInsuranceAdvisor'])
        ->middleware(['role:superadmin', 'permission:superadmin.catalogs.manage'])
        ->name('superadmin.insurance-advisors.store');
    Route::patch('/superadmin/insurance-advisors/{advisor}', [SuperAdminDashboardController::class, 'updateInsuranceAdvisor'])
        ->middleware(['role:superadmin', 'permission:superadmin.catalogs.manage'])
        ->name('superadmin.insurance-advisors.update');
    Route::post('/superadmin/settings/{section}', [SuperAdminDashboardController::class, 'updateSectionSettings'])
        ->middleware(['role:superadmin', 'permission:superadmin.settings.update'])
        ->name('superadmin.settings.update');
    Route::get('/insurance-advisor', [InsuranceAdvisorController::class, 'index'])
        ->middleware('role:superadmin,admin,insurance_advisor')
        ->name('insurance-advisor.dashboard');
    Route::get('/insurance-advisor/policies/export', [InsuranceAdvisorController::class, 'exportPolicies'])
        ->middleware('role:superadmin,admin,insurance_advisor')
        ->name('insurance-advisor.policies.export');
    Route::post('/insurance-advisor/alerts/sync', [InsuranceAdvisorController::class, 'syncAlerts'])
        ->middleware(['role:superadmin,admin,insurance_advisor', 'permission:insurance_advisor.policies.update'])
        ->name('insurance-advisor.alerts.sync');
    Route::post('/insurance-advisor/policies/sync', [InsuranceAdvisorController::class, 'bulkPolicySync'])
        ->middleware(['role:superadmin,admin,insurance_advisor', 'permission:insurance_advisor.policies.update'])
        ->name('insurance-advisor.policies.sync.bulk');
    Route::post('/insurance-advisor/policies/{policy}/sync', [InsuranceAdvisorController::class, 'requestPolicySync'])
        ->middleware(['role:superadmin,admin,insurance_advisor', 'permission:insurance_advisor.policies.update'])
        ->name('insurance-advisor.policies.sync');
    Route::post('/insurance-advisor/policies/{policy}/messages', [InsuranceAdvisorController::class, 'sendPolicyMessage'])
        ->middleware(['role:superadmin,admin,insurance_advisor', 'permission:insurance_advisor.policies.update'])
        ->name('insurance-advisor.policies.messages.store');
    Route::patch('/insurance-advisor/policies/{policy}/status', [InsuranceAdvisorController::class, 'updatePolicyStatus'])
        ->middleware(['role:superadmin,admin,insurance_advisor', 'permission:insurance_advisor.policies.update'])
        ->name('insurance-advisor.policies.status');
    Route::post('/insurance-advisor/policies/{policy}/renew', [InsuranceAdvisorController::class, 'renewPolicy'])
        ->middleware(['role:superadmin,admin,insurance_advisor', 'permission:insurance_advisor.policies.update'])
        ->name('insurance-advisor.policies.renew');
    Route::post('/insurance-advisor/claims', [InsuranceAdvisorController::class, 'storeClaim'])
        ->middleware(['role:superadmin,admin,insurance_advisor', 'permission:insurance_advisor.policies.update'])
        ->name('insurance-advisor.claims.store');
    Route::post('/insurance-advisor/policies/{policy}/claims/{claim}/documents', [InsuranceAdvisorController::class, 'uploadClaimDocument'])
        ->middleware(['role:superadmin,admin,insurance_advisor', 'permission:insurance_advisor.policies.update'])
        ->name('insurance-advisor.claims.documents.store');
    Route::post('/insurance-advisor/policies/{policy}/claims/{claim}/request-documents', [InsuranceAdvisorController::class, 'requestClaimDocuments'])
        ->middleware(['role:superadmin,admin,insurance_advisor', 'permission:insurance_advisor.policies.update'])
        ->name('insurance-advisor.claims.documents.request');
    Route::post('/insurance-advisor/policies/{policy}/claims/{claim}/hospital-discharge', [InsuranceAdvisorController::class, 'processClaimDischarge'])
        ->middleware(['role:superadmin,admin,insurance_advisor', 'permission:insurance_advisor.policies.update'])
        ->name('insurance-advisor.claims.discharge');
    Route::post('/insurance-advisor/policies/{policy}/claims/{claim}/follow-up', [InsuranceAdvisorController::class, 'storeClaimFollowUp'])
        ->middleware(['role:superadmin,admin,insurance_advisor', 'permission:insurance_advisor.policies.update'])
        ->name('insurance-advisor.claims.follow-up');
    Route::post('/insurance-advisor/policies/{policy}/claims/{claim}/send-insurer', [InsuranceAdvisorController::class, 'sendClaimToInsurer'])
        ->middleware(['role:superadmin,admin,insurance_advisor', 'permission:insurance_advisor.policies.update'])
        ->name('insurance-advisor.claims.send-insurer');
    Route::post('/insurance-advisor/policies/{policy}/claims/{claim}/quotations', [InsuranceAdvisorController::class, 'storeClaimQuotation'])
        ->middleware(['role:superadmin,admin,insurance_advisor', 'permission:insurance_advisor.policies.update'])
        ->name('insurance-advisor.claims.quotations.store');

    Route::prefix('insurance')
        ->name('insurance.')
        ->middleware('role:superadmin,admin,insurance_admin,medical_auditor,patient_coordinator,delivery_coordinator,hospital_coordinator,billing,read_only,insurance_advisor')
        ->group(function (): void {
            Route::get('/', InsuranceDashboardController::class)->name('dashboard');
            Route::resource('patients', InsurancePatientController::class)->except(['destroy']);
            Route::delete('patients/{patient}', [InsurancePatientController::class, 'destroy'])->name('patients.destroy');

            Route::post('diagnoses', [InsurancePatientDiagnosisController::class, 'store'])->name('diagnoses.store');
            Route::post('treatments', [InsuranceTreatmentController::class, 'store'])->name('treatments.store');

            Route::get('deliveries', [InsuranceMedicationDeliveryController::class, 'index'])->name('deliveries.index');
            Route::post('deliveries', [InsuranceMedicationDeliveryController::class, 'store'])->name('deliveries.store');
            Route::patch('deliveries/{delivery}/status', [InsuranceMedicationDeliveryController::class, 'updateStatus'])->name('deliveries.status');

            Route::get('hospitalizations', [InsuranceHospitalizationController::class, 'index'])->name('hospitalizations.index');
            Route::post('hospitalizations', [InsuranceHospitalizationController::class, 'store'])->name('hospitalizations.store');
            Route::get('hospitalizations/{hospitalization}', [InsuranceHospitalizationController::class, 'show'])->name('hospitalizations.show');
            Route::post('hospitalization-notes', [InsuranceHospitalizationDailyNoteController::class, 'store'])->name('hospitalization-notes.store');

            Route::get('invoices', [InsuranceInvoiceController::class, 'index'])->name('invoices.index');
            Route::post('invoices', [InsuranceInvoiceController::class, 'store'])->name('invoices.store');

            Route::get('authorizations', [InsuranceAuthorizationController::class, 'index'])->name('authorizations.index');
            Route::post('authorizations', [InsuranceAuthorizationController::class, 'store'])->name('authorizations.store');

            Route::get('documents', [InsuranceDocumentController::class, 'index'])->name('documents.index');
            Route::post('documents', [InsuranceDocumentController::class, 'store'])->name('documents.store');

            Route::get('reports', [InsuranceReportController::class, 'index'])->name('reports.index');
            Route::get('admin/users', [InsuranceAdminController::class, 'users'])->name('admin.users');
        });
});


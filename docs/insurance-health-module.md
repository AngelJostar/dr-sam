# Modulo Aseguradora Salud

## Arquitectura

El modulo se implementa como una extension nativa Laravel bajo el prefijo `/insurance`.
Reutiliza `users`, `patients`, `doctors`, `providers`, `prescriptions`, `medical_units` y `audit_logs`.
Las tablas nuevas cubren polizas, padecimientos cronicos, tratamientos, entregas, hospitales, hospitalizaciones, bitacoras diarias, autorizaciones, facturacion, documentos, roles y permisos.

## Instalacion

1. Configurar `.env` con la base de datos del proyecto.
2. Ejecutar migraciones: `php artisan migrate`.
3. Cargar datos demo y catalogos: `php artisan db:seed`.
4. Entrar con un usuario demo, por ejemplo `aseguradora.admin`, y abrir el modulo `Aseguradora salud`.

## Rutas principales

- `/insurance`: dashboard operativo.
- `/insurance/patients`: pacientes asegurados.
- `/insurance/patients/{patient}`: expediente integral.
- `/insurance/deliveries`: entregas de medicamentos.
- `/insurance/hospitalizations`: hospitalizaciones.
- `/insurance/invoices`: facturacion hospitalaria.
- `/insurance/authorizations`: autorizaciones.
- `/insurance/documents`: control documental.
- `/insurance/reports`: reportes.
- `/insurance/admin/users`: usuarios, roles y permisos.

## API REST

Todas las rutas API requieren sesion autenticada:

- `GET /api/insurance/dashboard`
- `GET /api/insurance/reports`
- `GET /api/insurance/patients`
- `POST /api/insurance/patients`
- `GET /api/insurance/patients/{patient}`
- `POST /api/insurance/diagnoses`
- `POST /api/insurance/treatments`
- `POST /api/insurance/deliveries`
- `PATCH /api/insurance/deliveries/{delivery}`
- `POST /api/insurance/hospitalizations`
- `POST /api/insurance/hospitalization-notes`
- `POST /api/insurance/invoices`
- `POST /api/insurance/authorizations`
- `POST /api/insurance/documents`

## Roles

- `insurance_admin`: gestiona todo el modulo.
- `medical_auditor`: consulta pacientes, tratamientos, hospitalizaciones y autorizaciones.
- `patient_coordinator`: alta y edicion de pacientes, diagnosticos y tratamientos.
- `delivery_coordinator`: entregas de medicamentos.
- `hospital_coordinator`: hospitalizaciones y autorizaciones hospitalarias.
- `billing`: facturas y pagos.
- `read_only`: consulta sin edicion.

## Bitacora y privacidad

Las acciones de creacion, actualizacion y desactivacion registran eventos en `audit_logs`.
La bitacora evita guardar valores clinicos completos: registra modulo, entidad, id, usuario, fecha, IP y campos modificados.
La eliminacion de pacientes usa `deleted_at` y cambia el estatus a `discharged`.

## Extension futura

- Integrar `Treatment` con recetas reales del modulo medico mediante `prescription_id`.
- Ligar `MedicationDelivery` con rutas de mensajeria y farmacia digital.
- Sustituir carga local de documentos por almacenamiento privado S3 o similar.
- Agregar jobs programados para alertas por correo o tablero operativo.
- Crear exports CSV/XLSX para reportes cuando se agregue una libreria de hojas de calculo.
- Migrar permisos de string `users.role` a asignacion relacional si se requiere multirol por usuario.

# Dr. Sam Laravel Platform

## Instalación para colaboradores y uso con Codex

La guía completa para clonar el repositorio, importar `dr_sam.sql`, ejecutar Laravel en Windows/Laragon y trabajar de forma segura con Codex está en el [manual de instalación y trabajo con Codex](docs/MANUAL_INSTALACION_CODEX.md).

Inicio rápido después de clonar:

```powershell
composer install
Copy-Item .env.example .env
php artisan key:generate
mysql -u root -e "CREATE DATABASE IF NOT EXISTS dr_sam CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
cmd /c "mysql -u root dr_sam < dr_sam.sql"
php artisan migrate
php artisan optimize:clear
php artisan serve --host=127.0.0.1 --port=8000
```

No ejecutes `migrate:fresh` después de importar el respaldo incluido.

Plataforma médica modular desarrollada con Laravel 12, Blade y MySQL. Incluye permisos por acción, control de acceso por entidad, auditoría y pruebas automatizadas.

## Modulo Aseguradora Salud

El proyecto incluye un modulo Laravel nativo para aseguradoras de salud en `/insurance`.
Administra pacientes asegurados ligados a usuarios de plataforma, padecimientos cronico-degenerativos, tratamientos, entregas de medicamentos, hospitalizaciones, bitacoras diarias, facturacion hospitalaria, documentos, autorizaciones, reportes, roles y bitacora de cambios.

Documentacion tecnica y de uso: [docs/insurance-health-module.md](docs/insurance-health-module.md).

La plataforma centraliza:

- Acceso centralizado de usuarios por rol y modulo.
- Revision temporal sin contrasena.
- Base de datos unica para instituciones, unidades, servicios, pacientes, medicos, proveedores, farmacia, pedidos y mensajeria.
- Permisos finos por accion, ownership por entidad y auditoria de cambios criticos.

Modulos nativos actuales:

- Superadministrador: `/superadmin`
- Institucion: `/institution`
- Unidad medica: `/unit`
- Area operativa: `/operational`
- Farmacia digital: `/pharmacy`
- Farmacia externa: `/external-pharmacy`
- Paciente: `/patient`
- Pedidos paciente: `/orders`
- Medico: `/doctor`
- Proveedores NPT e importación: `/providers/npt`, `/providers/import`
- Mensajeria: `/messenger`
- Aseguradora salud: `/insurance`
- Asesor de seguros GMM: `/insurance-advisor`

## Instalación

Para la primera instalación sigue el [manual para colaboradores](docs/MANUAL_INSTALACION_CODEX.md). El procedimiento incluye requisitos, descarga, configuración, importación de `dr_sam.sql`, ejecución, pruebas y trabajo asistido con Codex.

## Acceso libre de revision

La variable `DRSAM_REVIEW_PASSWORDLESS=true` deja activo el acceso sin contrasena. En la pantalla inicial solo se elige el usuario y se entra al modulo.

Usuarios incluidos:

- `superadmin`
- `admin`
- `institucion`
- `angeles`
- `imss.cdmx`
- `imss.bienestar`
- `unidad.demo`
- `op.enfermeria`
- `op.farmacia`
- `op.farmacia.externa`
- `op.oncologia`
- `op.consulta`
- `farmacia.digital`
- `proveedor`
- `proveedor.quimioterapias`
- `proveedor.importacion`
- `mensajero`
- `jimmy.carter`
- `paciente`
- `asesor.seguros`
- `aseguradora.admin`
- `aseguradora.auditor`
- `aseguradora.pacientes`
- `aseguradora.entregas`
- `aseguradora.hospital`
- `aseguradora.facturacion`
- `aseguradora.consulta`

Cuando se pase a produccion:

```env
DRSAM_REVIEW_PASSWORDLESS=false
```

El sistema ya impide activar `DRSAM_REVIEW_PASSWORDLESS=true` en produccion.

## Estructura

- `app/Enums`: roles normalizados.
- `app/Models`: modelos Eloquent de la base unificada.
- `app/Http/Controllers/Auth`: acceso demo y cierre de sesion.
- `app/Services/Platform`: registro de modulos, navegacion, permisos por accion y auditoria.
- `database/migrations`: esquema relacional consolidado.
- `database/seeders`: usuarios y datos demo de revision.

## Endurecimiento aplicado

- Permisos por accion con middleware `permission`.
- Auditoria uniforme con `PlatformAuditService`.
- Ownership en acciones sensibles de institucion, unidad, operativa y mensajeria.
- Navegacion global por modulos permitidos.
- Seed demo cubierto por pruebas.

# Dr. Sam Laravel Platform

Base modular Laravel para Dr. Sam con pantallas Blade nativas, MySQL, permisos por accion, auditoria y bloqueo del legacy publico.

## Modulo Aseguradora Salud

El proyecto incluye un modulo Laravel nativo para aseguradoras de salud en `/insurance`.
Administra pacientes asegurados ligados a usuarios de plataforma, padecimientos cronico-degenerativos, tratamientos, entregas de medicamentos, hospitalizaciones, bitacoras diarias, facturacion hospitalaria, documentos, autorizaciones, reportes, roles y bitacora de cambios.

Documentacion tecnica y de uso: [docs/insurance-health-module.md](docs/insurance-health-module.md).

La plataforma ya no usa los HTML legacy como destino de modulos. El acceso principal esta resuelto con Laravel Blade para:

- Acceso centralizado de usuarios por rol y modulo.
- Revision temporal sin contrasena.
- Base de datos unica para instituciones, unidades, servicios, pacientes, medicos, proveedores, farmacia, pedidos y mensajeria.
- Importacion de catalogos heredados desde los archivos JS/JSON actuales.
- Permisos finos por accion, ownership por entidad y auditoria de cambios criticos.
- Bloqueo del puente y archivos legacy publicos por defecto.

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
- Proveedores NPT/quimioterapia/importacion: `/providers/npt`, `/providers/chemotherapy`, `/providers/import`
- Mensajeria: `/messenger`
- Aseguradora salud: `/insurance`
- Asesor de seguros GMM: `/insurance-advisor`

## Instalacion sugerida

Desde esta carpeta:

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate --seed
php artisan serve
```

Configuracion MySQL recomendada para Laragon/local:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=dr_sam
DB_USERNAME=root
DB_PASSWORD=
```

Para validar la instalacion:

```bash
php artisan test
```

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
DRSAM_LEGACY_BRIDGE_ENABLED=false
```

El sistema ya impide activar `DRSAM_REVIEW_PASSWORDLESS=true` en produccion.

## Legacy bloqueado

Los archivos heredados permanecen en `public/legacy` solo como referencia/importacion, pero el acceso web directo queda bloqueado por `.htaccess`.

El puente `/legacy/open/{module}` tambien esta deshabilitado por defecto mediante:

```env
DRSAM_LEGACY_BRIDGE_ENABLED=false
```

Si en una revision controlada se requiere abrir una pantalla antigua, se debe habilitar explicitamente esa variable y revisar que el modulo tenga un target legacy configurado.

## Importacion de datos heredados

Para cargar catalogos desde el prototipo actual:

```bash
php artisan drsam:import-legacy --root="../"
```

El importador reconoce:

- `medical-units.js`
- `private-hospitals-catalog.js`
- `medication-catalog.js`
- `farmacia-catalogo-imss-bienestar.js`
- `med_catalog_final.json`

Los datos que todavia no tengan tabla especifica se pueden conservar en `legacy_payloads` para no perder informacion durante la migracion.

## Estructura

- `app/Enums`: roles normalizados.
- `app/Models`: modelos Eloquent de la base unificada.
- `app/Http/Controllers/Auth`: acceso demo y cierre de sesion.
- `app/Http/Controllers/Legacy`: puente legacy deshabilitado por defecto.
- `app/Services/LegacyData`: lectura e importacion de JS/JSON heredados.
- `app/Services/Platform`: registro de modulos, navegacion, permisos por accion y auditoria.
- `database/migrations`: esquema relacional consolidado.
- `database/seeders`: usuarios y datos demo de revision.

## Endurecimiento aplicado

- Permisos por accion con middleware `permission`.
- Auditoria uniforme con `PlatformAuditService`.
- Ownership en acciones sensibles de institucion, unidad, operativa y mensajeria.
- Navegacion global por modulos permitidos.
- Bloqueo de legacy publico.
- Seed demo cubierto por pruebas.

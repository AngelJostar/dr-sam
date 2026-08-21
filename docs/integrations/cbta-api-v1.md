# Integracion Dr. Sam - Mezclas/CBTA v1

Este contrato implementa el primer bloque del plan de integracion. CBTA conserva la propiedad de hospitales operativos, listas, catalogos, presentaciones, precios e inventario. Dr. Sam consume esos datos y conserva solamente referencias externas y datos de sincronizacion.

## Primer flujo habilitado

1. CBTA asigna `external_code` estable a hospitales, productos y presentaciones.
2. Un administrador de CBTA emite un token tecnico con la capacidad `catalogs:read`.
3. Dr. Sam consulta el catalogo correspondiente a la unidad autorizada.
4. Dr. Sam muestra y guarda `product_code`, `presentation_code` y `catalog_version`; nunca utiliza IDs internos de CBTA.

## Configuracion

En CBTA:

```powershell
php artisan migrate
php artisan integration:issue-drsam-token drsam.integration --provision
```

En `.env` de Dr. Sam:

```dotenv
CBTA_BASE_URL=http://127.0.0.1:8080
CBTA_API_TOKEN=TOKEN_EMITIDO_POR_CBTA
CBTA_API_TIMEOUT=10
CBTA_API_CONNECT_TIMEOUT=3
```

En desarrollo, el puerto debe ser distinto al utilizado por Legacy. En el entorno local actual CBTA se ejecuta en `http://127.0.0.1:8082`.

La instancia local destinada a esta integracion usa la base aislada `cbta_api_db`. Se crea sin copiar datos de `cbta_db` y se carga con:

```powershell
php artisan migrate
php artisan db:seed --class=MixturesDemoSeeder
```

El seeder crea la unidad externa `DRSAM-DEMO`, tres productos NPT, dos productos oncologicos, existencias de prueba y solicitudes en distintos estados. Tambien crea el usuario visual `hospital.demo`; el token tecnico para Dr. Sam se emite por separado con `integration:issue-drsam-token`.

El token es un secreto. No debe almacenarse en Git, capturas, logs ni payloads.

## Endpoints implementados en CBTA

### Catalogo NPT

`GET /api/internal/v1/medical-units/{externalCode}/catalogs/npt`

### Catalogo oncologico

`GET /api/internal/v1/medical-units/{externalCode}/catalogs/oncology`

Encabezados:

```http
Accept: application/json
Authorization: Bearer <token>
```

Respuesta base:

```json
{
  "data": {
    "medical_unit": {
      "external_code": "HOSP-000001",
      "name": "Hospital autorizado"
    },
    "catalog_type": "npt",
    "catalog_version": "2026-08-05T12:00:00-06:00",
    "items": [
      {
        "product_code": "NPT-CAT-000001",
        "presentation_code": "NPT-PRES-000001"
      }
    ]
  },
  "meta": {
    "count": 1
  }
}
```

## Errores esperados

- `401`: token ausente, invalido o revocado.
- `403`: token sin capacidad `catalogs:read`.
- `404`: unidad inexistente, inactiva o sin codigo externo reconocido.
- `422`: se reservara para parametros de consulta invalidos.
- `500`: error interno; la respuesta no debe exponer credenciales ni trazas.

## Prevalidacion de solicitudes

`POST /api/internal/v1/mixture-requests/prevalidate`

Este endpoint requiere la capacidad `requests:prevalidate`. Comprueba que la unidad este activa, que producto y presentacion pertenezcan a su lista vigente, que la unidad de medida sea compatible, que la version del catalogo no sea obsoleta y que exista inventario suficiente. No crea solicitudes, no reserva ni descuenta existencias y no genera movimientos.

Solicitud NPT de ejemplo:

```json
{
  "medical_unit_code": "HOSP-000001",
  "catalog_type": "npt",
  "catalog_version": "2026-08-05T12:00:00-06:00",
  "items": [
    {
      "product_code": "NPT-CAT-000001",
      "presentation_code": "NPT-PRES-000001",
      "quantity": 100,
      "unit": "ml"
    }
  ]
}
```

Para oncologia, `unit` admite `mg` o `unit`. Cuando se envian miligramos, CBTA calcula los frascos requeridos usando el contenido de la presentacion.

Una validacion de negocio responde `200`, incluso cuando `data.valid` es `false`; el detalle queda en `data.errors`. Los errores estructurales del payload responden `422`.

Respuesta resumida:

```json
{
  "data": {
    "valid": true,
    "catalog_version": "2026-08-05T12:00:00-06:00",
    "items": [
      {
        "presentation_code": "NPT-PRES-000001",
        "valid": true,
        "available_quantity": 32000,
        "availability_unit": "ml"
      }
    ],
    "errors": []
  },
  "meta": {
    "mutated_inventory": false,
    "created_request": false
  }
}
```

## Persistencia en Dr. Sam

Cada registro de `medical_units` dispone de `cbta_external_code`. Esta referencia es la unica que debe usarse para consultar los catalogos operativos; los IDs internos de cualquiera de los dos sistemas no se comparten.

Antes de vincular una unidad, se puede verificar la comunicacion:

```powershell
php artisan cbta:check-catalog HOSP-000001 --type=npt
php artisan cbta:check-catalog HOSP-000001 --type=oncology
```

La vinculacion valida ambos catalogos remotos antes de persistir el codigo:

```powershell
php artisan cbta:map-unit DRSAM-DEMO HOSP-000001
```

El segundo comando solo debe ejecutarse cuando se haya confirmado administrativamente que ambas unidades representan al mismo hospital.

La tabla `mixture_integrations` relaciona cada `provider_request` con:

- UUID local usado como clave de idempotencia futura.
- Folio remoto de CBTA.
- estado remoto y estado de sincronizacion;
- version del catalogo usado;
- hash del payload y ultimo error;
- fecha de ultima sincronizacion y metadatos no sensibles.

## Formulario real de mezclas

Las solicitudes NPT y oncologicas de una unidad vinculada mediante `cbta_external_code` consultan el catalogo operativo de CBTA. El formulario envia el producto, la presentacion, la cantidad, la unidad y la version exacta del catalogo que vio el medico.

Antes de guardar una solicitud, Dr. Sam ejecuta la prevalidacion remota. El resultado se maneja de forma atomica:

- Si CBTA acepta la prevalidacion, Dr. Sam crea el `provider_request` y su `mixture_integration` con estado `awaiting_authorizations` dentro de la misma transaccion.
- Si CBTA rechaza la solicitud por catalogo, presentacion, unidad o inventario, no se crea ninguno de los dos registros.
- Si CBTA no esta disponible, tampoco se crea una solicitud parcial y el formulario informa que debe intentarse nuevamente.
- La prevalidacion no crea aun una solicitud en CBTA y no reserva ni descuenta inventario.

Las unidades sin `cbta_external_code` conservan temporalmente el flujo local anterior. Esto permite habilitar la integracion hospital por hospital despues de validar la correspondencia de codigos y catalogos.

## Autorizaciones y creacion definitiva

La prevalidacion inicial no envia la solicitud a Mezclas. Dr. Sam espera las autorizaciones internas requeridas: Enfermeria y Farmacia Intrahospitalaria para NPT; Centro Oncologico y Farmacia Intrahospitalaria para oncologia. Cuando ambas quedan autorizadas, Dr. Sam vuelve a prevalidar catalogo, presentacion e inventario y solamente entonces crea la solicitud remota.

La creacion definitiva usa `POST /api/internal/v1/mixture-requests` y requiere `requests:create`. `local_external_id` funciona como clave de idempotencia: repetir exactamente el mismo payload devuelve el mismo `request_id`; reutilizar la clave con contenido diferente devuelve conflicto `409`.

CBTA registra primero la solicitud externa con estado `received` y luego la materializa de forma idempotente en sus tablas operativas NPT u oncologicas. La materializacion crea la solicitud y sus renglones clinicos, pero no reserva ni descuenta inventario. El consumo sigue ocurriendo en la aprobacion operativa de Mezclas y queda respaldado por sus movimientos de inventario. La respuesta de estado distingue `inventory.stage=validated` de `inventory.stage=consumed`, incluyendo cantidad y numero de movimientos. Dr. Sam conserva esa trazabilidad en `mixture_integrations.metadata.remote_status_details`.

Si la creacion remota falla despues de guardar la solicitud clinica local, la integracion queda en `failed` con el error resumido. Puede reintentarse sin duplicar solicitudes mediante:

```powershell
php artisan cbta:sync-mixtures
php artisan cbta:sync-mixtures --id=15
```

Cuando ya existe `cbta_request_id`, el mismo comando consulta `GET /api/internal/v1/mixture-requests/{request_id}` y concilia el estado.

Dr. Sam ejecuta esta conciliacion cada minuto mediante el programador de Laravel. En CBTA, las solicitudes que quedaron en `received` o `materialization_failed` se reintentan tambien cada minuto y pueden procesarse manualmente con:

```powershell
php artisan integration:materialize-mixtures
php artisan integration:materialize-mixtures --id=15
```

Ambos procesos usan bloqueo e idempotencia para que los reintentos no dupliquen solicitudes, mezclas ni renglones de medicamentos. Para que el programador se ejecute en un servidor debe existir un cron que invoque `php artisan schedule:run` cada minuto; durante desarrollo puede usarse `php artisan schedule:work` en cada proyecto.

## Conciliacion de estados

Los estados operativos se concilian automaticamente desde CBTA y se traducen a un contrato estable:

| Estado API | Estado de la solicitud en Dr. Sam | Significado |
| --- | --- | --- |
| `pending` | `requested` | Recibida y pendiente de autorizacion |
| `authorized` | `accepted` | Autorizada para preparacion |
| `preparing` | `preparing` | En preparacion |
| `ready` | `ready` | Preparada y revisada |
| `delivered` | `delivered` | Entregada |
| `cancelled` | `cancelled` | Cancelada operativamente |
| `rejected` | `rejected` | No aprobada o rechazada |

Cada cambio crea un solo evento en `provider_request_status_events` con actor `cbta.integration`. El detalle original de CBTA (estado fuente, estados de mezclas y remision) queda en `mixture_integrations.metadata.remote_status_details`, lo que permite auditar la traduccion sin acoplar Dr. Sam a los nombres internos de CBTA.

## Documentos y remisiones

La respuesta de consulta incluye el folio de remision cuando CBTA lo haya generado. Dr. Sam conserva esa informacion en `provider_requests.payload.cbta.remission` y la presenta en el historial de solicitudes. La remision estructurada contiene `number`, `issued_at`, `currency`, `subtotal`, `total` e `items`. Cada renglon informa producto, presentacion, cantidad, unidad, tipo de precio, precio unitario e importe. Solicitudes antiguas que no tengan este desglose siguen siendo compatibles y se muestran sin importes hasta su siguiente conciliacion.

Los archivos de autorizacion se transfieren por un endpoint separado:

`POST /api/internal/v1/mixture-requests/{request_id}/documents`

Este endpoint requiere `requests:documents`, acepta PDF, JPG o PNG hasta 10 MB y almacena el archivo en el disco privado de CBTA. La combinacion solicitud, tipo y SHA-256 hace la carga idempotente. Las respuestas nunca exponen la ruta fisica del servidor; solo devuelven identificador, nombre, tipo MIME, tamano, hash y fecha de carga.

Dr. Sam reintenta la transferencia junto con la conciliacion normal. Si el documento ya existe con el mismo hash, CBTA devuelve el registro existente sin crear otra copia.

La descarga usa el endpoint privado:

`GET /api/internal/v1/mixture-requests/{request_id}/documents/{document_id}`

CBTA valida la capacidad `requests:documents`, que el documento pertenezca a la solicitud indicada y que el archivo siga disponible en almacenamiento privado. Dr. Sam no entrega el token ni una URL interna al navegador: expone una ruta autenticada propia, comprueba que la solicitud pertenezca al medico y actua como proxy de descarga con `no-store` y proteccion `nosniff`.

Cuando el estado remoto informa una remision disponible, Dr. Sam habilita su descarga mediante su propio proxy autenticado. El proxy consulta:

`GET /api/internal/v1/mixture-requests/{request_id}/remission`

CBTA resuelve la solicitud materializada y genera el PDF con el mismo generador oficial utilizado por sus modulos NPT u oncologico. Por lo tanto, folio, productos, presentaciones, precios y datos hospitalarios no se duplican ni se calculan en Dr. Sam.

## Webhooks firmados

Mezclas notifica los cambios de estado a:

`POST /api/integrations/cbta/mixture-status`

El aviso incluye `event_id`, `event_type`, `request_id`, `status`, `source_updated_at` y `event_at`, y se firma con HMAC SHA-256 mediante `X-CBTA-Timestamp` y `X-CBTA-Signature`. Dr. Sam rechaza firmas invalidas y avisos vencidos. Una vez validado el aviso, Dr. Sam consulta el endpoint autenticado de Mezclas y concilia la respuesta oficial; no toma el estado del webhook como fuente de verdad. El ultimo evento recibido queda identificado en los metadatos de la integracion para auditoria.

CBTA guarda cada aviso en `external_mixture_webhook_deliveries` antes de intentar enviarlo. Un error de red o una respuesta HTTP no exitosa conserva el payload, la cantidad de intentos, el error, el codigo HTTP y la proxima fecha de entrega. El comando programado `integration:retry-webhooks` usa espera exponencial y reenvia exactamente el mismo `event_id`; una transicion ya entregada no se vuelve a enviar.

Dr. Sam aplica el mismo criterio a la conciliacion saliente. Las integraciones fallidas guardan `sync_attempts` y `next_retry_at`; el proceso automatico respeta una espera exponencial y deja de insistir al llegar al limite configurado. El comando con `--id` permite una recuperacion manual inmediata despues de corregir la causa:

```powershell
php artisan cbta:sync-mixtures --id=15
```

Configuracion en Dr. Sam:

```dotenv
CBTA_WEBHOOK_SECRET=UN_SECRETO_LARGO_COMPARTIDO
CBTA_WEBHOOK_TOLERANCE=300
CBTA_MAX_SYNC_ATTEMPTS=10
CBTA_MAX_RETRY_DELAY_MINUTES=60
```

Configuracion en Mezclas/CBTA:

```dotenv
DR_SAM_WEBHOOK_URL=http://127.0.0.1:8000/api/integrations/cbta/mixture-status
DR_SAM_WEBHOOK_SECRET=EL_MISMO_SECRETO_COMPARTIDO
DR_SAM_WEBHOOK_TIMEOUT=5
DR_SAM_WEBHOOK_CONNECT_TIMEOUT=3
DR_SAM_WEBHOOK_MAX_ATTEMPTS=8
```

El sondeo programado con `cbta:sync-mixtures` se conserva como segundo mecanismo de recuperacion cuando Dr. Sam no esta disponible al momento del aviso. En CBTA tambien puede forzarse el reenvio de eventos vencidos con `php artisan integration:retry-webhooks`.

# Publicación del catálogo universal en farmacia digital

## Administración

- Catálogo universal y Farmacia digital: columna **Mostrar en farmacia digital** con switch ON/OFF. Guarda sin navegar ni mover la tabla.
- **Estatus** abre un diálogo con Activo, Inactivo, Desabasto y Descontinuado.
- Se publica el medicamento cuando tiene ON y está Activo; se conservan las restricciones existentes de productos controlados y cadena fría. La tienda debe estar habilitada.
- OFF oculta el medicamento y todas sus ofertas vinculadas de las respuestas públicas, las imágenes y nuevas solicitudes. No elimina productos ni modifica pedidos históricos.
- La migración `2026_11_12_000007_link_universal_catalog_to_digital_pharmacy.php` es aditiva. Los medicamentos existentes comienzan en OFF. Las ofertas anteriores sin vínculo se conservan con sus reglas existentes; no se enlazan por coincidencias ambiguas de nombres.

## Distribuidores

En **Catálogo de farmacia digital** (`/providers/medicines/pharmacy-catalog`) aparecen los mismos medicamentos publicados por Klini. El distribuidor selecciona un producto y guarda precio, stock, marca/laboratorio y ON/OFF de su oferta. Hay una sola oferta por distribuidor y presentación del catálogo universal; guardar nuevamente la actualiza.

Las ofertas se agregan a Catálogo propio, Inventario y Tienda digital. La identidad, descripción y restricciones del producto provienen del catálogo universal; el distribuidor conserva control de precio, existencias, imágenes y estado de su oferta. Solo se publican ofertas de distribuidores autorizados y cuentas activas.

En **Configuración** se registran dirección pública, latitud y longitud del punto de atención. No se inventan ubicaciones. Las coordenadas deben capturarse juntas.

## Contrato para la interfaz móvil

`GET /api/mobile/v1/pharmacy/catalog` conserva `data.products` (ofertas antiguas, compatible con clientes existentes) y agrega `data.catalog_items` (hasta 100 medicamentos con sus ofertas).

`GET /api/mobile/v1/pharmacy/catalog-items` devuelve el catálogo completo paginado de 24 en 24. Admite `q`, `category`, `brand`, `page`, `latitude` y `longitude`. Los filtros se aplican antes de paginar. La categoría se obtiene del grupo terapéutico y, si falta, de términos de material de curación en la descripción; el resto pertenece a Medicamentos e insumos. Las marcas son capturadas, no inferidas.

`GET /api/mobile/v1/pharmacy/catalog-items/{id}?latitude=...&longitude=...` devuelve `{data: medicamento}` con todas sus ofertas autorizadas, para el futuro detalle y selector de distribuidor. Un medicamento OFF o no disponible para publicación responde 404. No se cambia automáticamente el distribuidor elegido por el usuario.

Cada entrada tiene `id` (ID del medicamento universal), `sku`, `name`, `description`, `presentation`, `category`, `brand`, `requires_prescription`, `currency`, `price_from`, `available`, `image_url`, `offers` y `nearest_offer_id`.

Cada oferta incluye `id` (ID de PharmacyProduct usado para solicitar), `catalog_item_id`, precio, existencias, disponibilidad, imagen, laboratorio y `provider: {id, name, address, latitude, longitude, distance_km}`. El precio/stock nunca se inventa cuando no hay oferta: `price_from=null`, `available=false`, `offers=[]`.

La interfaz debe:

1. Mostrar una tarjeta por medicamento, evitando duplicar las ofertas vinculadas que también aparecen en `data.products`.
2. Mostrar el nombre y dirección del distribuidor debajo del producto; permitir elegir entre sus ofertas.
3. Pedir ubicación solo cuando el usuario active la opción de cercanía y enviar las coordenadas junto con las consultas del catálogo. Sin coordenadas no se identifica un distribuidor como más cercano.
4. Usar `nearest_offer_id` para destacar la oferta con existencias y ubicación más cercana. La distancia usa Haversine en kilómetros en línea recta; no es tiempo de entrega ni distancia por carretera. Ubicaciones desconocidas devuelven `null`, nunca cero.
5. Enviar el ID de la **oferta** en `items[].product_id`, nunca el ID del medicamento universal. La creación de solicitudes revalida publicación, estado, stock y receta.

La solicitud guarda en cada línea `catalog_item_id`, `sku`, `provider_id` y `provider_name` junto al producto y precio elegido, para consulta histórica. Los IDs, nombres y precios se resuelven en el servidor a partir de la oferta seleccionada.

No se guarda la ubicación del comprador en la base de datos al consultar el catálogo. El endpoint no devuelve datos privados de la cuenta proveedora.

Por instrucción del usuario, esta entrega prepara el backend de Klini móvil y mantiene su frontend sin cambios. El diseño gráfico y el flujo de selección de distribuidores se integrarán posteriormente. Estos cambios no equivalen a publicar una nueva versión móvil.

## Archivos principales

- `MedicationCatalogController`: estatus y publicación del superadministrador.
- `DistributorCatalogController`: selección y edición de ofertas propias.
- `PharmacyCatalogPublication`: catálogo compartido, ofertas y distancia.
- `PharmacyStoreController`: API móvil y validación de nuevas solicitudes.
- `UniversalPharmacyPublicationTest`: publicación, estados, aislamiento, cercanía y validación.

También se actualizaron los modelos `MedicationCatalogItem`, `PharmacyProduct` y `Provider`; las rutas web/API; `PharmacyStoreAdminController`; `DistributorWorkspace` y `DistributorTableFilters`; las vistas `superadmin/medications/{table,create-dialog,status-dialog}`, `superadmin/catalog`, `pharmacy-store/distributor/{layout,workspace,universal-catalog}` y `pharmacy-store/product-fields`; y los recursos `public/js/universal-medication-catalog.js` y `public/css/universal-medication-catalog.css`.

## Verificación del 12 de noviembre de 2026

- Migración aditiva aplicada a la base local.
- Suite completa: **434 pruebas correctas y 1 falla previa**, 6962 aserciones. La falla ajena a esta entrega está en `DoctorConsultationTariffTest:74`, por orden de claves de un arreglo de auditoría. Reporte: `storage/app/qa/universal-pharmacy-final-tests.txt`.
- Los 7 casos nuevos de `UniversalPharmacyPublicationTest` pasan, incluyendo que el pedido respete el distribuidor seleccionado aunque exista otro más cercano.
- Navegador: popup con los cuatro estatus y guardado ON/OFF verificados. Se restauró el estado OFF original del producto usado en la comprobación.
- API local `/api/mobile/v1/pharmacy/catalog-items`: respuesta correcta, página de 24; catálogo universal publicado vacío mientras sus productos permanezcan OFF.
- El frontend móvil no se modificó, conforme a la instrucción del usuario.

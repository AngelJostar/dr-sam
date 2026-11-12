# Farmacia digital: administración y API móvil

## Alcance real

Esta implementación agrega un catálogo de distribuidores y solicitudes administrativas. **No procesa pagos, no reserva stock, no confirma dispensación ni entrega.** Los totales y envío son una cotización de referencia calculada en servidor. Las recetas permanecen pendientes de revisión humana; adjuntarlas no equivale a autorización médica.

Se reutilizan usuarios `role=provider`, módulo `provider_medicines`, y perfiles Provider de tipo `medicines`, `medication` o `distributor`. No se cambia la interfaz paciente web.

## Activación local

1. Revisar y respaldar la base que se utilizará. No ejecutar migrate:fresh.
2. Aplicar las migraciones aditivas `2026_11_11_000001_create_pharmacy_store.php` y `2026_11_11_000002_create_pharmacy_prescription_uploads_table.php` mediante el procedimiento normal de despliegue.
3. Entrar como superadmin en /superadmin/distributors. Crear o autorizar un distribuidor.
4. El distribuidor administra sus propios productos en /providers/medicines/products. También hay un enlace desde su panel existente de medicamentos.
5. Superadmin configura y abre la tienda en /superadmin/digital-pharmacy. Ambas autorizaciones (tienda abierta + distribuidor habilitado) son necesarias.

Los productos previos sin provider_id no se publican. Los distribuidores y la tienda nacen deshabilitados. También se exige producto activo, perfil proveedor activo y cuenta proveedora activa. Productos controlados o de cadena fría no se publican mientras no exista flujo de cumplimiento y entrega adecuado.

No se envían correos de acceso al crear usuarios. La contraseña inicial se valida y almacena hasheada. Debe entregarse por un canal seguro por el operador.

## API base /api/mobile/v1/pharmacy

GET /catalog (público):
- data.settings: enabled, title, subtitle, banner_image_url, accent_color, currency=MXN, shipping_fee decimal string, delivery_estimate, purchase_terms, checkout_available=false, orders_available, notice.
- data.products: hasta 100 productos, destacados primero. Para catálogo mayor usar /products.
- data.categories: categorías publicadas.
- data.programs: [] (sin programa real configurado).

GET /settings devuelve {data:settings}.
GET /products admite q, category, specialty, product_type, page. Devuelve paginator Laravel con data[], current_page, last_page, links, etc.; página de 24.
GET /products/{id} devuelve {data:producto}. Un producto no publicable devuelve 404 incluso por URL directa.

Producto: id, name, description, category, specialty, product_type, image_url, price (decimal string), currency, compare_at_price (decimal string|null), is_promotion, adherence_program_id=null, stock, requires_prescription, featured, presentation, laboratory, provider:{id,name}.

### Autenticación de solicitudes

Usa la autenticación móvil ya existente:
POST /api/mobile/v1/auth/login con login, password, device_name.
El token devuelto en data.access_token se usa como Authorization: Bearer TOKEN. Pedidos y recetas exigen usuario paciente activo, perfil paciente y capacidad Sanctum mobile:access. No se admite contraseña de revisión ni token ficticio.

POST /orders:
- client_request_id: UUID obligatorio. Reutilizar el mismo UUID al reintentar la misma solicitud. Una repetición devuelve la solicitud original, no crea otra.
- items: array no vacío [{product_id:integer, quantity:integer}], IDs únicos, máximo 50 líneas, cantidad 1–99.
- address: {name, street, neighborhood, postal_code, references?}; código postal mexicano de 5 dígitos.
- delivery_method: "delivery".
- terms_accepted: true.
- prescription_id opcional: receta clínica del paciente actual.
- prescription_upload_id opcional: adjunto privado del mismo usuario y paciente, pendiente de revisión.

Precios, publicación y stock se revalidan en transacción. El cliente no controla subtotal, envío, total, estado, identidad ni aprobación de receta. El precio unitario y la tarifa de envío se guardan como snapshot. No hay disminución ni reserva de inventario. El total máximo por solicitud es 1,000,000.00 MXN y el precio unitario máximo publicable es 999,999.99 MXN.

Respuesta 201 nueva / 200 reintento: {data:{id,user_id,client_request_id,items:[{product_id,provider_id,name,quantity,unit_price,total,requires_prescription}],subtotal,shipping_fee,total,currency,status:"submitted",payment_status:"not_configured",prescription_id,prescription_upload_id,prescription_status:"pending_review"|null,address,delivery_method,terms_accepted_at,purchase_terms,created_at,updated_at}}.

GET /orders devuelve paginator de solicitudes propias. GET /orders/{id} devuelve {data:order}; otra cuenta recibe 404.

### Receta adjunta

POST /prescription-uploads, multipart file, Bearer paciente: PDF/JPEG/PNG hasta 8 MiB. Devuelve 201 {success:true,data:{id,file_name,mime_type,size_bytes,status:"pending_review",created_at}}. Archivos guardados en disco local privado, sin exponer path.
GET /prescription-uploads/{upload}/file: descarga privada del mismo usuario y paciente, nosniff/no-store. Límite de subidas por minuto y por día. No hay análisis antimalware ni aprobación clínica automáticos; deben integrarse antes de un flujo de dispensación real.

### Errores

El manejador móvil existente devuelve {success:false,error:{code,message,details?}}. Errores de validación: error.details.fields. 401 sin autenticación, 403 rol/cuenta incorrecta, 404 recursos ajenos/no públicos, 422 datos inválidos/no disponibles, 429 límites, 503 tienda cerrada para nueva solicitud.

## Administración y límites

Superadmin tiene menús separados Farmacia digital y Distribuidores. Solo él cambia publicación, presentación y estados de solicitudes. El distribuidor solo crea/edita productos propios y no puede autoautorizarse. Estado: submitted -> reviewing -> ready; cancelación posible hasta ready, cancelled terminal. ready significa seguimiento administrativo, nunca pago o entrega.

Las imágenes y términos se configuran mediante URLs HTTPS sin credenciales incrustadas. No se descargan imágenes desde servidor. No se proporciona almacenamiento de imágenes público ni pasarela, transportista, emails transaccionales o programas de adherencia.

La aplicación cliente debe diferenciar catálogo real vacío/deshabilitado de demostración. No debe presentar productos demo como existencias reales ni enviar pedidos con IDs demo.

## Verificación

Pruebas enfocadas, sin base activa:
```powershell
$env:DB_CONNECTION='sqlite'
$env:DB_DATABASE=':memory:'
$env:TEMP=(Join-Path (Get-Location) 'storage/framework/testing-tmp')
$env:TMP=$env:TEMP
php -d sys_temp_dir=storage/framework/testing-tmp vendor/phpunit/phpunit/phpunit --filter 'PharmacyStore|PharmacyPrescriptionUpload' --do-not-cache-result
```
Cubre publicación deny-by-default, URLs directas, roles, aislamiento, idempotencia, totales servidor, stock, propiedad de receta/solicitud, carga privada, estados administrativos y ausencia de cobro.

## Seguimiento del distribuidor
GET web /providers/medicines/store-orders muestra solo sus líneas, subtotal propio y estado administrativo. No incluye datos personales, dirección, recetas ni total o líneas de terceros. Solo superadmin gestiona estados globales, contacto y dirección; puede descargar adjuntos con validación de vínculo y auditoría. Para alto volumen conviene normalizar líneas: el listado actual recorre snapshots con cursor para compatibilidad entre bases.

## Estado local verificado (11 de noviembre de 2026)

Las dos migraciones nuevas fueron aplicadas por el coordinador únicamente en el entorno local MySQL dr_sam, batch 19. No se habilitó la tienda ni ningún distribuidor. Esto no representa despliegue a producción.

Smoke mediante HTTP Kernel sobre la base local migrada: catálogo 200 con enabled=false y cero productos; pedidos sin token 401; descarga de receta sin token 401. No se probó conexión desde teléfono físico.

Suite completa aislada SQLite en memoria: 313 pruebas y 4969 assertions correctas. Suite farmacia: 23 pruebas y 164 assertions correctas.

QA visual usa HTML Blade generado por pruebas con cuentas/productos/pedidos ficticios en SQLite en memoria, recursos CSS/JS locales y Edge headless con solicitudes interceptadas. No utiliza usuarios reales, sesiones reales, red externa ni datos de la base activa. Capturas permanecen locales. Se corrigió un desborde de la etiqueta accesible del selector de estado en la tabla a 390px, manteniendo scroll horizontal interno de tablas.

Resultado visual final: 21 vistas (presentación, distribuidores, alta distribuidor, solicitudes superadmin, edición y alta de producto, solicitudes proveedor; cada una a 1380, 1024 y 390 px). document.documentElement.scrollWidth coincide con el viewport en todos los casos. Se inspeccionaron capturas reales de escritorio, tablet y móvil. Las tablas anchas conservan su scroll interno y no ensanchan la página. Prueba de render ficticio: 1 test, 7 assertions. Tras el ajuste CSS se repitió la suite farmacia: 23 tests, 164 assertions correctas.

Esta QA valida presentación/render responsive sobre fixtures; las escrituras/autorizaciones se verifican mediante tests Laravel, no mediante envío de formularios reales desde navegador. No constituye prueba de un teléfono físico.
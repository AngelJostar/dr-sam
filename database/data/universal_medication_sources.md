# Catálogo universal de demostración

Se reutilizan 203 presentaciones distintas por clave CNIS del archivo
`imss_bienestar_essential_medicines_2026.json`. El nombre histórico del archivo no
implica una edición oficial 2026. Verificación realizada el 12 de noviembre de 2026.

- Fuente principal: [IMSS Bienestar, Rutas de la Salud v5.1](https://pgsp.imssbienestar.gob.mx/rutas_de_la_salud/docs/App_RdlS_v5.1_Meds_MatCur.pdf).
  Se contrastaron las 203 claves, nombres, descripciones y tres indicadores por
  tipo de unidad contra el PDF descargado. Las 203 coinciden después de normalizar
  espacios, puntuación y acentos de la extracción. Se conserva la descripción original
  del conjunto de datos, incluidas concentraciones y contenido del envase.
- Referencia secundaria: [Secretaría de Salud CDMX, Catálogo Institucional de Medicamentos,
  edición 2020](https://data.salud.cdmx.gob.mx/ssdf/portalut/archivo/Actualizaciones/4toTrimestre21/Dir_Med_Tec_Ins/CIM.pdf),
  publicado en la Gaceta del 3 de diciembre de 2021. Se localizaron 152 de las claves.
  Esta comprobación es de presencia de clave, no una afirmación de vigencia ni de
  coincidencia de todas las descripciones con esa edición histórica.
- `universal_medication_sources.json` registra las páginas de ambas fuentes, los
  resultados por clave y SHA-256 de los documentos descargados.

La carga se ejecuta con `php artisan db:seed --class=UniversalMedicationDemoSeeder`.
Es transaccional e idempotente por CNIS. Reutiliza los registros existentes, conserva
sus datos capturados e identificadores, y solo completa SKU/código cuando faltan.
Completa también metadatos de referencia ausentes (tipo de unidad y catálogo), sin
reemplazar los valores existentes.
No modifica catálogos institucionales de unidades ni crea productos de distribuidores.

El SKU numérico de nueve dígitos que comienza con 1 (por ejemplo, `100000001`) y el número de 16 dígitos con prefijo interno `9900` son
identificadores propios de demostración, no GTIN/EAN asignados por un fabricante.
La carga convierte también los SKU generados anteriormente con prefijo `KLINI-MED-`
al formato numérico y antepone 1 a los SKU anteriores de ocho dígitos. Conserva los
SKU personalizados que ya cumplen ese formato. El editor solo admite nueve dígitos
con prefijo 1. De este modo, la exportación CSV no depende de ceros a la izquierda.
El alta desde el popup reserva el siguiente SKU después del máximo del catálogo o
de la secuencia persistida. La reserva, creación y edición de SKU usan transacciones
y un bloqueo de fila compartido, evitando asignar el mismo consecutivo simultáneamente.
El SVG representa el número mediante Code 128 con checksum y zonas de silencio;
no es una imagen comercial de envase. No se infieren marcas, precios, inventarios,
registros sanitarios o condiciones de dispensación que las fuentes no proporcionan.
Los códigos comerciales se pueden capturar desde el envase mediante el editor.

Farmacia digital y Medicamentos consultan los mismos `MedicationCatalogItem`.
Los SKU de distribuidores en `PharmacyProduct` siguen siendo identificadores de sus
propias ofertas; la carga del catálogo base no autoriza ni publica ofertas en la tienda.

# Ajustes de densidad y Comunidades — 6 de octubre de 2026

- `public/css/klini-carousels.css`: tarjetas de servicio de 140 × 148 px (antes 224 px de alto); 126 px de ancho en pantallas menores a 900 px. Iconos, espacios y variantes de navegación del paciente reducidos. Se reutilizan los selectores compartidos de Unidad, Institución, Área operativa y Proveedor de importación.
- `public/js/klini-patient-workspace.js`: eliminación de la columna lateral de Comunidades, incluidas las sugerencias, perfil y tarjeta del calendario. Actualización de la versión del CSS del componente para invalidar la caché.
- `public/css/klini-patient-component.css`: contenido de Comunidades centrado en una sola columna, con ancho máximo de 820 px; eliminación de estilos de la columna retirada.

## Verificación de este ajuste

- 2 pruebas Laravel enfocadas aprobadas, 348 aserciones (opciones de importación y secciones de catálogos del superadministrador).
- 5 pruebas JavaScript de `tests/Frontend/PatientWorkspace.test.mjs` aprobadas.
- Navegador: Comunidades sin columna lateral, navegación a Mi calendario y regreso; desplazamiento del carrusel de Unidad y selección de Consulta externa.
- Medidas comprobadas en navegador: tarjetas de 140 × 148 px en escritorio y 126 × 148 px a 390 px de viewport. Sin desbordamiento horizontal del documento en Unidad a ese ancho.
- Capturas: `klini-patient-communities-centered.png` y `klini-carousels-compact.png`.

No se modificaron rutas, permisos ni datos. La comprobación global de espacios detectó avisos en archivos generados y registros existentes ajenos al ajuste; se conservaron.

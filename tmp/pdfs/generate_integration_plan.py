from pathlib import Path
from datetime import date

from reportlab.lib import colors
from reportlab.lib.enums import TA_CENTER, TA_LEFT
from reportlab.lib.pagesizes import A4, landscape
from reportlab.lib.styles import getSampleStyleSheet, ParagraphStyle
from reportlab.lib.units import mm
from reportlab.platypus import (
    SimpleDocTemplate, Paragraph, Spacer, Table, TableStyle, PageBreak,
    KeepTogether, ListFlowable, ListItem
)
from reportlab.pdfbase.ttfonts import TTFont
from reportlab.pdfbase import pdfmetrics


BASE = Path(__file__).resolve().parents[2]
OUT = BASE / "output" / "pdf" / "plan_integracion_api_drsam_mezclas_cbta.pdf"
OUT.parent.mkdir(parents=True, exist_ok=True)

PAGE = landscape(A4)
TEAL = colors.HexColor("#008C82")
TEAL_DARK = colors.HexColor("#075E59")
TEAL_LIGHT = colors.HexColor("#EAF8F6")
NAVY = colors.HexColor("#11243D")
BLUE_LIGHT = colors.HexColor("#EDF4FB")
GRAY_900 = colors.HexColor("#243341")
GRAY_700 = colors.HexColor("#526371")
GRAY_500 = colors.HexColor("#7C8A96")
GRAY_300 = colors.HexColor("#CDD7DE")
GRAY_200 = colors.HexColor("#E4EAEE")
GRAY_100 = colors.HexColor("#F5F7F8")
AMBER = colors.HexColor("#C97800")
AMBER_LIGHT = colors.HexColor("#FFF5DF")
RED = colors.HexColor("#C23838")
RED_LIGHT = colors.HexColor("#FFF0F0")
GREEN = colors.HexColor("#16845B")
GREEN_LIGHT = colors.HexColor("#EAF8F1")


def register_fonts():
    candidates = [
        ("Inter", Path("C:/Windows/Fonts/arial.ttf")),
        ("Inter-Bold", Path("C:/Windows/Fonts/arialbd.ttf")),
    ]
    for name, path in candidates:
        if path.exists():
            pdfmetrics.registerFont(TTFont(name, str(path)))


register_fonts()
# Standard PDF fonts render more consistently across cPanel previews, browsers
# and Poppler than locally embedded Windows fonts.
FONT = "Helvetica"
FONT_BOLD = "Helvetica-Bold"

styles = getSampleStyleSheet()
styles.add(ParagraphStyle(
    name="CoverTitle", fontName=FONT_BOLD, fontSize=27, leading=31,
    textColor=NAVY, alignment=TA_LEFT, spaceAfter=12
))
styles.add(ParagraphStyle(
    name="CoverSubtitle", fontName=FONT, fontSize=13, leading=18,
    textColor=GRAY_700, spaceAfter=18
))
styles.add(ParagraphStyle(
    name="H1x", fontName=FONT_BOLD, fontSize=17, leading=21,
    textColor=NAVY, spaceBefore=3, spaceAfter=10
))
styles.add(ParagraphStyle(
    name="H2x", fontName=FONT_BOLD, fontSize=11.5, leading=14,
    textColor=TEAL_DARK, spaceBefore=8, spaceAfter=6
))
styles.add(ParagraphStyle(
    name="Bodyx", fontName=FONT, fontSize=8.7, leading=12.5,
    textColor=GRAY_900, spaceAfter=6
))
styles.add(ParagraphStyle(
    name="Smallx", fontName=FONT, fontSize=7.2, leading=9.4,
    textColor=GRAY_700
))
styles.add(ParagraphStyle(
    name="Tinyx", fontName=FONT, fontSize=6.2, leading=7.8,
    textColor=GRAY_900
))
styles.add(ParagraphStyle(
    name="Cell", fontName=FONT, fontSize=6.5, leading=8.2,
    textColor=GRAY_900
))
styles.add(ParagraphStyle(
    name="CellBold", fontName=FONT_BOLD, fontSize=6.5, leading=8.2,
    textColor=GRAY_900
))
styles.add(ParagraphStyle(
    name="CellWhite", fontName=FONT_BOLD, fontSize=6.5, leading=8.2,
    textColor=colors.white
))
styles.add(ParagraphStyle(
    name="Callout", fontName=FONT, fontSize=9, leading=13,
    textColor=TEAL_DARK, borderColor=colors.HexColor("#8FD8D1"),
    borderWidth=0.8, borderPadding=9, backColor=TEAL_LIGHT,
    spaceBefore=5, spaceAfter=8
))
styles.add(ParagraphStyle(
    name="Warning", fontName=FONT, fontSize=8.5, leading=12,
    textColor=colors.HexColor("#784500"), borderColor=colors.HexColor("#F2C76D"),
    borderWidth=0.8, borderPadding=8, backColor=AMBER_LIGHT,
    spaceBefore=4, spaceAfter=8
))


def P(text, style="Bodyx"):
    return Paragraph(text, styles[style])


def bullets(items, level=0):
    return ListFlowable(
        [ListItem(P(i, "Bodyx"), leftIndent=7) for i in items],
        bulletType="bullet", start="circle", leftIndent=14 + level * 8,
        bulletFontName=FONT, bulletFontSize=6, spaceAfter=5
    )


def make_table(headers, rows, widths, header_bg=TEAL_DARK, font_size=6.5,
               repeat_rows=1, row_bgs=True):
    data = [[P(h, "CellWhite") for h in headers]]
    for row in rows:
        formatted = []
        for value in row:
            if isinstance(value, Paragraph):
                formatted.append(value)
            else:
                formatted.append(P(str(value), "Cell"))
        data.append(formatted)
    table = Table(data, colWidths=widths, repeatRows=repeat_rows, hAlign="LEFT")
    commands = [
        ("BACKGROUND", (0, 0), (-1, 0), header_bg),
        ("TEXTCOLOR", (0, 0), (-1, 0), colors.white),
        ("FONTNAME", (0, 0), (-1, 0), FONT_BOLD),
        ("VALIGN", (0, 0), (-1, -1), "TOP"),
        ("GRID", (0, 0), (-1, -1), 0.45, GRAY_300),
        ("LEFTPADDING", (0, 0), (-1, -1), 5),
        ("RIGHTPADDING", (0, 0), (-1, -1), 5),
        ("TOPPADDING", (0, 0), (-1, -1), 5),
        ("BOTTOMPADDING", (0, 0), (-1, -1), 5),
    ]
    if row_bgs:
        for i in range(1, len(data)):
            commands.append(("BACKGROUND", (0, i), (-1, i), colors.white if i % 2 else GRAY_100))
    table.setStyle(TableStyle(commands))
    return table


def status_badge(text, color, bg):
    return Table([[P(text, "CellBold")]], colWidths=[25 * mm], style=TableStyle([
        ("BACKGROUND", (0, 0), (-1, -1), bg),
        ("TEXTCOLOR", (0, 0), (-1, -1), color),
        ("BOX", (0, 0), (-1, -1), 0.7, color),
        ("ALIGN", (0, 0), (-1, -1), "CENTER"),
        ("VALIGN", (0, 0), (-1, -1), "MIDDLE"),
        ("LEFTPADDING", (0, 0), (-1, -1), 4),
        ("RIGHTPADDING", (0, 0), (-1, -1), 4),
        ("TOPPADDING", (0, 0), (-1, -1), 3),
        ("BOTTOMPADDING", (0, 0), (-1, -1), 3),
    ]))


def header_footer(canvas, doc):
    canvas.saveState()
    width, height = PAGE
    if doc.page > 1:
        canvas.setFillColor(NAVY)
        canvas.rect(0, height - 13 * mm, width, 13 * mm, fill=1, stroke=0)
        canvas.setFillColor(colors.white)
        canvas.setFont(FONT_BOLD, 7.5)
        canvas.drawString(14 * mm, height - 8.2 * mm, "PLAN DE INTEGRACION API - DR. SAM Y MEZCLAS/CBTA")
        canvas.setFont(FONT, 7)
        canvas.drawRightString(width - 14 * mm, height - 8.2 * mm, "Version 1.0 | 5 de agosto de 2026")
    canvas.setStrokeColor(GRAY_300)
    canvas.line(14 * mm, 10 * mm, width - 14 * mm, 10 * mm)
    canvas.setFillColor(GRAY_500)
    canvas.setFont(FONT, 6.7)
    canvas.drawString(14 * mm, 6.2 * mm, "Documento de planeacion tecnica - Alcance inicial sin aplicacion movil")
    canvas.drawRightString(width - 14 * mm, 6.2 * mm, f"Pagina {doc.page}")
    canvas.restoreState()


doc = SimpleDocTemplate(
    str(OUT), pagesize=PAGE,
    rightMargin=14 * mm, leftMargin=14 * mm,
    topMargin=20 * mm, bottomMargin=15 * mm,
    title="Plan de integracion API Dr. Sam - Mezclas/CBTA",
    author="Proyecto Dr. Sam",
    subject="Matriz detallada para la integracion entre Dr. Sam y el Sistema de Mezclas/CBTA"
)

story = []

# Cover
story.append(Spacer(1, 18 * mm))
story.append(P("PLAN DE INTEGRACION API", "CoverTitle"))
story.append(P("Dr. Sam y Sistema de Mezclas / CBTA", "CoverTitle"))
story.append(P(
    "Matriz detallada de fases, responsabilidades, entregables, dependencias, pruebas y criterios de aceptacion.",
    "CoverSubtitle"
))
cover_meta = Table([
    [P("Version", "CellBold"), P("1.0", "Cell")],
    [P("Fecha", "CellBold"), P("5 de agosto de 2026", "Cell")],
    [P("Proyectos", "CellBold"), P("Dr. Sam (Laravel 12) y Mezclas/CBTA (Laravel 10)", "Cell")],
    [P("Ramas de trabajo", "CellBold"), P("API en ambos repositorios", "Cell")],
    [P("Alcance", "CellBold"), P("Integracion web entre sistemas. La aplicacion movil de mensajeros queda fuera de esta etapa.", "Cell")],
], colWidths=[36 * mm, 135 * mm])
cover_meta.setStyle(TableStyle([
    ("BACKGROUND", (0, 0), (0, -1), TEAL_LIGHT),
    ("BOX", (0, 0), (-1, -1), 0.8, GRAY_300),
    ("INNERGRID", (0, 0), (-1, -1), 0.5, GRAY_300),
    ("VALIGN", (0, 0), (-1, -1), "TOP"),
    ("LEFTPADDING", (0, 0), (-1, -1), 8),
    ("RIGHTPADDING", (0, 0), (-1, -1), 8),
    ("TOPPADDING", (0, 0), (-1, -1), 7),
    ("BOTTOMPADDING", (0, 0), (-1, -1), 7),
]))
story.append(cover_meta)
story.append(Spacer(1, 14 * mm))
story.append(P(
    "Principio rector: Dr. Sam administra el contexto clinico, las autorizaciones y el seguimiento; Mezclas/CBTA administra catalogos tecnicos, inventario, produccion, lotes, remisiones y entrega.",
    "Callout"
))
story.append(PageBreak())

# 1 Executive scope
story.append(P("1. Objetivo, alcance y resultado esperado", "H1x"))
story.append(P(
    "El proyecto construira una integracion desacoplada entre dos aplicaciones Laravel independientes. Dr. Sam sera el sistema que origina y coordina la solicitud; Mezclas/CBTA sera el sistema que valida su viabilidad tecnica y ejecuta la produccion. La comunicacion se realizara mediante APIs JSON versionadas, autenticadas e idempotentes.",
    "Bodyx"
))
scope_rows = [
    ["Incluido", "Consulta de catalogos habilitados por unidad", "Dr. Sam podra construir solicitudes con codigos vigentes de CBTA."],
    ["Incluido", "Prevalidacion", "CBTA validara catalogo, presentaciones, reglas tecnicas y disponibilidad informativa."],
    ["Incluido", "Alta definitiva", "CBTA registrara la solicitud, reservara o consumira inventario conforme a su flujo y devolvera un folio."],
    ["Incluido", "Sincronizacion de estados", "Dr. Sam recibira estados productivos, lote, remision y entrega."],
    ["Incluido", "Cancelacion controlada", "Dr. Sam solicitara la cancelacion y CBTA decidira si procede segun el estado productivo."],
    ["Incluido", "Seguridad y auditoria", "Tokens de servicio, firma de eventos, correlacion, reintentos y bitacora."],
    ["Fuera de alcance", "Aplicacion movil", "El rastreo de mensajeros se planificara en una etapa independiente."],
    ["Fuera de alcance", "Migracion total de CBTA", "No se movera inventario ni produccion a la base de Dr. Sam."],
    ["Fuera de alcance", "Base de datos compartida", "Cada sistema conservara su base y accedera al otro exclusivamente mediante contratos de integracion."],
]
story.append(make_table(
    ["Clasificacion", "Elemento", "Resultado esperado"], scope_rows,
    [27 * mm, 70 * mm, 155 * mm]
))
story.append(Spacer(1, 5 * mm))
story.append(P("Resultado minimo viable (MVP)", "H2x"))
story.append(bullets([
    "Una unidad de Dr. Sam puede consultar el catalogo que CBTA tiene habilitado para ella.",
    "Una solicitud NPT puede prevalidarse y registrarse una sola vez en CBTA.",
    "Una solicitud oncologica puede prevalidarse y registrarse una sola vez en CBTA.",
    "Dr. Sam conserva el vinculo entre su solicitud y el folio emitido por CBTA.",
    "Los cambios productivos de CBTA se reflejan en Dr. Sam con trazabilidad y sin duplicados.",
    "Los errores de validacion y comunicacion son visibles y recuperables sin intervencion en las bases de datos."
]))
story.append(PageBreak())

# 2 Architecture and ownership
story.append(P("2. Arquitectura y propiedad de la informacion", "H1x"))
architecture = Table([
    [P("CONSUMIDORES", "CellWhite"), P("DR. SAM", "CellWhite"), P("MEZCLAS / CBTA", "CellWhite")],
    [
        P("Portal web actual<br/>Aplicaciones futuras<br/>Usuarios institucionales", "Cell"),
        P("API central y orquestador<br/>Pacientes, medicos, unidades, autorizaciones, solicitud e historial de integracion", "Cell"),
        P("API interna especializada<br/>Catalogos, inventario, viabilidad, produccion, lotes, remisiones y entrega", "Cell")
    ],
    [P("", "Cell"), P("HTTPS JSON + token de servicio", "CellBold"), P("Webhooks firmados + consulta de estado", "CellBold")]
], colWidths=[60 * mm, 96 * mm, 96 * mm])
architecture.setStyle(TableStyle([
    ("BACKGROUND", (0, 0), (-1, 0), NAVY),
    ("BACKGROUND", (0, 1), (0, 1), BLUE_LIGHT),
    ("BACKGROUND", (1, 1), (1, 1), TEAL_LIGHT),
    ("BACKGROUND", (2, 1), (2, 1), AMBER_LIGHT),
    ("BACKGROUND", (1, 2), (2, 2), GRAY_100),
    ("BOX", (0, 0), (-1, -1), 0.7, GRAY_300),
    ("INNERGRID", (0, 0), (-1, -1), 0.5, GRAY_300),
    ("VALIGN", (0, 0), (-1, -1), "MIDDLE"),
    ("ALIGN", (0, 0), (-1, -1), "CENTER"),
    ("LEFTPADDING", (0, 0), (-1, -1), 8),
    ("RIGHTPADDING", (0, 0), (-1, -1), 8),
    ("TOPPADDING", (0, 0), (-1, -1), 8),
    ("BOTTOMPADDING", (0, 0), (-1, -1), 8),
]))
story.append(architecture)
story.append(Spacer(1, 5 * mm))
ownership_rows = [
    ["Paciente, medico y unidad solicitante", "Dr. Sam", "CBTA conserva una fotografia para produccion."],
    ["Autorizaciones internas", "Dr. Sam", "CBTA recibe evidencia, pero no las modifica."],
    ["Prescripcion original", "Dr. Sam", "Se envia con codigos externos y unidades normalizadas."],
    ["Catalogo tecnico y presentaciones", "CBTA", "Dr. Sam mantiene cache o copia de lectura."],
    ["Inventario, lote y caducidad", "CBTA", "Dr. Sam no reserva ni descuenta directamente."],
    ["Precios y lista aplicable", "CBTA", "Se determina por hospital/laboratorio/lista vigente."],
    ["Estado productivo", "CBTA", "Dr. Sam conserva el ultimo estado notificado."],
    ["Remision y documentos productivos", "CBTA", "Dr. Sam enlaza o descarga una copia autorizada."],
    ["Historial de integracion", "Ambos", "Cada sistema registra solicitudes, respuestas, eventos y errores."],
]
story.append(make_table(
    ["Informacion", "Autoridad", "Regla de intercambio"], ownership_rows,
    [75 * mm, 35 * mm, 142 * mm]
))
story.append(PageBreak())

# 3 Decisions
story.append(P("3. Decisiones obligatorias antes de programar", "H1x"))
decision_rows = [
    ["D-01", "Identificador externo", "Definir UUID o folio inmutable para cada solicitud de Dr. Sam.", "Arquitectura", "Bloqueante"],
    ["D-02", "Equivalencia de unidades", "Elegir CLUES, codigo institucional o tabla explicita Dr. Sam -> hospital CBTA.", "Negocio + datos", "Bloqueante"],
    ["D-03", "Codigos de catalogo", "Agregar o confirmar codigos externos estables para insumos, medicamentos y presentaciones.", "CBTA", "Bloqueante"],
    ["D-04", "Estados canonicos", "Normalizar pending, approved, prepared, reviewed, delivered, cancelled y rejected.", "Ambos", "Bloqueante"],
    ["D-05", "Momento de envio", "Confirmar reglas de autorizacion requeridas para NPT y oncologia.", "Dr. Sam", "Bloqueante"],
    ["D-06", "Reserva de inventario", "MVP: prevalidacion informativa y reserva solo al alta definitiva.", "CBTA", "Recomendado"],
    ["D-07", "Cancelacion", "Definir estados en los que CBTA acepta o rechaza cancelaciones.", "Ambos", "Bloqueante"],
    ["D-08", "Documentos", "Definir si Dr. Sam descarga, almacena o enlaza remisiones y PDFs.", "Ambos", "Posterior al MVP"],
    ["D-09", "Credenciales", "Crear identidad de servicio y capacidades de lectura/escritura.", "Infraestructura", "Bloqueante"],
    ["D-10", "Retencion de auditoria", "Definir periodo de conservacion de payloads, errores y eventos.", "Seguridad", "Antes de produccion"],
]
story.append(make_table(
    ["ID", "Decision", "Definicion requerida", "Responsable", "Prioridad"], decision_rows,
    [14 * mm, 39 * mm, 126 * mm, 38 * mm, 35 * mm]
))
story.append(Spacer(1, 5 * mm))
story.append(P(
    "No debe comenzar el alta definitiva de solicitudes hasta resolver D-01 a D-05, D-07 y D-09. Programar sin estas decisiones obligaria a rehacer migraciones, mapeos y contratos de API.",
    "Warning"
))
story.append(PageBreak())

# 4 Master matrix phase 0-3
story.append(P("4. Matriz maestra de ejecucion - Preparacion y contratos", "H1x"))
master1 = [
    ["0.1", "Inventariar modelos, tablas, controladores y estados actuales", "Ambos", "Ninguna", "Documento de inventario", "Todos los campos y estados estan identificados"],
    ["0.2", "Congelar el alcance del MVP", "Lider del proyecto", "0.1", "Acta de alcance", "Incluidos y excluidos aprobados"],
    ["0.3", "Definir propiedad de cada dato", "Ambos", "0.1", "Matriz de autoridad", "No existe informacion con doble autoridad"],
    ["0.4", "Definir codigos y equivalencias de unidades", "Ambos", "0.1", "Tabla de mapeo", "Cada unidad piloto resuelve un hospital CBTA"],
    ["1.1", "Crear especificacion OpenAPI version 1", "Ambos", "0.2-0.4", "openapi.yaml", "Valida sin errores y documenta ejemplos"],
    ["1.2", "Definir esquema de errores", "Ambos", "1.1", "Catalogo de errores", "422, 401, 403, 404, 409 y 5xx estan cubiertos"],
    ["1.3", "Definir idempotencia y correlacion", "Ambos", "1.1", "Contrato tecnico", "Repetir un POST no duplica registros"],
    ["1.4", "Definir estados y eventos", "Ambos", "0.3", "Mapa de estados", "Cada estado CBTA tiene representacion en Dr. Sam"],
    ["2.1", "Agregar identidad externa a catalogos CBTA", "CBTA", "0.4", "Migracion + modelo", "Codigos unicos, estables e indexados"],
    ["2.2", "Crear tabla de enlaces externos en CBTA", "CBTA", "1.3", "Migracion + modelo", "external_system + external_id son unicos"],
    ["2.3", "Crear tabla mixture_integrations en Dr. Sam", "Dr. Sam", "1.3", "Migracion + modelo", "Una solicitud tiene como maximo un vinculo CBTA activo"],
    ["2.4", "Crear mapeo de unidad Dr. Sam -> hospital CBTA", "Ambos", "0.4", "Migracion + configuracion", "La API rechaza unidades sin mapeo"],
    ["3.1", "Extraer servicio de creacion NPT", "CBTA", "2.1-2.2", "CreateNptRequest", "Web y API usan la misma regla de negocio"],
    ["3.2", "Extraer servicio de creacion oncologica", "CBTA", "2.1-2.2", "CreateOncologyRequest", "Web y API usan la misma regla de negocio"],
    ["3.3", "Extraer transiciones productivas", "CBTA", "3.1-3.2", "Servicios de estados", "No hay transiciones duplicadas en controladores"],
]
story.append(make_table(
    ["Paso", "Actividad", "Responsable", "Dependencia", "Entregable", "Criterio de aceptacion"],
    master1, [14 * mm, 65 * mm, 28 * mm, 27 * mm, 44 * mm, 74 * mm]
))
story.append(PageBreak())

# 5 Master matrix phase 4-7
story.append(P("5. Matriz maestra de ejecucion - Construccion e integracion", "H1x"))
master2 = [
    ["4.1", "Autenticacion de servicio en CBTA", "CBTA", "D-09", "Token/capacidades", "Solo Dr. Sam autorizado consume endpoints internos"],
    ["4.2", "Endpoint de catalogo NPT por unidad", "CBTA", "2.1, 2.4", "GET catalog/npt", "Solo devuelve productos habilitados y codigos externos"],
    ["4.3", "Endpoint de catalogo oncologico por unidad", "CBTA", "2.1, 2.4", "GET catalog/oncology", "Respeta lista y hospital asignados"],
    ["4.4", "Endpoint de prevalidacion", "CBTA", "3.1-3.2", "POST prevalidate", "No crea registros ni reserva inventario"],
    ["4.5", "Endpoint de alta definitiva", "CBTA", "4.1-4.4", "POST requests", "Crea una sola solicitud y devuelve folio CBTA"],
    ["4.6", "Endpoint de consulta de estado", "CBTA", "4.5", "GET requests/{externalId}", "Devuelve estado y metadatos permitidos"],
    ["4.7", "Endpoint de cancelacion", "CBTA", "D-07, 4.5", "POST cancel", "Acepta o rechaza segun estado, sin inconsistencias"],
    ["5.1", "Cliente HTTP CBTA", "Dr. Sam", "1.1, 4.1", "CbtaClient", "Timeout, token, correlacion y errores centralizados"],
    ["5.2", "Servicio de catalogos con cache", "Dr. Sam", "4.2-4.3", "CbtaCatalogService", "Cache invalida y ultima version identificable"],
    ["5.3", "Adaptar formularios de mezclas", "Dr. Sam", "5.2", "UI conectada", "Usuarios seleccionan codigos validos de CBTA"],
    ["5.4", "Prevalidacion desde Dr. Sam", "Dr. Sam", "4.4, 5.1", "Servicio + UI", "Errores se muestran por campo sin crear solicitud CBTA"],
    ["5.5", "Envio tras autorizaciones", "Dr. Sam", "4.5, 5.4", "Servicio/job", "Solo solicitudes elegibles llegan a CBTA"],
    ["5.6", "Consulta y recuperacion manual", "Dr. Sam", "4.6", "Accion de resincronizar", "Un fallo temporal puede recuperarse sin SQL manual"],
    ["6.1", "Endpoint receptor de eventos CBTA", "Dr. Sam", "1.4", "POST events", "Firma e idempotencia validadas"],
    ["6.2", "Emisor de eventos con reintentos", "CBTA", "6.1", "Webhook dispatcher", "Fallas quedan registradas y reintentables"],
    ["6.3", "Mapeo de estados", "Dr. Sam", "1.4, 6.1", "CbtaStatusMapper", "Eventos fuera de orden no degradan el estado"],
    ["7.1", "Remision y documentos", "Ambos", "4.6, 6.1", "Endpoint + UI", "Documento accesible solo con autorizacion"],
    ["7.2", "Confirmacion de entrega", "Ambos", "6.1-6.3", "Evento final", "Ambos sistemas muestran entrega consistente"],
]
story.append(make_table(
    ["Paso", "Actividad", "Responsable", "Dependencia", "Entregable", "Criterio de aceptacion"],
    master2, [14 * mm, 65 * mm, 28 * mm, 27 * mm, 44 * mm, 74 * mm]
))
story.append(PageBreak())

# 6 API matrix
story.append(Spacer(1, 12 * mm))
story.append(P("6. Matriz de endpoints propuestos", "H1x"))
api_rows = [
    ["CBTA", "GET", "/api/internal/v1/medical-units/{code}/catalogs/npt", "Catalogo NPT habilitado por unidad", "catalogs:read"],
    ["CBTA", "GET", "/api/internal/v1/medical-units/{code}/catalogs/oncology", "Catalogo oncologico habilitado por unidad", "catalogs:read"],
    ["CBTA", "POST", "/api/internal/v1/mixture-requests/prevalidate", "Validar composicion y disponibilidad sin reservar", "mixtures:prevalidate"],
    ["CBTA", "POST", "/api/internal/v1/mixture-requests", "Registrar solicitud definitiva", "mixtures:create"],
    ["CBTA", "GET", "/api/internal/v1/mixture-requests/{externalId}", "Consultar estado productivo", "mixtures:read"],
    ["CBTA", "POST", "/api/internal/v1/mixture-requests/{externalId}/cancel", "Solicitar cancelacion", "mixtures:cancel"],
    ["CBTA", "GET", "/api/internal/v1/mixture-requests/{externalId}/remission", "Obtener remision autorizada", "documents:read"],
    ["Dr. Sam", "POST", "/api/internal/v1/integrations/cbta/events", "Recibir eventos productivos firmados", "Firma HMAC"],
    ["Dr. Sam", "GET", "/api/v1/mixture-catalogs", "Fachada futura para consumidores de Dr. Sam", "Usuario autenticado"],
    ["Dr. Sam", "POST", "/api/v1/mixture-requests/prevalidate", "Fachada de prevalidacion", "mixtures:create"],
    ["Dr. Sam", "POST", "/api/v1/mixture-requests", "Registrar solicitud en Dr. Sam", "mixtures:create"],
    ["Dr. Sam", "GET", "/api/v1/mixture-requests/{id}", "Consultar solicitud y sincronizacion", "mixtures:read"],
]
story.append(make_table(
    ["Sistema", "Metodo", "Ruta", "Proposito", "Autorizacion"], api_rows,
    [25 * mm, 18 * mm, 100 * mm, 78 * mm, 31 * mm]
))
story.append(Spacer(1, 5 * mm))
story.append(P("Reglas de contrato", "H2x"))
story.append(bullets([
    "Todas las rutas se versionan; los cambios incompatibles crean una nueva version.",
    "Los POST de alta requieren Idempotency-Key y X-Correlation-ID.",
    "Las fechas usan ISO 8601 con zona horaria.",
    "Las cantidades incluyen valor y unidad; no se infieren unidades por nombre.",
    "Los catalogos exponen external_code; nunca IDs internos de base de datos.",
    "Los errores incluyen code, message, field y details cuando corresponda.",
    "Ninguna respuesta expone credenciales, rutas locales o trazas de excepcion."
]))
story.append(PageBreak())

# 7 data matrix
story.append(Spacer(1, 12 * mm))
story.append(P("7. Datos requeridos para la solicitud", "H1x"))
data_rows = [
    ["external_id", "Dr. Sam", "Si", "UUID/folio inmutable", "Idempotencia y correlacion"],
    ["request_type", "Dr. Sam", "Si", "npt | oncology", "Selecciona reglas CBTA"],
    ["requested_at / required_at", "Dr. Sam", "Si", "ISO 8601", "CBTA valida ventana operativa"],
    ["medical_unit.external_code", "Dr. Sam", "Si", "CLUES/codigo acordado", "Debe mapear a hospital CBTA"],
    ["patient.external_id", "Dr. Sam", "Si", "Referencia no sensible", "No usar ID CBTA"],
    ["patient clinical fields", "Dr. Sam", "Segun tipo", "Nombre, sexo, fecha, peso, registro, diagnostico", "Minimizar datos al necesario"],
    ["physician", "Dr. Sam", "Si", "Nombre, cedula, especialidad", "Fotografia de la solicitud"],
    ["authorizations", "Dr. Sam", "Si", "Area, estado, fecha, actor", "CBTA no las modifica"],
    ["catalog_version", "CBTA", "Si", "Marca de version", "Detecta catalogo desactualizado"],
    ["product_code", "CBTA", "Si", "Codigo externo estable", "Resuelve producto interno"],
    ["presentation_code", "CBTA", "Si", "Codigo externo estable", "Resuelve presentacion"],
    ["dose / quantity / unit", "Dr. Sam", "Si", "Decimal + unidad canonica", "CBTA valida compatibilidad"],
    ["cbta_request_id", "CBTA", "Respuesta", "Folio productivo", "Se guarda en mixture_integrations"],
    ["lot / remission", "CBTA", "Evento", "Codigo productivo", "Solo CBTA los genera"],
    ["remote_status", "CBTA", "Evento", "Estado canonico", "Dr. Sam mantiene copia de lectura"],
]
story.append(make_table(
    ["Campo", "Origen", "Obligatorio", "Formato", "Regla"], data_rows,
    [49 * mm, 28 * mm, 25 * mm, 71 * mm, 79 * mm]
))
story.append(PageBreak())

# 8 states
story.append(P("8. Matriz de estados y eventos", "H1x"))
state_rows = [
    ["pending_sync", "Dr. Sam", "Solicitud local aun no enviada", "Ninguno", "Reintentar"],
    ["received", "CBTA", "Solicitud recibida e identificada", "request.received", "Mostrar folio CBTA"],
    ["approved", "CBTA", "Aceptada tecnicamente", "request.approved", "Actualizar seguimiento"],
    ["rejected", "CBTA", "No viable o no aprobada", "request.rejected", "Mostrar causa; no reintentar sin cambios"],
    ["prepared", "CBTA", "Mezcla preparada", "mixture.prepared", "Guardar fecha y lote si existe"],
    ["reviewed", "CBTA", "Control de calidad completado", "mixture.reviewed", "Mostrar liberacion"],
    ["remission_generated", "CBTA", "Remision disponible", "remission.generated", "Habilitar documento"],
    ["in_delivery", "CBTA", "Salida a entrega", "delivery.started", "Actualizar seguimiento"],
    ["delivered", "CBTA", "Entrega confirmada", "delivery.completed", "Cerrar flujo"],
    ["cancel_requested", "Dr. Sam", "Cancelacion solicitada", "request.cancel.requested", "Esperar resolucion CBTA"],
    ["cancelled", "CBTA", "Cancelacion aceptada", "request.cancelled", "Cerrar flujo y liberar reserva"],
    ["cancel_rejected", "CBTA", "Cancelacion no permitida", "request.cancel.rejected", "Mantener estado productivo"],
    ["sync_error", "Dr. Sam", "Fallo tecnico de comunicacion", "Ninguno", "Reintento controlado"],
]
story.append(make_table(
    ["Estado", "Autoridad", "Significado", "Evento", "Accion en Dr. Sam"], state_rows,
    [40 * mm, 29 * mm, 72 * mm, 54 * mm, 57 * mm]
))
story.append(Spacer(1, 5 * mm))
story.append(P(
    "Regla critica: un evento tardio o repetido no puede regresar una solicitud a un estado anterior. Cada evento tendra event_id, occurred_at y secuencia o version de entidad.",
    "Callout"
))
story.append(PageBreak())

# 9 security reliability
story.append(P("9. Seguridad, confiabilidad y observabilidad", "H1x"))
security_rows = [
    ["Transporte", "HTTPS obligatorio", "Rechazar HTTP fuera del entorno local", "Prueba de conexion cifrada"],
    ["Autenticacion", "Token de servicio con capacidades", "Separar tokens de usuarios y sistemas", "401/403 cubiertos"],
    ["Secretos", "Solo variables de entorno", "Nunca incluir tokens en Git o logs", "Revision automatizada/manual"],
    ["Webhooks", "HMAC-SHA256 + timestamp", "Ventana contra replay y firma constante", "Firma invalida rechazada"],
    ["Idempotencia", "Clave unica por alta", "Misma clave devuelve mismo recurso", "Prueba de POST duplicado"],
    ["Timeout", "Conexion y respuesta limitadas", "No bloquear indefinidamente la UI", "Fallo visible y recuperable"],
    ["Reintentos", "Solo 5xx, timeout y red", "Backoff; no reintentar 4xx funcionales", "Contador y ultimo error"],
    ["Auditoria", "Payload hash, actor, sistema, fecha y correlacion", "Ocultar datos sensibles en logs", "Trazabilidad extremo a extremo"],
    ["Disponibilidad", "Cache de catalogo y modo degradado", "No crear solicitud definitiva sin validar", "Mensaje claro al usuario"],
    ["Datos clinicos", "Minimizacion y control de acceso", "Enviar solo campos requeridos", "Revision de payload"],
]
story.append(make_table(
    ["Control", "Implementacion", "Regla", "Validacion"], security_rows,
    [42 * mm, 77 * mm, 83 * mm, 50 * mm]
))
story.append(Spacer(1, 6 * mm))
story.append(P("Registro minimo de cada intercambio", "H2x"))
story.append(bullets([
    "correlation_id e idempotency_key.",
    "Sistema origen y destino.",
    "Endpoint, metodo y codigo HTTP.",
    "Identificador externo e identificador remoto.",
    "Hash del payload, no necesariamente el payload clinico completo.",
    "Numero de intento, duracion, fecha y ultimo error sanitizado."
]))
story.append(PageBreak())

# 10 test matrix
story.append(P("10. Matriz de pruebas y criterios de salida", "H1x"))
test_rows = [
    ["T-01", "Catalogo NPT", "Unidad mapeada", "Productos habilitados con codigos externos", "Automatizada"],
    ["T-02", "Catalogo por unidad", "Unidad sin mapeo", "404/422 MEDICAL_UNIT_NOT_MAPPED", "Automatizada"],
    ["T-03", "Prevalidacion", "Composicion viable", "valid=true sin crear registros", "Automatizada"],
    ["T-04", "Prevalidacion", "Inventario insuficiente", "422 con producto y cantidad disponible", "Automatizada"],
    ["T-05", "Alta NPT", "Solicitud valida", "Un registro CBTA y vinculo Dr. Sam", "Integracion"],
    ["T-06", "Alta oncologica", "Solicitud con varias mezclas", "Solicitud y mezclas creadas correctamente", "Integracion"],
    ["T-07", "Idempotencia", "Repetir POST", "No duplica; devuelve mismo folio", "Integracion"],
    ["T-08", "Autenticacion", "Token ausente o invalido", "401 sin informacion interna", "Seguridad"],
    ["T-09", "Autorizacion", "Token sin capacidad", "403", "Seguridad"],
    ["T-10", "Webhook", "Evento repetido", "Se procesa una sola vez", "Integracion"],
    ["T-11", "Orden de eventos", "reviewed antes de prepared tardio", "No retrocede estado", "Integracion"],
    ["T-12", "Cancelacion", "Solicitud pendiente", "Cancela y libera reserva", "Integracion"],
    ["T-13", "Cancelacion", "Solicitud ya preparada", "409 con razon", "Integracion"],
    ["T-14", "Recuperacion", "CBTA no disponible", "Dr. Sam registra error y permite reintento", "Resiliencia"],
    ["T-15", "Remision", "Solicitud autorizada", "Documento protegido y consistente", "Integracion"],
    ["T-16", "Regresion CBTA", "Flujo web existente", "Sigue creando y procesando solicitudes", "Regresion"],
    ["T-17", "Regresion Dr. Sam", "Flujos actuales", "Pruebas existentes continúan aprobadas", "Regresion"],
]
story.append(make_table(
    ["ID", "Area", "Escenario", "Resultado esperado", "Tipo"], test_rows,
    [14 * mm, 42 * mm, 67 * mm, 96 * mm, 33 * mm]
))
story.append(Spacer(1, 5 * mm))
story.append(P("Criterio de salida del MVP", "H2x"))
story.append(P(
    "El MVP puede pasar a un entorno de pruebas compartido cuando T-01 a T-14 y las regresiones T-16/T-17 aprueben, no existan errores de severidad alta y sea posible rastrear una solicitud completa desde Dr. Sam hasta CBTA con un unico correlation_id.",
    "Callout"
))
story.append(PageBreak())

# 11 deployment
story.append(P("11. Estrategia de ambientes, despliegue y reversa", "H1x"))
deploy_rows = [
    ["Local", "Dos aplicaciones y dos bases independientes", "Desarrollo de contrato y pruebas automatizadas", "Datos ficticios"],
    ["Integracion", "URLs HTTPS accesibles entre si", "Pruebas extremo a extremo", "Tokens exclusivos y datos controlados"],
    ["Preproduccion", "Configuracion equivalente a produccion", "Aceptacion de usuarios y rendimiento", "Sin datos reales innecesarios"],
    ["Produccion", "Dominios, SSL, backups y monitoreo", "Liberacion gradual", "Credenciales rotadas"],
]
story.append(make_table(
    ["Ambiente", "Configuracion", "Uso", "Condicion"], deploy_rows,
    [38 * mm, 83 * mm, 77 * mm, 54 * mm]
))
story.append(Spacer(1, 5 * mm))
rollout_rows = [
    ["R1", "Desplegar migraciones compatibles", "Sin activar envio automatico", "Revertir codigo; mantener columnas no destructivas"],
    ["R2", "Activar catalogos", "Solo lectura", "Deshabilitar feature flag"],
    ["R3", "Activar prevalidacion", "Una unidad piloto", "Volver a captura sin prevalidacion remota"],
    ["R4", "Activar alta definitiva", "Una unidad y tipos controlados", "Detener envio; conservar solicitudes locales"],
    ["R5", "Activar webhooks", "Comparar contra consulta manual", "Desactivar receptor y usar polling temporal"],
    ["R6", "Ampliar unidades", "Despues de periodo estable", "Desactivar por unidad"],
]
story.append(P("Liberacion gradual", "H2x"))
story.append(make_table(
    ["Paso", "Accion", "Alcance", "Reversa"], rollout_rows,
    [18 * mm, 68 * mm, 76 * mm, 90 * mm]
))
story.append(Spacer(1, 5 * mm))
story.append(P(
    "Todas las funciones nuevas deben quedar detras de banderas de funcionalidad por ambiente y, cuando sea posible, por unidad medica. La reversa no debe requerir eliminar datos ni ejecutar migraciones destructivas.",
    "Warning"
))
story.append(PageBreak())

# 12 timeline and checklist
story.append(P("12. Secuencia recomendada de trabajo", "H1x"))
timeline_rows = [
    ["Bloque A", "Descubrimiento y decisiones", "0.1-0.4, D-01 a D-10", "Contrato funcional aprobado"],
    ["Bloque B", "Contrato API y modelos de integracion", "1.1-2.4", "OpenAPI, migraciones y mapeos"],
    ["Bloque C", "Refactor controlado en CBTA", "3.1-3.3", "Servicios reutilizables sin regresion web"],
    ["Bloque D", "API interna CBTA", "4.1-4.7", "Catalogos, prevalidacion, alta, estado y cancelacion"],
    ["Bloque E", "Cliente e interfaz Dr. Sam", "5.1-5.6", "Captura con catalogo, prevalidacion y sincronizacion"],
    ["Bloque F", "Eventos y documentos", "6.1-7.2", "Webhooks, estados, remision y entrega"],
    ["Bloque G", "Pruebas y piloto", "T-01 a T-17, R1-R6", "Aceptacion de unidad piloto"],
]
story.append(make_table(
    ["Bloque", "Objetivo", "Pasos", "Salida"], timeline_rows,
    [28 * mm, 68 * mm, 71 * mm, 85 * mm]
))
story.append(Spacer(1, 6 * mm))
story.append(P("Punto de inicio inmediato", "H2x"))
story.append(P(
    "Comenzar con un taller de definicion y un inventario tecnico. El primer cambio de codigo debe realizarse solo despues de aprobar el identificador externo, el mapeo de unidades, los codigos de catalogo, el mapa de estados y el momento exacto de envio.",
    "Callout"
))
check_rows = [
    [status_badge("Pendiente", AMBER, AMBER_LIGHT), "Confirmar ramas API activas y estado limpio o documentado en ambos repositorios."],
    [status_badge("Pendiente", AMBER, AMBER_LIGHT), "Seleccionar una unidad medica piloto existente en ambos sistemas."],
    [status_badge("Pendiente", AMBER, AMBER_LIGHT), "Seleccionar una solicitud NPT y una oncologica como casos de referencia."],
    [status_badge("Pendiente", AMBER, AMBER_LIGHT), "Aprobar nombres y codigos externos de catalogos y unidades."],
    [status_badge("Pendiente", AMBER, AMBER_LIGHT), "Redactar openapi.yaml antes de crear controladores de API."],
    [status_badge("Pendiente", AMBER, AMBER_LIGHT), "Definir credenciales independientes para desarrollo e integracion."],
]
check = Table(check_rows, colWidths=[30 * mm, 218 * mm])
check.setStyle(TableStyle([
    ("VALIGN", (0, 0), (-1, -1), "MIDDLE"),
    ("BOX", (0, 0), (-1, -1), 0.5, GRAY_300),
    ("INNERGRID", (0, 0), (-1, -1), 0.4, GRAY_300),
    ("BACKGROUND", (0, 0), (-1, -1), colors.white),
    ("LEFTPADDING", (0, 0), (-1, -1), 5),
    ("RIGHTPADDING", (0, 0), (-1, -1), 5),
    ("TOPPADDING", (0, 0), (-1, -1), 5),
    ("BOTTOMPADDING", (0, 0), (-1, -1), 5),
]))
story.append(P("Lista de arranque", "H2x"))
story.append(check)
story.append(PageBreak())

# 13 Risks
story.append(P("13. Matriz de riesgos", "H1x"))
risk_rows = [
    ["R-01", "IDs internos usados como contrato", "Alta", "Alta", "Codigos externos y pruebas de estabilidad", "CBTA"],
    ["R-02", "Unidad Dr. Sam sin hospital CBTA", "Alta", "Media", "Mapeo obligatorio y error explicito", "Ambos"],
    ["R-03", "Inventario cambia entre consulta y alta", "Alta", "Alta", "Revalidar y reservar en alta definitiva", "CBTA"],
    ["R-04", "Duplicado por timeout", "Alta", "Media", "Idempotency-Key y restriccion unica", "Ambos"],
    ["R-05", "Estados incompatibles o fuera de orden", "Alta", "Media", "Estados canonicos, version y mapper", "Ambos"],
    ["R-06", "Refactor rompe flujo web CBTA", "Alta", "Media", "Servicios compartidos y regresion automatizada", "CBTA"],
    ["R-07", "Datos clinicos en logs", "Alta", "Baja", "Sanitizacion y hash de payload", "Ambos"],
    ["R-08", "CBTA temporalmente no disponible", "Media", "Media", "Timeout, reintento y estado visible", "Dr. Sam"],
    ["R-09", "Diferencias de codificacion/estados", "Media", "Media", "UTF-8 y normalizacion de enums", "CBTA"],
    ["R-10", "Ampliacion prematura del alcance", "Media", "Alta", "MVP y control formal de cambios", "Lider"],
]
story.append(make_table(
    ["ID", "Riesgo", "Impacto", "Probabilidad", "Mitigacion", "Responsable"], risk_rows,
    [14 * mm, 67 * mm, 25 * mm, 30 * mm, 83 * mm, 33 * mm]
))
story.append(Spacer(1, 7 * mm))
story.append(P("Cierre", "H2x"))
story.append(P(
    "El proyecto debe avanzar por contratos y responsabilidades, no por conexiones directas a tablas. El primer hito de valor es consultar catalogos CBTA desde Dr. Sam; el segundo es prevalidar; el tercero es registrar una solicitud idempotente. Los estados, documentos y entrega se incorporan despues de estabilizar ese camino principal.",
    "Callout"
))
story.append(P(
    "Este documento funciona como linea base. Cualquier cambio en propiedad de datos, reserva de inventario, autorizaciones requeridas o alcance de documentos debe registrarse antes de modificar el contrato API.",
    "Bodyx"
))

doc.build(story, onFirstPage=header_footer, onLaterPages=header_footer)
print(OUT)

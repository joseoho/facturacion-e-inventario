# -*- coding: utf-8 -*-
"""Genera el Manual de Usuario en DOCX para Facturación e Inventario."""

from docx import Document
from docx.enum.section import WD_SECTION
from docx.enum.table import WD_TABLE_ALIGNMENT
from docx.enum.text import WD_ALIGN_PARAGRAPH, WD_TAB_ALIGNMENT
from docx.oxml import OxmlElement
from docx.oxml.ns import qn
from docx.shared import Mm, Pt, RGBColor

# ============================================================
# Paleta y medidas
# ============================================================
PRIMARY = RGBColor(0x1F, 0x38, 0x64)
ACCENT = RGBColor(0x2E, 0x75, 0xB6)
GRAY = RGBColor(0x59, 0x59, 0x59)
LIGHT = RGBColor(0x7F, 0x7F, 0x7F)
WHITE = RGBColor(0xFF, 0xFF, 0xFF)
TEXT = RGBColor(0x26, 0x26, 0x26)

PRIMARY_HEX = "1F3864"
ACCENT_HEX = "2E75B6"
ROW_ALT_HEX = "F2F5FA"
BORDER_HEX = "D9D9D9"

CONTENT_WIDTH = 170  # mm

doc = Document()

# ============================================================
# Propiedades y estilos
# ============================================================
props = doc.core_properties
props.title = "Manual de Usuario - Facturación e Inventario"
props.subject = "Guía de uso del sistema"
props.author = "Sistema de Facturación e Inventario"
props.language = "es-VE"
props.keywords = "facturación, inventario, egresos, manual"

styles = doc.styles

normal = styles["Normal"]
normal.font.name = "Calibri"
normal.font.size = Pt(10.5)
normal.font.color.rgb = TEXT
normal.paragraph_format.space_after = Pt(4)
normal.paragraph_format.line_spacing = 1.12

for name, size, color, before, after in [
    ("Heading 1", 17, PRIMARY, 16, 6),
    ("Heading 2", 12.5, PRIMARY, 12, 4),
    ("Heading 3", 11, ACCENT, 10, 3),
]:
    st = styles[name]
    st.font.name = "Calibri"
    st.font.size = Pt(size)
    st.font.bold = True
    st.font.color.rgb = color
    st.paragraph_format.space_before = Pt(before)
    st.paragraph_format.space_after = Pt(after)
    st.paragraph_format.keep_with_next = True

styles["Title"].font.name = "Calibri"
styles["Title"].font.size = Pt(34)
styles["Title"].font.color.rgb = PRIMARY

bullet = styles["List Bullet"]
bullet.font.name = "Calibri"
bullet.font.size = Pt(10.5)
bullet.paragraph_format.space_after = Pt(2)

footer_style = styles["Footer"]
footer_style.paragraph_format.tab_stops.clear_all()
footer_style.paragraph_format.tab_stops.add_tab_stop(Mm(170), WD_TAB_ALIGNMENT.RIGHT)

# ============================================================
# Sección / página
# ============================================================
section = doc.sections[0]
section.page_width = Mm(210)
section.page_height = Mm(297)
section.top_margin = Mm(22)
section.bottom_margin = Mm(20)
section.left_margin = Mm(20)
section.right_margin = Mm(20)
section.header_distance = Mm(10)
section.footer_distance = Mm(10)
section.different_first_page_header_footer = True


# ============================================================
# Utilidades
# ============================================================
def shade(cell, hex_color):
    tc_pr = cell._tc.get_or_add_tcPr()
    shd = OxmlElement("w:shd")
    shd.set(qn("w:val"), "clear")
    shd.set(qn("w:color"), "auto")
    shd.set(qn("w:fill"), hex_color)
    tc_pr.append(shd)


def cell_margins(table, top=60, bottom=60, left=90, right=90):
    tbl_pr = table._tbl.tblPr
    mar = OxmlElement("w:tblCellMar")
    for side, value in (("top", top), ("left", left), ("bottom", bottom), ("right", right)):
        el = OxmlElement(f"w:{side}")
        el.set(qn("w:w"), str(value))
        el.set(qn("w:type"), "dxa")
        mar.append(el)
    tbl_pr.append(mar)


def borders(table, color=BORDER_HEX, size=4):
    tbl_pr = table._tbl.tblPr
    b = OxmlElement("w:tblBorders")
    for edge in ("top", "left", "bottom", "right", "insideH", "insideV"):
        el = OxmlElement(f"w:{edge}")
        el.set(qn("w:val"), "single")
        el.set(qn("w:sz"), str(size))
        el.set(qn("w:space"), "0")
        el.set(qn("w:color"), color)
        b.append(el)
    tbl_pr.append(b)


def repeat_header(row):
    tr_pr = row._tr.get_or_add_trPr()
    header = OxmlElement("w:tblHeader")
    header.set(qn("w:val"), "true")
    tr_pr.append(header)


def add_rich(paragraph, text, size=None, color=None):
    """Interpreta **negrita** dentro del texto."""
    parts = text.split("**")

    for index, part in enumerate(parts):
        if not part:
            continue
        run = paragraph.add_run(part)
        run.bold = index % 2 == 1
        if size:
            run.font.size = Pt(size)
        if color:
            run.font.color.rgb = color


def p(text="", size=None, space_after=4, italic=False, color=None, align=None):
    paragraph = doc.add_paragraph()
    paragraph.paragraph_format.space_after = Pt(space_after)
    if align is not None:
        paragraph.alignment = align
    add_rich(paragraph, text, size=size, color=color)
    if italic:
        for run in paragraph.runs:
            run.italic = True
    return paragraph


def h1(text):
    heading = doc.add_heading(text, level=1)
    add_bottom_border(heading, PRIMARY_HEX, 8)
    return heading


def h2(text):
    return doc.add_heading(text, level=2)


def h3(text):
    return doc.add_heading(text, level=3)


def add_bottom_border(paragraph, color, size=6):
    p_pr = paragraph._p.get_or_add_pPr()
    p_bdr = OxmlElement("w:pBdr")
    bottom = OxmlElement("w:bottom")
    bottom.set(qn("w:val"), "single")
    bottom.set(qn("w:sz"), str(size))
    bottom.set(qn("w:space"), "4")
    bottom.set(qn("w:color"), color)
    p_bdr.append(bottom)
    p_pr.append(p_bdr)


def bullets(items):
    for item in items:
        paragraph = doc.add_paragraph(style="List Bullet")
        add_rich(paragraph, item)


def steps(items, start=1):
    for offset, item in enumerate(items):
        paragraph = doc.add_paragraph()
        paragraph.paragraph_format.left_indent = Mm(9)
        paragraph.paragraph_format.first_line_indent = Mm(-6)
        paragraph.paragraph_format.space_after = Pt(3)
        number = paragraph.add_run(f"{start + offset}.  ")
        number.bold = True
        number.font.color.rgb = ACCENT
        add_rich(paragraph, item)


def table(headers, rows, widths, font_size=9.2, align_right=None):
    align_right = align_right or []

    t = doc.add_table(rows=1, cols=len(headers))
    t.style = "Table Grid"
    t.alignment = WD_TABLE_ALIGNMENT.CENTER
    t.autofit = False

    borders(t)
    cell_margins(t)

    header_cells = t.rows[0].cells

    for i, header in enumerate(headers):
        header_cells[i].width = Mm(widths[i])
        paragraph = header_cells[i].paragraphs[0]
        paragraph.paragraph_format.space_after = Pt(0)
        run = paragraph.add_run(header)
        run.bold = True
        run.font.size = Pt(font_size)
        run.font.color.rgb = WHITE
        shade(header_cells[i], PRIMARY_HEX)

    repeat_header(t.rows[0])

    for row_index, row in enumerate(rows):
        cells = t.add_row().cells

        for i, value in enumerate(row):
            cells[i].width = Mm(widths[i])
            paragraph = cells[i].paragraphs[0]
            paragraph.paragraph_format.space_after = Pt(0)

            if i in align_right:
                paragraph.alignment = WD_ALIGN_PARAGRAPH.RIGHT

            add_rich(paragraph, str(value), size=font_size)

            if row_index % 2 == 1:
                shade(cells[i], ROW_ALT_HEX)

    doc.add_paragraph().paragraph_format.space_after = Pt(2)
    return t


def callout(title, text, bg="FFF4CE", color="7F6000"):
    t = doc.add_table(rows=1, cols=1)
    t.alignment = WD_TABLE_ALIGNMENT.CENTER
    t.autofit = False
    cell_margins(t, top=100, bottom=100, left=140, right=140)

    cell = t.cell(0, 0)
    cell.width = Mm(CONTENT_WIDTH)
    shade(cell, bg)

    paragraph = cell.paragraphs[0]
    paragraph.paragraph_format.space_after = Pt(0)

    run = paragraph.add_run(f"{title}: ")
    run.bold = True
    run.font.size = Pt(9.5)
    run.font.color.rgb = RGBColor.from_string(color)

    add_rich(paragraph, text, size=9.5)

    doc.add_paragraph().paragraph_format.space_after = Pt(2)


def field(paragraph, instruction):
    run = paragraph.add_run()

    begin = OxmlElement("w:fldChar")
    begin.set(qn("w:fldCharType"), "begin")

    instr = OxmlElement("w:instrText")
    instr.set(qn("xml:space"), "preserve")
    instr.text = instruction

    separate = OxmlElement("w:fldChar")
    separate.set(qn("w:fldCharType"), "separate")

    text = OxmlElement("w:t")
    text.text = "1"

    end = OxmlElement("w:fldChar")
    end.set(qn("w:fldCharType"), "end")

    run._r.append(begin)
    run._r.append(instr)
    run._r.append(separate)
    run._r.append(text)
    run._r.append(end)


# ============================================================
# Encabezado y pie
# ============================================================
header = section.header
header_paragraph = header.paragraphs[0]
header_paragraph.alignment = WD_ALIGN_PARAGRAPH.RIGHT
header_run = header_paragraph.add_run("Sistema de Facturación e Inventario")
header_run.font.size = Pt(8)
header_run.font.color.rgb = LIGHT
add_bottom_border(header_paragraph, BORDER_HEX, 4)

footer = section.footer
footer_paragraph = footer.paragraphs[0]
footer_paragraph.paragraph_format.tab_stops.clear_all()
footer_paragraph.paragraph_format.tab_stops.add_tab_stop(Mm(170), WD_TAB_ALIGNMENT.RIGHT)

footer_run = footer_paragraph.add_run("Manual de Usuario  ·  v1.0")
footer_run.font.size = Pt(8)
footer_run.font.color.rgb = LIGHT

footer_paragraph.add_run("\t")

page_label = footer_paragraph.add_run("Página ")
page_label.font.size = Pt(8)
page_label.font.color.rgb = LIGHT

field(footer_paragraph, " PAGE ")

of_label = footer_paragraph.add_run(" de ")
of_label.font.size = Pt(8)
of_label.font.color.rgb = LIGHT

field(footer_paragraph, " NUMPAGES ")

# ============================================================
# Portada
# ============================================================
for _ in range(5):
    doc.add_paragraph()

cover_title = doc.add_paragraph()
cover_title.alignment = WD_ALIGN_PARAGRAPH.LEFT
title_run = cover_title.add_run("Manual de Usuario")
title_run.font.size = Pt(38)
title_run.font.bold = True
title_run.font.color.rgb = PRIMARY

cover_subtitle = doc.add_paragraph()
subtitle_run = cover_subtitle.add_run("Sistema de Facturación e Inventario")
subtitle_run.font.size = Pt(18)
subtitle_run.font.color.rgb = ACCENT

line = doc.add_paragraph()
add_bottom_border(line, ACCENT_HEX, 12)

doc.add_paragraph()
cover_text = doc.add_paragraph()
cover_text_run = cover_text.add_run(
    "Guía práctica para administradores y vendedores: facturación en varias monedas, "
    "control de inventario, egresos y reportes."
)
cover_text_run.font.size = Pt(11)
cover_text_run.font.color.rgb = GRAY

for _ in range(7):
    doc.add_paragraph()

meta = doc.add_table(rows=2, cols=2)
meta.autofit = False
meta.alignment = WD_TABLE_ALIGNMENT.LEFT
meta_values = [
    ("Versión", "1.0"),
    ("Fecha", "Octubre 2026"),
]
for row_index, (label, value) in enumerate(meta_values):
    cells = meta.rows[row_index].cells
    cells[0].width = Mm(30)
    cells[1].width = Mm(80)
    label_run = cells[0].paragraphs[0].add_run(label)
    label_run.bold = True
    label_run.font.size = Pt(9)
    label_run.font.color.rgb = LIGHT
    value_run = cells[1].paragraphs[0].add_run(value)
    value_run.font.size = Pt(9)
    value_run.font.color.rgb = GRAY

doc.add_page_break()

# ============================================================
# Índice
# ============================================================
index_heading = doc.add_heading("Contenido", level=1)
add_bottom_border(index_heading, PRIMARY_HEX, 8)

index_rows = [
    ("1", "Introducción"),
    ("2", "Acceso al sistema"),
    ("3", "Roles y permisos"),
    ("4", "Navegación y dashboard"),
    ("5", "Clientes"),
    ("6", "Productos y precios"),
    ("7", "Categorías"),
    ("8", "Monedas"),
    ("9", "Tasas de cambio"),
    ("10", "Facturación"),
    ("11", "Egresos"),
    ("12", "Categorías de egreso"),
    ("13", "Reportes"),
    ("14", "Preguntas frecuentes"),
    ("15", "Buenas prácticas"),
    ("16", "Glosario"),
    ("A", "Anexo: credenciales y soporte"),
]

index_table = doc.add_table(rows=1, cols=2)
index_table.autofit = False
index_table.alignment = WD_TABLE_ALIGNMENT.LEFT
cell_margins(index_table, top=16, bottom=16, left=0, right=0)

# Anchos de columna explícitos: sin esto LibreOffice reparte 50/50.
grid_cols = index_table._tbl.tblGrid.findall(qn("w:gridCol"))
if len(grid_cols) == 2:
    grid_cols[0].set(qn("w:w"), str(Mm(14).twips))
    grid_cols[1].set(qn("w:w"), str(Mm(156).twips))

for position, (number, title) in enumerate(index_rows):
    cells = index_table.rows[0].cells if position == 0 else index_table.add_row().cells
    cells[0].width = Mm(14)
    cells[1].width = Mm(156)

    number_paragraph = cells[0].paragraphs[0]
    number_paragraph.paragraph_format.space_after = Pt(1)
    number_run = number_paragraph.add_run(number)
    number_run.bold = True
    number_run.font.color.rgb = ACCENT
    number_run.font.size = Pt(10)

    title_paragraph = cells[1].paragraphs[0]
    title_paragraph.paragraph_format.space_after = Pt(1)
    title_run = title_paragraph.add_run(title)
    title_run.font.size = Pt(10)

p()
callout(
    "Cómo usar este manual",
    "Los módulos marcados como **Solo admin** requieren el rol de administrador. "
    "Las instrucciones usan el nombre exacto de los botones y campos que verás en pantalla.",
    bg="E8F1FB",
    color="1F3864",
)

# ============================================================
# 1. Introducción
# ============================================================
h1("1. Introducción")

p(
    "**Facturación e Inventario** es un sistema interno para registrar ventas, controlar el "
    "inventario en kilogramos, administrar egresos y consultar reportes. Está pensado para "
    "operar en varias monedas (USD, BS y COP) usando una tasa de cambio que queda guardada "
    "en cada documento."
)

h2("1.1 ¿Qué puedes hacer con el sistema?")
bullets(
    [
        "Emitir facturas con uno o varios productos, con IVA por producto.",
        "Facturar en dólares, bolívares o pesos colombianos, dejando registrada la tasa usada.",
        "Descontar stock automáticamente al vender y reponerlo al anular.",
        "Registrar egresos (compras, servicios, sueldos) y, si corresponde, ingresar mercancía.",
        "Consultar el dashboard, reportes de inventario, stock bajo y ventas diarias.",
        "Administrar clientes, productos, categorías, monedas y tasas de cambio.",
    ]
)

h2("1.2 ¿Qué no hace?")
p(
    "El sistema no emite facturación electrónica ni se conecta con entes externos (SENIAT, "
    "bancos o pasarelas de pago). Los pagos se registran de forma manual."
)

h2("1.3 Cómo está organizado")
table(
    ["Sección del menú", "Módulos", "Acceso"],
    [
        ("Principal", "Dashboard", "Todos"),
        ("Facturación", "Facturas", "Todos"),
        ("Inventario", "Productos, Categorías", "Ver: todos · Gestionar: admin"),
        ("Clientes", "Clientes", "Ver: todos · Gestionar: admin"),
        ("Egresos", "Egresos, Categorías de egreso", "Solo admin"),
        ("Administración", "Tasas de cambio, Monedas", "Solo admin"),
        ("Reportes", "Inventario, Stock bajo, Ventas diarias", "Solo admin"),
        ("Sistema", "Cerrar sesión", "Todos"),
    ],
    widths=[45, 80, 45],
)

# ============================================================
# 2. Acceso
# ============================================================
h1("2. Acceso al sistema")

h2("2.1 Iniciar sesión")
steps(
    [
        "Abre el navegador y entra a la dirección que te indique el administrador "
        "(por ejemplo, **http://facturacion-inventario.test**).",
        "Escribe tu **correo electrónico** y tu **contraseña**.",
        "Pulsa **Login**.",
        "Si los datos son correctos, entrarás al **Dashboard**.",
    ]
)

callout(
    "Importante",
    "Solo pueden iniciar sesión los usuarios con estado **activo**. Si tu cuenta está "
    "inactiva, el sistema rechazará el acceso; pide al administrador que la active.",
)

h2("2.2 Cerrar sesión")
p(
    "En el menú lateral, al final de la sección **Sistema**, pulsa **Cerrar Sesión**. "
    "Cierra el navegador si usas un equipo compartido."
)

h2("2.3 Cambiar tu contraseña")
p(
    "Puedes cambiar tu contraseña en cualquier momento, sin depender del correo."
)
steps(
    [
        "En el menú lateral, sección **Sistema**, pulsa **Cambiar contraseña**.",
        "Escribe tu **contraseña actual**.",
        "Escribe la **nueva contraseña** (mínimo 8 caracteres, con letras y números) y repítela.",
        "Pulsa **Guardar contraseña**.",
    ]
)

callout(
    "Recomendación",
    "Cambia la contraseña la primera vez que entres, sobre todo la del administrador inicial.",
    bg="E2EFDA",
    color="375623",
)

h2("2.4 Contraseña olvidada")
p(
    "En la pantalla de acceso pulsa **¿Olvidaste tu contraseña?** y sigue las instrucciones. "
    "El envío de correo debe estar configurado por el administrador del sistema."
)

callout(
    "Seguridad",
    "No compartas tu usuario ni tu contraseña. Cada movimiento queda registrado con el "
    "usuario que lo realizó.",
    bg="FCE4D6",
    color="833C0B",
)

# ============================================================
# 3. Roles
# ============================================================
h1("3. Roles y permisos")

h2("3.1 Roles del sistema")

p("El sistema tiene dos roles: **admin** y **vendedor**.")

table(
    ["Módulo / acción", "Admin", "Vendedor"],
    [
        ("Dashboard", "Sí (incluye finanzas)", "Sí (sin finanzas)"),
        ("Facturas: ver y crear", "Sí", "Sí"),
        ("Facturas: marcar como pagada", "Sí", "Sí, si está pendiente"),
        ("Facturas: anular", "Sí", "No"),
        ("Clientes: ver", "Sí", "Sí"),
        ("Clientes: crear, editar, eliminar", "Sí", "No"),
        ("Productos: ver", "Sí", "Sí"),
        ("Productos: crear, editar, precios, eliminar", "Sí", "No"),
        ("Categorías: ver", "Sí", "Sí"),
        ("Categorías: crear, editar, eliminar", "Sí", "No"),
        ("Tasas de cambio y monedas", "Sí", "No"),
        ("Egresos y categorías de egreso", "Sí", "No"),
        ("Usuarios (crear, rol, activar)", "Sí", "No"),
        ("Reportes", "Sí", "No"),
    ],
    widths=[85, 45, 40],
)

callout(
    "Nota",
    "Si un vendedor intenta abrir una sección solo para administradores, el sistema lo "
    "devuelve al Dashboard con un aviso.",
    bg="E8F1FB",
    color="1F3864",
)

h2("3.2 Gestión de usuarios (Solo admin)")
p(
    "En **Administración → Usuarios** el administrador da de alta a las personas que usarán "
    "el sistema y controla su acceso."
)
steps(
    [
        "Pulsa **Nuevo Usuario**.",
        "Completa **Nombre**, **Correo** (único), **Rol** y **Contraseña** "
        "(mínimo 8 caracteres, con letras y números).",
        "Deja **Usuario activo** marcado y pulsa **Crear usuario**.",
    ]
)
p(
    "En el listado puedes **editar** a cualquier usuario, **activarlo o desactivarlo** con el "
    "botón de la persona y **resetear su contraseña**: escribe una nueva en el formulario de "
    "edición (si la dejas en blanco, se conserva la actual)."
)
callout(
    "Reglas",
    "No puedes desactivar tu propia cuenta ni quitarte el rol de administrador, y siempre "
    "debe quedar al menos un administrador activo. Un usuario desactivado no puede iniciar "
    "sesión y, si ya estaba dentro, su sesión se cierra.",
    bg="FCE4D6",
    color="833C0B",
)

# ============================================================
# 4. Navegación y dashboard
# ============================================================
h1("4. Navegación y dashboard")

h2("4.1 El menú lateral")
p(
    "Agrupa los módulos por área. La opción activa se resalta. En pantallas pequeñas el "
    "menú se oculta y se abre con el botón de tres líneas de la barra superior."
)

h2("4.2 La barra superior")
p(
    "Muestra el título de la página, tu nombre, tu rol y las iniciales de tu usuario. "
    "El botón de tres líneas abre o cierra el menú en móviles."
)

h2("4.3 Dashboard")
p("Es la pantalla inicial. Resume la operación del negocio:")

bullets(
    [
        "**Ventas de hoy** y número de facturas emitidas hoy.",
        "**Ventas del mes**, comparación con el mes anterior y facturas pendientes.",
        "**Clientes nuevos**, productos sin stock, stock bajo y total de productos y clientes.",
        "**Facturación del mes anterior**: pagadas, pendientes y anuladas.",
        "**Ventas de la semana** y **facturas recientes**.",
    ]
)

p(
    "Los usuarios **admin** ven además: **Egresos de hoy**, **Egresos del mes**, "
    "**Utilidad del mes**, **Margen del mes** y el comparativo **Ventas vs Egresos** de los "
    "últimos seis meses."
)

callout(
    "Cómo se calculan utilidad y margen",
    "Se convierten a USD las facturas pagadas y los egresos pagados del mes. "
    "**Utilidad = ventas pagadas − egresos pagados**. "
    "**Margen = utilidad ÷ ventas × 100**.",
    bg="E2EFDA",
    color="375623",
)

h2("4.4 Flujo diario recomendado")
steps(
    [
        "Revisa y actualiza las **tasas de cambio** del día (admin).",
        "Verifica **stock bajo** y repón mercancía si hace falta.",
        "Emite las **facturas** del día.",
        "Registra **pagos** de facturas y **egresos**.",
        "Al cierre, revisa el **Dashboard** y los **reportes**.",
    ]
)

# ============================================================
# 5. Clientes
# ============================================================
h1("5. Clientes")

h2("5.1 Listado")
p(
    "Muestra los clientes con búsqueda por nombre, documento, correo o tipo de documento, "
    "y filtro por estado (activos o inactivos)."
)

h2("5.2 Crear un cliente (Solo admin)")
steps(
    [
        "Entra a **Clientes** y pulsa **Nuevo Cliente**.",
        "Completa los campos obligatorios: **Nombre**, **Documento**, **Tipo de documento** "
        "y **Tipo de cliente**.",
        "Completa los datos opcionales: dirección, teléfono, correo, contacto, límite de "
        "crédito, días de crédito y notas.",
        "Deja marcado **Activo** si el cliente puede operar.",
        "Pulsa **Guardar**.",
    ]
)

table(
    ["Campo", "Descripción"],
    [
        ("Nombre", "Nombre o razón social. Obligatorio."),
        ("Documento", "RIF, cédula u otro. No se puede repetir."),
        ("Tipo de documento", "J, V, G, E, CI u OTRO."),
        ("Correo", "Opcional y único. Se usa para contacto."),
        ("Tipo de cliente", "minorista, mayorista o corporativo."),
        ("Límite y días de crédito", "Datos informativos para la gestión de cobro."),
        ("Activo", "Si está desmarcado, el cliente no aparecerá en el formulario de factura."),
    ],
    widths=[45, 125],
)

h2("5.3 Ver y editar")
p(
    "El botón del ojo abre la ficha del cliente con sus datos, total facturado, total pagado, "
    "saldo pendiente y sus últimas facturas. El lápiz permite editarlo."
)

h2("5.4 Eliminar")
p(
    "No se puede eliminar un cliente que tenga facturas asociadas. En ese caso, desactívalo "
    "para que no se use en nuevas ventas."
)

# ============================================================
# 6. Productos
# ============================================================
h1("6. Productos y precios")

h2("6.1 Listado")
p(
    "Permite buscar por nombre, SKU o descripción, filtrar por categoría, estado y stock bajo, "
    "y ver el stock actual de cada producto."
)

h2("6.2 Crear un producto (Solo admin)")
steps(
    [
        "Entra a **Productos** y pulsa **Nuevo Producto**.",
        "Indica **SKU**, **Nombre**, **Precio por KG (USD)**, **Stock inicial (Kg)**, "
        "**Categoría** e **IVA (%)**.",
        "Opcionalmente agrega descripción, stock mínimo e imagen.",
        "Si lo deseas, registra un precio en moneda local: elige moneda, precio y tasa.",
        "Marca **Activo** y pulsa **Guardar**.",
    ]
)

table(
    ["Campo", "Uso"],
    [
        ("SKU", "Código único del producto."),
        ("Precio por KG (USD)", "Precio base en dólares. Es la referencia para calcular las demás monedas."),
        ("Stock (Kg)", "Cantidad disponible, con hasta 3 decimales."),
        ("Stock mínimo", "Umbral para las alertas de stock bajo."),
        ("IVA (%)", "Porcentaje que se aplica en cada línea de factura."),
        ("Categoría", "Obligatoria. Se usa en reportes y en el dashboard."),
        ("Activo", "Solo los productos activos se pueden facturar."),
    ],
    widths=[45, 125],
)

h2("6.3 Precios por moneda")
p(
    "En el listado, el botón de precios abre el historial de precios del producto. Allí puedes "
    "agregar un precio para una moneda y una tasa concretas, o eliminar un precio existente."
)

callout(
    "Importante",
    "Al facturar, el sistema calcula el precio como **precio base USD × tasa vigente** y lo "
    "impone aunque el navegador envíe otro valor. El precio no se edita a mano en la factura.",
)

h2("6.4 Editar y eliminar")
p(
    "El lápiz permite editar. Al eliminar, el producto se desactiva lógicamente y deja de "
    "aparecer en las búsquedas de facturación, pero su historial se conserva."
)

# ============================================================
# 7. Categorías
# ============================================================
h1("7. Categorías")

p(
    "Las categorías agrupan productos y alimentan los reportes. El listado permite buscar por "
    "nombre o descripción y filtrar por estado."
)

h2("7.1 Crear o editar (Solo admin)")
steps(
    [
        "Entra a **Categorías** y pulsa **Nueva Categoría**.",
        "Escribe el **Nombre** (único) y, si quieres, una **Descripción**.",
        "Marca **Activo** y pulsa **Guardar**.",
    ]
)

p(
    "No se puede eliminar una categoría que tenga productos asociados."
)

# ============================================================
# 8. Monedas
# ============================================================
h1("8. Monedas (Solo admin)")

p(
    "El sistema trabaja con una **moneda base** (USD) y monedas secundarias. Solo las monedas "
    "activas aparecen en los formularios de facturación y egresos."
)

h2("8.1 Crear una moneda")
steps(
    [
        "Entra a **Monedas** y pulsa **Nueva Moneda**.",
        "Completa **Código** (tres letras, por ejemplo EUR), **Nombre** y **Símbolo**.",
        "Marca **Es base** solo si será la moneda de referencia (debe existir una sola).",
        "Deja **Activa** marcada y pulsa **Guardar**.",
    ]
)

callout(
    "Reglas",
    "Solo puede haber una moneda base. No se puede desactivar la única moneda base ni "
    "eliminar una moneda que tenga facturas o precios asociados.",
    bg="FCE4D6",
    color="833C0B",
)

# ============================================================
# 9. Tasas
# ============================================================
h1("9. Tasas de cambio (Solo admin)")

p(
    "La tasa define cuántas unidades de una moneda equivalen a un dólar. Es clave: sin tasa "
    "vigente no se puede facturar en BS, COP ni EUR."
)

h2("9.1 Registrar una tasa")
steps(
    [
        "Entra a **Tasas de Cambio** y pulsa **Nueva Tasa**.",
        "Selecciona la **Moneda**.",
        "Escribe la **Tasa** (mayor que cero).",
        "Indica la **Fecha** (no puede ser futura).",
        "Pulsa **Guardar**.",
    ]
)

p(
    "Si ya existe una tasa para esa moneda y fecha, el sistema la actualiza en lugar de crear "
    "un duplicado."
)

h2("9.2 Historial y exportación")
p(
    "El historial permite filtrar por moneda y rango de fechas. El botón de exportación "
    "descarga un archivo **CSV** con las tasas del filtro."
)

h2("9.3 Duplicar una tasa")
p(
    "Copia una tasa existente con la fecha de hoy. Útil para actualizar el valor del día "
    "partiendo del anterior."
)

h2("9.4 Actualizar precios")
p(
    "El botón **Actualizar precios** recalcula la tabla de precios por moneda usando la tasa "
    "seleccionada. La facturación siempre vuelve a calcular el precio con la tasa vigente, "
    "por lo que este proceso sirve como referencia y control."
)

callout(
    "Antes de facturar",
    "Confirma que la moneda de pago tenga una tasa registrada. Si no la tiene, el sistema "
    "rechazará la factura con el mensaje **No hay tasa de cambio vigente**.",
)

# ============================================================
# 10. Facturación
# ============================================================
h1("10. Facturación")

h2("10.1 Listado de facturas")
p(
    "Permite filtrar por **número**, **cliente**, **estado** y **moneda**. Cada fila muestra "
    "número, cliente, fecha, total, moneda y estado (pendiente, pagada o anulada)."
)

p("Acciones disponibles:")
bullets(
    [
        "**Ver** (ojo): abre el detalle.",
        "**Imprimir**: abre la factura en una pestaña lista para imprimir.",
        "**Pagar** (check): marca la factura como pagada.",
        "**Anular** (equis): solo administradores.",
    ]
)

h2("10.2 Crear una factura")
steps(
    [
        "Entra a **Facturación → Facturas** y pulsa **Nueva Factura**.",
        "Selecciona el **Cliente**.",
        "Selecciona la **Moneda** en el desplegable.",
        "Pulsa el botón de **Moneda de Pago** que corresponda (USD, BS o COP). Debe coincidir "
        "con la moneda seleccionada.",
        "Verifica la **Fecha de Emisión** (por defecto, hoy).",
        "Busca un producto por nombre o código (mínimo 2 letras) y pulsa sobre él para agregarlo.",
        "Ajusta la **Cantidad** en kilogramos. El sistema no permite superar el stock disponible.",
        "Repite la búsqueda para agregar más productos.",
        "Revisa el **Resumen**: productos, subtotal, IVA y total.",
        "Pulsa **Crear Factura**.",
    ]
)

callout(
    "Precios y totales",
    "El precio de cada producto lo calcula el sistema con el precio base en USD y la tasa "
    "vigente. El IVA se aplica según el producto. El total que ves en el resumen es el que se "
    "guardará.",
    bg="E2EFDA",
    color="375623",
)

h2("10.3 Reglas de la factura")
table(
    ["Elemento", "Comportamiento"],
    [
        ("Número", "Se genera automáticamente con formato FACT-00000001."),
        ("Moneda base (USD)", "No requiere tasa."),
        ("BS, COP, EUR", "Requieren una tasa vigente; si no existe, la factura se rechaza."),
        ("Stock", "Se valida y se descuenta al guardar. Si no alcanza, no se crea la factura."),
        ("Precio", "Lo impone el backend; un precio manipulado se ignora y se registra en el log."),
        ("Estado inicial", "Pendiente."),
        ("Tasa", "Se guarda la tasa usada para poder auditar la factura después."),
    ],
    widths=[45, 125],
)

h2("10.4 Ver, pagar y anular")
p("**Ver** muestra los datos del cliente, las líneas, los totales y el estado.")

p(
    "**Pagar** marca la factura como pagada y guarda la fecha de pago. No se puede pagar una "
    "factura anulada ni volver a pagar una ya pagada."
)

p(
    "**Anular** (solo admin) cambia el estado a anulada y **repone el stock** de todas las "
    "líneas. No se puede anular dos veces. Una factura no se edita: si hay un error, se anula "
    "y se emite una nueva."
)

h2("10.5 Imprimir")
p(
    "El botón **Imprimir** abre una versión limpia de la factura y lanza el diálogo de "
    "impresión del navegador. Desde allí puedes imprimir o elegir **Guardar como PDF**."
)

callout(
    "Nota",
    "La exportación directa a PDF está en desarrollo; usa la opción **Guardar como PDF** del "
    "diálogo de impresión.",
    bg="E8F1FB",
    color="1F3864",
)

# ============================================================
# 11. Egresos
# ============================================================
h1("11. Egresos (Solo admin)")

p(
    "Un egreso representa **un solo concepto**: “el día X pagué Y en moneda Z por el concepto W, "
    "a tal proveedor, con tal método de pago”. Si necesitas registrar tres conceptos distintos, "
    "registra tres egresos."
)

h2("11.1 Crear un egreso")
steps(
    [
        "Entra a **Egresos** y pulsa **Nuevo Egreso**.",
        "Indica la **Fecha**.",
        "Selecciona la **Categoría**.",
        "Escribe el **Proveedor** (texto libre, opcional).",
        "Escribe el **Concepto** (por ejemplo, “Compra de 20 kg de café”).",
        "Selecciona la **Moneda de pago**: USD, BS o COP.",
        "Escribe el **Monto** total. Si lleva IVA, inclúyelo en el monto.",
        "Elige el **Método de pago** y, si aplica, la **Referencia**.",
        "Selecciona el **Estado inicial**: Pagado o Pendiente.",
        "Si la categoría afecta inventario, aparecerán **Producto afectado** y "
        "**Cantidad a sumar (Kg)**. Complétalos.",
        "Pulsa **Registrar egreso**.",
    ]
)

callout(
    "Categorías que afectan inventario",
    "Si la categoría está marcada como “afecta inventario”, el producto y la cantidad son "
    "obligatorios: al guardar, la cantidad se suma al stock. Si la categoría no afecta "
    "inventario, esos campos no se muestran ni se usan.",
)

h2("11.2 Reglas del egreso")
table(
    ["Elemento", "Comportamiento"],
    [
        ("Número", "Se genera automáticamente con formato EGR-00000001."),
        ("Moneda", "Solo USD, BS o COP. BS y COP requieren tasa vigente."),
        ("Monto", "Único, mayor que cero. No se separan subtotal e impuesto."),
        ("Tasa", "Se guarda la tasa usada para calcular el monto en USD."),
        ("Inventario", "Solo si la categoría afecta inventario; suma stock al producto."),
        ("Estado", "Pagado (guarda fecha de pago) o Pendiente."),
    ],
    widths=[45, 125],
)

h2("11.3 Listado y filtros")
p(
    "Puedes filtrar por texto (número, concepto, referencia o proveedor), categoría, estado, "
    "moneda y rango de fechas. Al pie se muestran la cantidad de egresos y el total en USD, "
    "además de un desglose por categoría."
)

h2("11.4 Pagar y anular")
p(
    "**Pagar** marca el egreso como pagado y guarda la fecha. **Anular** revierte el stock "
    "ingresado y deja el egreso en estado anulado."
)

callout(
    "Anulación bloqueada",
    "Si la mercancía ya se vendió, el stock actual será menor que la cantidad a revertir y el "
    "sistema impedirá la anulación con un mensaje. En ese caso, registra un egreso de ajuste "
    "en lugar de anular.",
    bg="FCE4D6",
    color="833C0B",
)

# ============================================================
# 12. Categorías de egreso
# ============================================================
h1("12. Categorías de egreso (Solo admin)")

p(
    "Clasifican los egresos y definen si afectan inventario. El listado muestra nombre, "
    "descripción, si afecta inventario, cuántos egresos tiene y su estado."
)

h2("12.1 Crear o editar")
steps(
    [
        "Entra a **Egresos → Categorías de Egreso** y pulsa **Nueva Categoría**.",
        "Escribe el **Nombre** (único) y una **Descripción** opcional.",
        "Marca **Afecta inventario** si los egresos de esta categoría ingresan mercancía.",
        "Deja **Activa** marcada y pulsa **Guardar**.",
    ]
)

p("No se puede eliminar una categoría que tenga egresos asociados.")

# ============================================================
# 13. Reportes
# ============================================================
h1("13. Reportes (Solo admin)")

h2("13.1 Inventario")
p(
    "Lista los productos activos con filtros por categoría y por situación de stock (bajo o "
    "sin stock). Muestra totales de productos, kilogramos, valor total en USD y categorías. "
    "Se puede imprimir."
)

h2("13.2 Stock bajo")
p(
    "Muestra los productos con 5 kg o menos (y más de cero), ordenados de menor a mayor, con "
    "estadísticas de productos bajo, sin stock, totales y categorías afectadas."
)

h2("13.3 Ventas diarias")
p(
    "Para una fecha seleccionada, muestra las facturas pagadas del día, el total de ventas, "
    "la cantidad de facturas, el promedio y el desglose por moneda."
)

callout(
    "Para el cierre",
    "Combina **Ventas diarias** con **Egresos** y las tarjetas de utilidad y margen del "
    "Dashboard para revisar el resultado del día o del mes.",
    bg="E2EFDA",
    color="375623",
)

# ============================================================
# 14. FAQ
# ============================================================
h1("14. Preguntas frecuentes")

faq = [
    (
        "No veo el botón para crear o editar.",
        "Tu rol es vendedor. Esas acciones son exclusivas del administrador.",
    ),
    (
        "No puedo facturar en bolívares o pesos.",
        "Falta una tasa de cambio vigente para esa moneda. Un administrador debe registrarla.",
    ),
    (
        "El sistema dice “Stock insuficiente”.",
        "La cantidad solicitada supera el stock disponible. Ajusta la cantidad o repón el producto.",
    ),
    (
        "El total de la factura no coincide con lo que calculé.",
        "El precio lo impone el sistema con el precio base USD y la tasa vigente; el IVA se "
        "aplica según el producto. Revisa la tasa y el IVA del producto.",
    ),
    (
        "La factura salió en una moneda distinta a la que quería.",
        "La moneda del desplegable y la moneda de pago deben coincidir. Verifica ambas antes de guardar.",
    ),
    (
        "No puedo anular un egreso.",
        "El stock de ese producto ya fue vendido; el sistema evita dejar existencias negativas.",
    ),
    (
        "No puedo eliminar un cliente, producto o categoría.",
        "Tiene documentos asociados. Desactívalo en lugar de eliminarlo.",
    ),
    (
        "Mi usuario no puede entrar.",
        "Está inactivo o la contraseña es incorrecta. Pide al administrador que revise tu cuenta.",
    ),
    (
        "Aparece un error inesperado.",
        "Anota la hora y la acción que estabas realizando y avisa a soporte. No repitas la "
        "operación varias veces sin verificar si se guardó.",
    ),
]

for question, answer in faq:
    paragraph = doc.add_paragraph()
    paragraph.paragraph_format.space_before = Pt(6)
    paragraph.paragraph_format.space_after = Pt(1)
    run = paragraph.add_run(question)
    run.bold = True
    run.font.color.rgb = PRIMARY
    p(answer, space_after=2)

# ============================================================
# 15. Buenas prácticas
# ============================================================
h1("15. Buenas prácticas")

bullets(
    [
        "**Actualiza las tasas al inicio del día** antes de emitir la primera factura.",
        "**Verifica el stock** antes de comprometer una venta.",
        "**No anules para corregir**: anula y emite una factura nueva si el error es de fondo.",
        "**Registra los egresos el mismo día** para que la utilidad del mes sea confiable.",
        "**Usa conceptos claros** en los egresos (“Compra de 20 kg de café”, no “varios”).",
        "**Revisa el Dashboard** al cierre y compara ventas contra egresos.",
        "**Desactiva** clientes y productos obsoletos en lugar de eliminarlos.",
        "**Cierra sesión** al terminar en equipos compartidos.",
    ]
)

# ============================================================
# 16. Glosario
# ============================================================
h1("16. Glosario")

glossary = [
    ("Factura", "Documento de venta con una o varias líneas de productos."),
    ("Línea de factura", "Producto, cantidad, precio, IVA y total dentro de una factura."),
    ("Tasa de cambio", "Valor de una moneda frente al dólar para una fecha."),
    ("Moneda base", "Moneda de referencia del sistema (USD)."),
    ("IVA", "Impuesto al valor agregado, configurado por producto."),
    ("Stock", "Cantidad disponible en kilogramos."),
    ("Stock mínimo", "Umbral para generar alertas de reposición."),
    ("Egreso", "Salida de dinero registrada con un concepto, una moneda y un monto."),
    ("Afecta inventario", "Categoría de egreso que, además de registrar el gasto, suma stock."),
    ("Anular", "Dejar sin efecto un documento y revertir sus efectos (por ejemplo, el stock)."),
]

table(
    ["Término", "Significado"],
    [(term, meaning) for term, meaning in glossary],
    widths=[45, 125],
)

# ============================================================
# Anexo
# ============================================================
h1("Anexo A. Credenciales y soporte")

p("**Usuario inicial de administrador**")
table(
    ["Dato", "Valor"],
    [
        ("Correo", "admin@facturador.com"),
        ("Contraseña", "Admin123! (solo la primera vez; cámbiala al entrar)"),
        ("URL", "http://facturacion-inventario.test"),
    ],
    widths=[45, 125],
)

callout(
    "Cambia la contraseña",
    "La contraseña inicial es pública. Cámbiala antes de usar el sistema con datos reales, "
    "desde **Sistema → Cambiar contraseña**.",
    bg="FCE4D6",
    color="833C0B",
)

p(
    "Para soporte técnico, indica: usuario, módulo, acción realizada, hora del incidente y el "
    "mensaje de error que apareció en pantalla."
)

doc.save("docs/Manual-Usuario.docx")
print("OK: docs/Manual-Usuario.docx")

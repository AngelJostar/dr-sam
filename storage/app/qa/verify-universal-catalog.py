import sys, json, re, unicodedata, hashlib
from pathlib import Path
sys.path.insert(0, str(Path(__file__).parent / 'catalog-python'))
import pymupdf

root = Path(__file__).resolve().parents[3]
base = root / 'storage/app/qa'
items = json.loads((root / 'database/data/imss_bienestar_essential_medicines_2026.json').read_text(encoding='utf-8'))
imss = pymupdf.open(base / 'imss-medicamentos-fuente.pdf')
salud = pymupdf.open(base / 'salud-cdmx-medicamentos-fuente.pdf')
def normal(text):
    return re.sub(r'[^A-Z0-9]', '', unicodedata.normalize('NFKD', text.upper()))
texts = [page.get_text() for page in imss]
salud_texts = [page.get_text() for page in salud]
blocks = re.split(r'(\d{3}\.\d{3}\.\d{4}\.\d{2})', '\n'.join(texts))
source = {blocks[i]: blocks[i+1] for i in range(1, len(blocks)-1, 2)}
report = []
for row in items:
    block = source.get(row['cnis'], '')
    flags = re.findall(r'^(SÍ|SI|NO)\s*$', block, flags=re.M)
    flags = [normal(f) for f in flags[:3]]
    expected = [normal(row[k]) for k in ['mobile_units', 'basic_units', 'cessa']]
    report.append({
        'cnis': row['cnis'],
        'imss_page': next((i+1 for i,t in enumerate(texts) if row['cnis'] in t), None),
        'name_matches': normal(row['name']) in normal(block),
        'description_matches': normal(row['description']) in normal(block),
        'flags_match': flags == expected,
        'salud_cdmx_page': next((i+1 for i,t in enumerate(salud_texts) if row['cnis'] in t), None),
    })
manifest = {
    'reviewed_at': '2026-11-12',
    'imss_url': 'https://pgsp.imssbienestar.gob.mx/rutas_de_la_salud/docs/App_RdlS_v5.1_Meds_MatCur.pdf',
    'salud_url': 'https://data.salud.cdmx.gob.mx/ssdf/portalut/archivo/Actualizaciones/4toTrimestre21/Dir_Med_Tec_Ins/CIM.pdf',
    'salud_edition': 'Catálogo Institucional de Medicamentos, edición 2020; publicado el 3 de diciembre de 2021',
    'hashes': {p.name: hashlib.sha256(p.read_bytes()).hexdigest() for p in [base/'imss-medicamentos-fuente.pdf',base/'salud-cdmx-medicamentos-fuente.pdf']},
    'records': report,
}
(root / 'database/data/universal_medication_sources.json').write_text(json.dumps(manifest, ensure_ascii=False, indent=2)+'\n', encoding='utf-8')
failures = [r for r in report if not all(r[k] for k in ['imss_page', 'name_matches', 'description_matches', 'flags_match'])]
print(json.dumps({'rows':len(items), 'unique_cnis':len({r['cnis'] for r in items}), 'verified_imss':len(items)-len(failures), 'salud_key_matches':sum(bool(r['salud_cdmx_page']) for r in report),'mismatches':failures}, ensure_ascii=True))

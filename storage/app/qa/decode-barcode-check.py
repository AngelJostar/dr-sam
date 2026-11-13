import sys, json
from pathlib import Path
sys.path.insert(0, str(Path(__file__).parent / 'catalog-python'))
import pymupdf, zxingcpp
from PIL import Image
base = Path(__file__).parent
numbers = json.loads((base / 'barcode-numbers.json').read_text())
for number in numbers:
    doc = pymupdf.open(base / ('barcode-'+number+'.svg'))
    pixmap = doc[0].get_pixmap(matrix=pymupdf.Matrix(2,2))
    result = zxingcpp.read_barcode(Image.frombytes('RGB', [pixmap.width, pixmap.height], pixmap.samples))
    assert result and result.text == number, (number, result.text if result else 'No decode')
print(f'{len(numbers)} barcodes decoded exactly with ZXing; even/odd lengths, leading zeroes, and all 100 digit pairs.')

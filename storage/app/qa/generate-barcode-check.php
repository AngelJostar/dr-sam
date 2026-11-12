<?php
require __DIR__.'/../../../app/Services/NumericBarcode.php';
$encoder = new App\Services\NumericBarcode;
$numbers = ['9900000000000001', '0001234567890', '00000000', '12345678901234567890123456789012'];
$pairs = '';
for ($i = 0; $i < 100; $i++) {
    $pairs .= str_pad((string) $i, 2, '0', STR_PAD_LEFT);
}
$numbers = array_merge($numbers, str_split($pairs, 32));
foreach ($numbers as $number) {
    file_put_contents(__DIR__.'/barcode-'.$number.'.svg', $encoder->svg($number));
}
file_put_contents(__DIR__.'/barcode-numbers.json', json_encode($numbers));

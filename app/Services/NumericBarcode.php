<?php

namespace App\Services;

use InvalidArgumentException;

/** Numeric Code 128, with a Code B tail for an odd final digit. */
class NumericBarcode
{
    // Standard Code 128 symbol widths (0–106). Reference: ZXing Code128Reader,
    // https://github.com/zxing/zxing (Apache-2.0); these are the symbology's numeric constants.
    private const WIDTHS = [
        '212222','222122','222221','121223','121322','131222','122213','122312','132212','221213',
        '221312','231212','112232','122132','122231','113222','123122','123221','223211','221132',
        '221231','213212','223112','312131','311222','321122','321221','312212','322112','322211',
        '212123','212321','232121','111323','131123','131321','112313','132113','132311','211313',
        '231113','231311','112133','112331','132131','113123','113321','133121','313121','211331',
        '231131','213113','213311','213131','311123','311321','331121','312113','312311','332111',
        '314111','221411','431111','111224','111422','121124','121421','141122','141221','112214',
        '112412','122114','122411','142112','142211','241211','221114','413111','241112','134111',
        '111242','121142','121241','114212','124112','124211','411212','421112','421211','212141',
        '214121','412121','111143','111341','131141','114113','114311','411113','411311','113141',
        '114131','311141','411131','211412','211214','211232','2331112',
    ];

    public function svg(string $number): string
    {
        if (! preg_match('/\A[0-9]{8,32}\z/', $number)) {
            throw new InvalidArgumentException('El código debe tener entre 8 y 32 dígitos.');
        }

        $symbols = [105]; // Start C; no GS1/FNC1: internal codes are not commercial GTINs.
        $pairedLength = strlen($number) - strlen($number) % 2;
        for ($offset = 0; $offset < $pairedLength; $offset += 2) {
            $symbols[] = (int) substr($number, $offset, 2);
        }
        if (strlen($number) % 2) {
            $symbols[] = 100; // Switch to B, preserving the number without padding.
            $symbols[] = ord(substr($number, -1)) - 32;
        }
        $checksum = 105;
        foreach (array_slice($symbols, 1) as $position => $symbol) {
            $checksum += ($position + 1) * $symbol;
        }
        $symbols[] = $checksum % 103;
        $symbols[] = 106;
        $x = 10; // Quiet zones at both ends: ten narrow modules.
        $bars = '';
        foreach ($symbols as $symbol) {
            foreach (str_split(self::WIDTHS[$symbol]) as $index => $width) {
                if ($index % 2 === 0) {
                    $bars .= '<rect x="'.$x.'" y="4" width="'.$width.'" height="40"/>';
                }
                $x += (int) $width;
            }
        }
        $width = $x + 10;

        return '<svg xmlns="http://www.w3.org/2000/svg" width="'.($width * 2).'" height="120" viewBox="0 0 '.$width.' 60" role="img"><title>Código de barras '.$number.'</title><rect width="100%" height="100%" fill="white"/><g fill="black" shape-rendering="crispEdges">'.$bars.'</g><text x="'.($width / 2).'" y="55" text-anchor="middle" font-family="monospace" font-size="8" fill="black">'.$number.'</text></svg>';
    }
}

<?php

namespace App\Services\Shipping;

class BarcodeGeneratorService
{
    /**
     * Mapeo de caracteres para Code 128 (Subconjunto B)
     */
    private const CODE128_B = [
        ' ' => '212222', '!' => '222122', '"' => '222221', '#' => '121223',
        '$' => '121322', '%' => '131222', '&' => '122213', '\'' => '122312',
        '(' => '132212', ')' => '221213', '*' => '221312', '+' => '231212',
        ',' => '112232', '-' => '122132', '.' => '122231', '/' => '113222',
        '0' => '123122', '1' => '123221', '2' => '223211', '3' => '221132',
        '4' => '221231', '5' => '213212', '6' => '223112', '7' => '312131',
        '8' => '311222', '9' => '321122', ':' => '321221', ';' => '312212',
        '<' => '322112', '=' => '322211', '>' => '212123', '?' => '212321',
        '@' => '232121', 'A' => '111323', 'B' => '131123', 'C' => '131321',
        'D' => '112313', 'E' => '132113', 'F' => '132311', 'G' => '211313',
        'H' => '231113', 'I' => '231311', 'J' => '112133', 'K' => '112331',
        'L' => '132131', 'M' => '113123', 'N' => '113321', 'O' => '133121',
        'P' => '313121', 'Q' => '211331', 'R' => '231131', 'S' => '213113',
        'T' => '213311', 'U' => '213131', 'V' => '311123', 'W' => '311321',
        'X' => '331121', 'Y' => '312113', 'Z' => '312311', '[' => '332111',
        '\\' => '314111', ']' => '221411', '^' => '431111', '_' => '111224',
        '`' => '111422', 'a' => '121124', 'b' => '121421', 'c' => '141122',
        'd' => '141221', 'e' => '112214', 'f' => '112412', 'g' => '122114',
        'h' => '122411', 'i' => '142112', 'j' => '142211', 'k' => '241211',
        'l' => '221114', 'm' => '413111', 'n' => '241112', 'o' => '134111',
        'p' => '111242', 'q' => '121142', 'r' => '121241', 's' => '114212',
        't' => '124112', 'u' => '124211', 'v' => '411212', 'w' => '421112',
        'x' => '421211', 'y' => '212141', 'z' => '214121', '{' => '412121',
        '|' => '111143', '}' => '111341', '~' => '131141',
    ];

    /**
     * Genera un código de barras vectorial SVG limpio para impresión térmica 203 DPI.
     */
    public function generateSvg(string $text, int $barWidth = 2, int $height = 50): string
    {
        $cleanText = strtoupper(trim($text));
        $pattern = '211214'; // START CODE B

        for ($i = 0; $i < strlen($cleanText); $i++) {
            $char = $cleanText[$i];
            $pattern .= self::CODE128_B[$char] ?? '122132'; // fallback guion
        }

        $pattern .= '2331112'; // STOP CODE

        $totalModules = 0;
        for ($i = 0; $i < strlen($pattern); $i++) {
            $totalModules += (int) $pattern[$i];
        }

        $width = $totalModules * $barWidth;
        $svg = "<svg xmlns=\"http://www.w3.org/2000/svg\" width=\"{$width}\" height=\"{$height}\" viewBox=\"0 0 {$width} {$height}\">";
        $svg .= "<rect width=\"100%\" height=\"100%\" fill=\"#ffffff\"/>";

        $currentX = 0;
        $isBar = true;

        for ($i = 0; $i < strlen($pattern); $i++) {
            $modCount = (int) $pattern[$i];
            $barW = $modCount * $barWidth;

            if ($isBar) {
                $svg .= "<rect x=\"{$currentX}\" y=\"0\" width=\"{$barW}\" height=\"{$height}\" fill=\"#000000\"/>";
            }

            $currentX += $barW;
            $isBar = !$isBar;
        }

        $svg .= '</svg>';

        return $svg;
    }
}

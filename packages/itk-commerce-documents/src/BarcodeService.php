<?php
/**
 * Local Code 39 barcode renderer for document numbers.
 *
 * @package ITK_Commerce_Documents
 */

namespace ITK\Commerce\Documents;

defined( 'ABSPATH' ) || exit;

final class BarcodeService {
    /**
     * Render a standards-compatible Code 39 SVG for bounded document numbers.
     * No remote service, image library or font is required.
     *
     * @param string $value Document number.
     * @return string
     */
    public function render( $value ) {
        $value = strtoupper( trim( (string) $value ) );
        if ( '' === $value || ! preg_match( '/^[0-9A-Z. \-\$\/+%]+$/', $value ) ) {
            return '';
        }

        $patterns = $this->patterns();
        $encoded = '*' . $value . '*';
        $narrow = 2;
        $wide = 6;
        $height = 46;
        $quiet = 12;
        $x = $quiet;
        $bars = '';

        foreach ( str_split( $encoded ) as $character ) {
            if ( ! isset( $patterns[ $character ] ) ) {
                return '';
            }
            $pattern = $patterns[ $character ];
            for ( $i = 0; $i < 9; $i++ ) {
                $width = 'w' === $pattern[ $i ] ? $wide : $narrow;
                if ( 0 === $i % 2 ) {
                    $bars .= '<rect x="' . esc_attr( (string) $x ) . '" y="2" width="' . esc_attr( (string) $width ) . '" height="' . esc_attr( (string) $height ) . '" fill="currentColor"/>';
                }
                $x += $width;
            }
            $x += $narrow;
        }

        $total_width = $x + $quiet;
        return '<svg class="itk-document-barcode" role="img" aria-label="' . esc_attr( sprintf( __( 'Document barcode %s', 'itk-commerce-documents' ), $value ) ) . '" viewBox="0 0 ' . esc_attr( (string) $total_width ) . ' 66" xmlns="http://www.w3.org/2000/svg" preserveAspectRatio="xMinYMin meet">' . $bars . '<text x="' . esc_attr( (string) ( $total_width / 2 ) ) . '" y="62" text-anchor="middle" font-family="monospace" font-size="10" fill="currentColor">' . esc_html( $value ) . '</text></svg>';
    }

    /** @return array<string,string> */
    private function patterns() {
        return array(
            '0' => 'nnnwwnwnn', '1' => 'wnnwnnnnw', '2' => 'nnwwnnnnw', '3' => 'wnwwnnnnn',
            '4' => 'nnnwwnnnw', '5' => 'wnnwwnnnn', '6' => 'nnwwwnnnn', '7' => 'nnnwnnwnw',
            '8' => 'wnnwnnwnn', '9' => 'nnwwnnwnn', 'A' => 'wnnnnwnnw', 'B' => 'nnwnnwnnw',
            'C' => 'wnwnnwnnn', 'D' => 'nnnnwwnnw', 'E' => 'wnnnwwnnn', 'F' => 'nnwnwwnnn',
            'G' => 'nnnnnwwnw', 'H' => 'wnnnnwwnn', 'I' => 'nnwnnwwnn', 'J' => 'nnnnwwwnn',
            'K' => 'wnnnnnnww', 'L' => 'nnwnnnnww', 'M' => 'wnwnnnnwn', 'N' => 'nnnnwnnww',
            'O' => 'wnnnwnnwn', 'P' => 'nnwnwnnwn', 'Q' => 'nnnnnnwww', 'R' => 'wnnnnnwwn',
            'S' => 'nnwnnnwwn', 'T' => 'nnnnwnwwn', 'U' => 'wwnnnnnnw', 'V' => 'nwwnnnnnw',
            'W' => 'wwwnnnnnn', 'X' => 'nwnnwnnnw', 'Y' => 'wwnnwnnnn', 'Z' => 'nwwnwnnnn',
            '-' => 'nwnnnnwnw', '.' => 'wwnnnnwnn', ' ' => 'nwwnnnwnn', '$' => 'nwnwnwnnn',
            '/' => 'nwnwnnnwn', '+' => 'nwnnnwnwn', '%' => 'nnnwnwnwn', '*' => 'nwnnwnwnn',
        );
    }
}

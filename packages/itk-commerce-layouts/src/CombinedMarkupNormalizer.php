<?php
/**
 * Accept all-in-one HTML bundles and split embedded <style>/<script> blocks.
 *
 * @package ITK_Commerce_Layouts
 */

namespace ITK\Commerce\Layouts;

defined( 'ABSPATH' ) || exit;

final class CombinedMarkupNormalizer {
    /**
     * Split complete Header/Footer bundles before the admin page sanitizes the
     * submitted HTML field. This is intentionally request-local only; the
     * existing save handler still performs capability, nonce and sanitization
     * checks before anything is persisted.
     *
     * @return void
     */
    public static function preprocess_submission() {
        if ( empty( $_POST['layout'] ) || ! is_array( $_POST['layout'] ) ) {
            return;
        }

        $layout = wp_unslash( $_POST['layout'] );

        foreach ( array( 'header', 'footer' ) as $area ) {
            if ( empty( $layout[ $area ]['html'] ) || ! is_array( $layout[ $area ]['html'] ) ) {
                continue;
            }

            $html       = $layout[ $area ]['html'];
            $shared_raw = isset( $html['shared'] ) ? (string) $html['shared'] : '';

            if ( false === stripos( $shared_raw, '<style' ) && false === stripos( $shared_raw, '<script' ) ) {
                continue;
            }

            $parsed = self::split_bundle( $shared_raw );

            $layout[ $area ]['html']['shared'] = $parsed['html'];
            $layout[ $area ]['html']['css'] = self::join_code(
                $parsed['css'],
                isset( $html['css'] ) ? (string) $html['css'] : ''
            );
            $layout[ $area ]['html']['js'] = self::join_code(
                $parsed['js'],
                isset( $html['js'] ) ? (string) $html['js'] : ''
            );
        }

        $_POST['layout'] = wp_slash( $layout );
    }

    /**
     * Re-normalize custom Header/Footer HTML from the original profile so users
     * may paste complete HTML + CSS + JavaScript bundles into Primary HTML.
     * Explicit CSS/JS fields are preserved and appended after extracted blocks.
     *
     * This remains as a second safety net for programmatic profile saves that
     * bypass the normal Header/Footer admin form.
     *
     * @param array<string,mixed> $normalized Normalized profile.
     * @param array<string,mixed> $original   Original profile.
     * @return array<string,mixed>
     */
    public static function normalize_profile( $normalized, $original ) {
        if ( ! is_array( $normalized ) || ! is_array( $original ) ) {
            return $normalized;
        }

        foreach ( array( 'header', 'footer' ) as $area ) {
            $html = $original['layouts'][ $area ]['content']['html'] ?? null;
            if ( ! is_array( $html ) ) {
                continue;
            }

            $shared_raw = isset( $html['shared'] ) ? (string) $html['shared'] : '';
            if ( false === stripos( $shared_raw, '<style' ) && false === stripos( $shared_raw, '<script' ) ) {
                continue;
            }

            $parsed = self::split_bundle( $shared_raw );

            if ( empty( $normalized['layouts'][ $area ]['content']['html'] ) || ! is_array( $normalized['layouts'][ $area ]['content']['html'] ) ) {
                $normalized['layouts'][ $area ]['content']['html'] = array();
            }

            $explicit_css = isset( $html['css'] ) ? (string) $html['css'] : '';
            $explicit_js  = isset( $html['js'] ) ? (string) $html['js'] : '';

            $normalized['layouts'][ $area ]['content']['html']['shared'] = CustomLayoutRenderer::sanitize_html( $parsed['html'] );
            $normalized['layouts'][ $area ]['content']['html']['css'] = CustomLayoutRenderer::sanitize_css(
                self::join_code( $parsed['css'], $explicit_css )
            );
            $normalized['layouts'][ $area ]['content']['html']['js'] = CustomLayoutRenderer::sanitize_js(
                self::join_code( $parsed['js'], $explicit_js )
            );
        }

        return $normalized;
    }

    /**
     * @param string $bundle Complete HTML bundle.
     * @return array{html:string,css:string,js:string}
     */
    public static function split_bundle( $bundle ) {
        $bundle = (string) $bundle;
        $css    = array();
        $js     = array();

        $html = preg_replace_callback(
            '#<style\b[^>]*>(.*?)</style\s*>#is',
            static function ( $match ) use ( &$css ) {
                $css[] = isset( $match[1] ) ? (string) $match[1] : '';
                return '';
            },
            $bundle
        );

        if ( ! is_string( $html ) ) {
            $html = $bundle;
        }

        $html = preg_replace_callback(
            '#<script\b[^>]*>(.*?)</script\s*>#is',
            static function ( $match ) use ( &$js ) {
                $js[] = isset( $match[1] ) ? (string) $match[1] : '';
                return '';
            },
            $html
        );

        if ( ! is_string( $html ) ) {
            $html = $bundle;
        }

        return array(
            'html' => trim( $html ),
            'css'  => trim( implode( "\n\n", array_filter( $css, 'strlen' ) ) ),
            'js'   => trim( implode( "\n\n", array_filter( $js, 'strlen' ) ) ),
        );
    }

    /** @param string $first First code block. @param string $second Second code block. @return string */
    private static function join_code( $first, $second ) {
        $first  = trim( (string) $first );
        $second = trim( (string) $second );
        if ( '' === $first ) {
            return $second;
        }
        if ( '' === $second ) {
            return $first;
        }
        return $first . "\n\n" . $second;
    }
}

add_action(
    'admin_post_itk_commerce_save_custom_header_footer',
    array( CombinedMarkupNormalizer::class, 'preprocess_submission' ),
    1
);

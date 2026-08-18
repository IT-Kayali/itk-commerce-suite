<?php
/**
 * Render profile-driven Header/Footer sources without patching Theme templates.
 *
 * @package ITK_Commerce_Layouts
 */

namespace ITK\Commerce\Layouts;

use ITK\Commerce\Core\Core;

defined( 'ABSPATH' ) || exit;

final class CustomLayoutRenderer {
    /** @var array<string,mixed>|null|false */
    private $profile = false;

    /**
     * Handle a Theme layout area when the active profile explicitly selects a
     * source other than the Theme model. Returning null keeps legacy behavior.
     *
     * @param bool|null $handled Existing handler result.
     * @param string    $area    Layout area.
     * @return bool|null
     */
    public function render_area( $handled, $area ) {
        if ( null !== $handled ) {
            return (bool) $handled;
        }

        $area = sanitize_key( $area );
        if ( ! in_array( $area, array( 'header', 'footer' ), true ) ) {
            return $handled;
        }

        $profile = $this->active_profile();
        if ( ! is_array( $profile ) || empty( $profile['layouts'][ $area ] ) || ! is_array( $profile['layouts'][ $area ] ) ) {
            return $handled;
        }

        $assignment = $profile['layouts'][ $area ];
        $source     = isset( $assignment['source'] ) ? sanitize_key( $assignment['source'] ) : '';

        // Older profiles intentionally keep the pre-existing Elementor/Theme
        // fallback chain until the source is explicitly saved by the new UI.
        if ( '' === $source ) {
            return $handled;
        }

        if ( 'theme' === $source ) {
            return false;
        }

        if ( 'disabled' === $source ) {
            return true;
        }

        $content = isset( $assignment['content'] ) && is_array( $assignment['content'] )
            ? $assignment['content']
            : array();

        if ( 'custom_html' === $source ) {
            return $this->render_html_source( $area, isset( $content['html'] ) && is_array( $content['html'] ) ? $content['html'] : array() )
                ? true
                : false;
        }

        if ( 'shortcode' === $source ) {
            return $this->render_shortcode_source( $area, isset( $content['shortcode'] ) && is_array( $content['shortcode'] ) ? $content['shortcode'] : array() )
                ? true
                : false;
        }

        if ( 'elementor' === $source ) {
            return $this->render_elementor_source( $area, isset( $content['elementor'] ) && is_array( $content['elementor'] ) ? $content['elementor'] : array() )
                ? true
                : false;
        }

        return $handled;
    }

    /**
     * Restore and sanitize raw custom layout content after Core's generic
     * profile sanitizer. This keeps portable HTML/CSS/JS intact while applying
     * an explicit module-owned security boundary.
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
            if ( empty( $original['layouts'][ $area ] ) || ! is_array( $original['layouts'][ $area ] ) ) {
                continue;
            }

            if ( empty( $normalized['layouts'][ $area ] ) || ! is_array( $normalized['layouts'][ $area ] ) ) {
                $normalized['layouts'][ $area ] = array();
            }

            $source = isset( $original['layouts'][ $area ]['source'] )
                ? sanitize_key( $original['layouts'][ $area ]['source'] )
                : '';
            if ( in_array( $source, self::sources(), true ) ) {
                $normalized['layouts'][ $area ]['source'] = $source;
            }

            $content = isset( $original['layouts'][ $area ]['content'] ) && is_array( $original['layouts'][ $area ]['content'] )
                ? $original['layouts'][ $area ]['content']
                : array();

            $html = isset( $content['html'] ) && is_array( $content['html'] ) ? $content['html'] : array();
            $normalized['layouts'][ $area ]['content']['html'] = array(
                'shared' => self::sanitize_html( isset( $html['shared'] ) ? $html['shared'] : '' ),
                'tablet' => self::sanitize_html( isset( $html['tablet'] ) ? $html['tablet'] : '' ),
                'mobile' => self::sanitize_html( isset( $html['mobile'] ) ? $html['mobile'] : '' ),
                'css'    => self::sanitize_css( isset( $html['css'] ) ? $html['css'] : '' ),
                'js'     => self::sanitize_js( isset( $html['js'] ) ? $html['js'] : '' ),
            );

            $shortcode = isset( $content['shortcode'] ) && is_array( $content['shortcode'] ) ? $content['shortcode'] : array();
            $normalized['layouts'][ $area ]['content']['shortcode'] = array(
                'shared' => self::sanitize_shortcode( isset( $shortcode['shared'] ) ? $shortcode['shared'] : '' ),
                'tablet' => self::sanitize_shortcode( isset( $shortcode['tablet'] ) ? $shortcode['tablet'] : '' ),
                'mobile' => self::sanitize_shortcode( isset( $shortcode['mobile'] ) ? $shortcode['mobile'] : '' ),
            );

            $elementor = isset( $content['elementor'] ) && is_array( $content['elementor'] ) ? $content['elementor'] : array();
            $normalized['layouts'][ $area ]['content']['elementor'] = array(
                'shared' => isset( $elementor['shared'] ) ? absint( $elementor['shared'] ) : 0,
                'tablet' => isset( $elementor['tablet'] ) ? absint( $elementor['tablet'] ) : 0,
                'mobile' => isset( $elementor['mobile'] ) ? absint( $elementor['mobile'] ) : 0,
            );
        }

        return $normalized;
    }

    /** @return string[] */
    public static function sources() {
        return array( 'theme', 'custom_html', 'elementor', 'shortcode', 'disabled' );
    }

    /** @param mixed $html Raw HTML. @return string */
    public static function sanitize_html( $html ) {
        return wp_kses( (string) $html, self::allowed_html() );
    }

    /** @param mixed $css Raw CSS. @return string */
    public static function sanitize_css( $css ) {
        $css = (string) $css;
        $css = preg_replace( '#</?style\b[^>]*>#i', '', $css );
        return is_string( $css ) ? $css : '';
    }

    /** @param mixed $js Raw JavaScript. @return string */
    public static function sanitize_js( $js ) {
        $js = (string) $js;
        $js = preg_replace( '#</?script\b[^>]*>#i', '', $js );
        return is_string( $js ) ? $js : '';
    }

    /** @param mixed $shortcode Raw shortcode expression. @return string */
    public static function sanitize_shortcode( $shortcode ) {
        return sanitize_textarea_field( (string) $shortcode );
    }

    /** @param string $area Header/footer. @param array<string,mixed> $values Values. @return bool */
    private function render_html_source( $area, array $values ) {
        $shared = isset( $values['shared'] ) ? self::sanitize_html( $values['shared'] ) : '';
        if ( '' === trim( $shared ) ) {
            return false;
        }

        $tablet = isset( $values['tablet'] ) ? self::sanitize_html( $values['tablet'] ) : '';
        $mobile = isset( $values['mobile'] ) ? self::sanitize_html( $values['mobile'] ) : '';
        $css    = isset( $values['css'] ) ? self::sanitize_css( $values['css'] ) : '';
        $js     = isset( $values['js'] ) ? self::sanitize_js( $values['js'] ) : '';

        $this->render_variants(
            $area,
            $shared,
            $tablet,
            $mobile,
            static function ( $markup ) {
                echo $markup; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- sanitized through explicit custom layout allowlist.
            }
        );

        if ( '' !== trim( $css ) ) {
            echo '<style data-itk-custom-layout-css="' . esc_attr( $area ) . '">' . $css . '</style>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- admin-owned CSS, style tags stripped on save/render.
        }
        if ( '' !== trim( $js ) ) {
            echo '<script data-itk-custom-layout-js="' . esc_attr( $area ) . '">' . $js . '</script>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- admin-owned JS, script tags stripped on save/render.
        }

        return true;
    }

    /** @param string $area Header/footer. @param array<string,mixed> $values Values. @return bool */
    private function render_shortcode_source( $area, array $values ) {
        $shared = isset( $values['shared'] ) ? self::sanitize_shortcode( $values['shared'] ) : '';
        if ( '' === trim( $shared ) ) {
            return false;
        }

        $tablet = isset( $values['tablet'] ) ? self::sanitize_shortcode( $values['tablet'] ) : '';
        $mobile = isset( $values['mobile'] ) ? self::sanitize_shortcode( $values['mobile'] ) : '';

        $this->render_variants(
            $area,
            $shared,
            $tablet,
            $mobile,
            static function ( $expression ) {
                echo do_shortcode( $expression ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- shortcode callback owns its output contract.
            }
        );

        return true;
    }

    /** @param string $area Header/footer. @param array<string,mixed> $values Values. @return bool */
    private function render_elementor_source( $area, array $values ) {
        $shared = isset( $values['shared'] ) ? absint( $values['shared'] ) : 0;
        if ( ! $shared || ! class_exists( '\\Elementor\\Plugin' ) || ! isset( \Elementor\Plugin::$instance->frontend ) ) {
            return false;
        }

        $tablet = isset( $values['tablet'] ) ? absint( $values['tablet'] ) : 0;
        $mobile = isset( $values['mobile'] ) ? absint( $values['mobile'] ) : 0;

        $this->render_variants(
            $area,
            (string) $shared,
            $tablet ? (string) $tablet : '',
            $mobile ? (string) $mobile : '',
            static function ( $template_id ) {
                $template_id = absint( $template_id );
                if ( ! $template_id ) {
                    return;
                }
                echo \Elementor\Plugin::$instance->frontend->get_builder_content_for_display( $template_id, true ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Elementor renders its own saved template.
            }
        );

        return true;
    }

    /**
     * Render one required shared value plus optional tablet/mobile overrides.
     * Blank overrides automatically inherit the shared value.
     *
     * @param string   $area     Header/footer.
     * @param string   $shared   Shared value.
     * @param string   $tablet   Tablet override.
     * @param string   $mobile   Mobile override.
     * @param callable $renderer Value renderer.
     * @return void
     */
    private function render_variants( $area, $shared, $tablet, $mobile, $renderer ) {
        $id          = 'itk-layout-override-' . sanitize_html_class( $area );
        $has_tablet  = '' !== trim( (string) $tablet );
        $has_mobile  = '' !== trim( (string) $mobile );
        $classes     = array( 'itk-layout-override', 'itk-layout-override--' . sanitize_html_class( $area ) );
        $classes[]   = $has_tablet ? 'has-tablet-override' : 'uses-shared-tablet';
        $classes[]   = $has_mobile ? 'has-mobile-override' : 'uses-shared-mobile';

        echo '<div id="' . esc_attr( $id ) . '" class="' . esc_attr( implode( ' ', $classes ) ) . '">';
        echo '<div class="itk-layout-variant itk-layout-variant--shared">';
        call_user_func( $renderer, $shared );
        echo '</div>';

        if ( $has_tablet ) {
            echo '<div class="itk-layout-variant itk-layout-variant--tablet">';
            call_user_func( $renderer, $tablet );
            echo '</div>';
        }
        if ( $has_mobile ) {
            echo '<div class="itk-layout-variant itk-layout-variant--mobile">';
            call_user_func( $renderer, $mobile );
            echo '</div>';
        }
        echo '</div>';

        echo '<style data-itk-layout-variants="' . esc_attr( $area ) . '">';
        echo '#' . esc_attr( $id ) . '>.itk-layout-variant--tablet,#' . esc_attr( $id ) . '>.itk-layout-variant--mobile{display:none;}';
        if ( $has_tablet ) {
            echo '@media (min-width:768px) and (max-width:1024px){#' . esc_attr( $id ) . '>.itk-layout-variant--shared{display:none;}#' . esc_attr( $id ) . '>.itk-layout-variant--tablet{display:block;}}';
        }
        if ( $has_mobile ) {
            echo '@media (max-width:767px){#' . esc_attr( $id ) . '>.itk-layout-variant--shared{display:none;}#' . esc_attr( $id ) . '>.itk-layout-variant--mobile{display:block;}}';
        }
        echo '</style>';
    }

    /** @return array<string,mixed>|null */
    private function active_profile() {
        if ( false !== $this->profile ) {
            return is_array( $this->profile ) ? $this->profile : null;
        }

        $core       = Core::instance();
        $profile_id = $core->settings()->active_profile_id();
        $this->profile = $profile_id ? $core->profiles()->get( $profile_id ) : null;

        return is_array( $this->profile ) ? $this->profile : null;
    }

    /** @return array<string,array<string,bool>> */
    private static function allowed_html() {
        $allowed = wp_kses_allowed_html( 'post' );
        $global  = array(
            'id'         => true,
            'class'      => true,
            'style'      => true,
            'title'      => true,
            'role'       => true,
            'tabindex'   => true,
            'hidden'     => true,
            'dir'        => true,
            'lang'       => true,
            'aria-*'     => true,
            'data-*'     => true,
        );

        foreach ( array_keys( $allowed ) as $tag ) {
            $allowed[ $tag ] = array_merge( is_array( $allowed[ $tag ] ) ? $allowed[ $tag ] : array(), $global );
        }

        $allowed['header'] = $global;
        $allowed['footer'] = $global;
        $allowed['nav']    = $global;
        $allowed['main']   = $global;
        $allowed['section'] = $global;
        $allowed['article'] = $global;
        $allowed['aside']   = $global;
        $allowed['form']    = array_merge( $global, array( 'action' => true, 'method' => true, 'name' => true, 'target' => true, 'autocomplete' => true ) );
        $allowed['input']   = array_merge( $global, array( 'type' => true, 'name' => true, 'value' => true, 'placeholder' => true, 'checked' => true, 'disabled' => true, 'readonly' => true, 'required' => true, 'autocomplete' => true, 'min' => true, 'max' => true, 'step' => true ) );
        $allowed['button']  = array_merge( $global, array( 'type' => true, 'name' => true, 'value' => true, 'disabled' => true ) );
        $allowed['select']  = array_merge( $global, array( 'name' => true, 'multiple' => true, 'required' => true, 'disabled' => true ) );
        $allowed['option']  = array_merge( $global, array( 'value' => true, 'selected' => true, 'disabled' => true ) );
        $allowed['label']   = array_merge( $global, array( 'for' => true ) );
        $allowed['img']     = array_merge( isset( $allowed['img'] ) && is_array( $allowed['img'] ) ? $allowed['img'] : array(), $global, array( 'src' => true, 'srcset' => true, 'sizes' => true, 'alt' => true, 'width' => true, 'height' => true, 'loading' => true, 'decoding' => true ) );
        $allowed['a']       = array_merge( isset( $allowed['a'] ) && is_array( $allowed['a'] ) ? $allowed['a'] : array(), $global, array( 'href' => true, 'target' => true, 'rel' => true, 'download' => true ) );
        $allowed['svg']     = array_merge( $global, array( 'xmlns' => true, 'viewbox' => true, 'width' => true, 'height' => true, 'fill' => true, 'stroke' => true, 'stroke-width' => true, 'aria-hidden' => true, 'focusable' => true ) );
        $allowed['path']    = array_merge( $global, array( 'd' => true, 'fill' => true, 'stroke' => true, 'stroke-width' => true, 'stroke-linecap' => true, 'stroke-linejoin' => true, 'fill-rule' => true, 'clip-rule' => true ) );
        $allowed['circle']  = array_merge( $global, array( 'cx' => true, 'cy' => true, 'r' => true, 'fill' => true, 'stroke' => true ) );
        $allowed['rect']    = array_merge( $global, array( 'x' => true, 'y' => true, 'rx' => true, 'ry' => true, 'width' => true, 'height' => true, 'fill' => true, 'stroke' => true ) );
        $allowed['line']    = array_merge( $global, array( 'x1' => true, 'x2' => true, 'y1' => true, 'y2' => true, 'stroke' => true ) );
        $allowed['polyline'] = array_merge( $global, array( 'points' => true, 'fill' => true, 'stroke' => true ) );
        $allowed['polygon'] = array_merge( $global, array( 'points' => true, 'fill' => true, 'stroke' => true ) );
        $allowed['g']       = array_merge( $global, array( 'fill' => true, 'stroke' => true, 'transform' => true ) );
        $allowed['use']     = array_merge( $global, array( 'href' => true, 'xlink:href' => true ) );

        return $allowed;
    }
}

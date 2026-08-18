<?php
/**
 * Profile-driven local font management.
 *
 * @package ITK_Commerce_Core
 */

namespace ITK\Commerce\Core\Design;

use ITK\Commerce\Core\Core;

defined( 'ABSPATH' ) || exit;

final class LocalFonts {
    const PAGE_SLUG = 'itk-commerce-local-fonts';

    /** @var Core */
    private $core;

    /** @param Core $core Core application. */
    public function __construct( Core $core ) {
        $this->core = $core;
    }

    /** @return void */
    public function register() {
        add_action( 'wp_head', array( $this, 'render_font_faces' ), 5 );
        add_filter( 'itk_commerce_local_fonts', array( $this, 'filter_fonts' ) );

        if ( is_admin() ) {
            add_action( 'itk_commerce_admin_menu', array( $this, 'register_menu' ) );
            add_action( 'admin_post_itk_commerce_save_local_fonts', array( $this, 'save' ) );
            add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_media' ) );
            add_action( 'admin_footer', array( $this, 'render_media_picker_script' ) );
            add_filter( 'upload_mimes', array( $this, 'allow_font_mimes' ) );
        }
    }

    /** @param string $parent_slug Parent menu slug. @return void */
    public function register_menu( $parent_slug ) {
        add_submenu_page(
            $parent_slug,
            __( 'Local Fonts', 'itk-commerce-core' ),
            __( 'Local Fonts', 'itk-commerce-core' ),
            'itk_manage_design',
            self::PAGE_SLUG,
            array( $this, 'render_admin' )
        );
    }

    /** @param string $hook Current admin hook. @return void */
    public function enqueue_media( $hook ) {
        unset( $hook );
        if ( ! $this->is_font_admin_page() ) {
            return;
        }
        wp_enqueue_media();
    }

    /**
     * Permit common self-hosted font formats for authorized design managers.
     * WordPress still performs its normal upload/filetype validation.
     *
     * @param array<string,string> $mimes Existing MIME map.
     * @return array<string,string>
     */
    public function allow_font_mimes( $mimes ) {
        if ( ! current_user_can( 'itk_manage_design' ) ) {
            return $mimes;
        }

        $mimes['woff']  = 'font/woff';
        $mimes['woff2'] = 'font/woff2';
        $mimes['ttf']   = 'font/ttf';
        $mimes['otf']   = 'font/otf';
        return $mimes;
    }

    /** @return void */
    public function render_admin() {
        if ( ! current_user_can( 'itk_manage_design' ) ) {
            wp_die( esc_html__( 'You are not allowed to manage local fonts.', 'itk-commerce-core' ) );
        }
        $fonts = $this->fonts();
        while ( count( $fonts ) < 6 ) {
            $fonts[] = array( 'family' => '', 'url' => '', 'weight' => '400', 'style' => 'normal' );
        }
        ?>
        <div class="wrap">
            <h1><?php esc_html_e( 'Local Fonts', 'itk-commerce-core' ); ?></h1>
            <p><?php esc_html_e( 'Upload or select font files hosted on this WordPress installation. External font URLs are rejected so customer storefronts do not depend on Google Fonts or other third-party font CDNs.', 'itk-commerce-core' ); ?></p>
            <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
                <input type="hidden" name="action" value="itk_commerce_save_local_fonts">
                <?php wp_nonce_field( 'itk_commerce_save_local_fonts' ); ?>
                <table class="widefat striped"><thead><tr><th><?php esc_html_e( 'Family', 'itk-commerce-core' ); ?></th><th><?php esc_html_e( 'Local file', 'itk-commerce-core' ); ?></th><th><?php esc_html_e( 'Weight', 'itk-commerce-core' ); ?></th><th><?php esc_html_e( 'Style', 'itk-commerce-core' ); ?></th></tr></thead><tbody>
                <?php foreach ( array_slice( $fonts, 0, 12 ) as $index => $font ) : ?>
                    <?php $url_id = 'itk-commerce-font-url-' . absint( $index ); ?>
                    <tr>
                        <td><input type="text" name="fonts[<?php echo esc_attr( (string) $index ); ?>][family]" value="<?php echo esc_attr( $font['family'] ); ?>" placeholder="Brand Sans"></td>
                        <td>
                            <input id="<?php echo esc_attr( $url_id ); ?>" class="regular-text" type="url" name="fonts[<?php echo esc_attr( (string) $index ); ?>][url]" value="<?php echo esc_attr( $font['url'] ); ?>" placeholder="<?php echo esc_attr( content_url( 'uploads/font.woff2' ) ); ?>">
                            <button type="button" class="button itk-commerce-font-select" data-target="<?php echo esc_attr( $url_id ); ?>"><?php esc_html_e( 'Select / upload', 'itk-commerce-core' ); ?></button>
                        </td>
                        <td><input type="text" name="fonts[<?php echo esc_attr( (string) $index ); ?>][weight]" value="<?php echo esc_attr( $font['weight'] ); ?>" placeholder="400"></td>
                        <td><select name="fonts[<?php echo esc_attr( (string) $index ); ?>][style]"><option value="normal" <?php selected( $font['style'], 'normal' ); ?>>normal</option><option value="italic" <?php selected( $font['style'], 'italic' ); ?>>italic</option></select></td>
                    </tr>
                <?php endforeach; ?>
                </tbody></table>
                <?php submit_button( __( 'Save local fonts', 'itk-commerce-core' ) ); ?>
            </form>
        </div>
        <?php
    }

    /** @return void */
    public function render_media_picker_script() {
        if ( ! $this->is_font_admin_page() || ! current_user_can( 'itk_manage_design' ) ) {
            return;
        }
        ?>
        <script>
        (function () {
            'use strict';
            document.addEventListener('click', function (event) {
                var button = event.target.closest('.itk-commerce-font-select');
                if (!button || !window.wp || !wp.media) {
                    return;
                }
                event.preventDefault();
                var target = document.getElementById(button.getAttribute('data-target'));
                if (!target) {
                    return;
                }
                var frame = wp.media({
                    title: <?php echo wp_json_encode( __( 'Select local font', 'itk-commerce-core' ) ); ?>,
                    button: { text: <?php echo wp_json_encode( __( 'Use this font', 'itk-commerce-core' ) ); ?> },
                    multiple: false
                });
                frame.on('select', function () {
                    var attachment = frame.state().get('selection').first();
                    if (!attachment) {
                        return;
                    }
                    attachment = attachment.toJSON();
                    if (attachment && attachment.url) {
                        target.value = attachment.url;
                        target.dispatchEvent(new Event('change', { bubbles: true }));
                    }
                });
                frame.open();
            });
        }());
        </script>
        <?php
    }

    /** @return void */
    public function save() {
        if ( ! current_user_can( 'itk_manage_design' ) ) {
            wp_die( esc_html__( 'You are not allowed to manage local fonts.', 'itk-commerce-core' ) );
        }
        check_admin_referer( 'itk_commerce_save_local_fonts' );

        $raw = isset( $_POST['fonts'] ) && is_array( $_POST['fonts'] ) ? wp_unslash( $_POST['fonts'] ) : array();
        $fonts = array();
        foreach ( array_slice( $raw, 0, 12 ) as $row ) {
            if ( ! is_array( $row ) ) {
                continue;
            }
            $family = isset( $row['family'] ) ? sanitize_text_field( $row['family'] ) : '';
            $url = isset( $row['url'] ) ? esc_url_raw( $row['url'] ) : '';
            if ( '' === $family || '' === $url || ! $this->is_local_font_url( $url ) ) {
                continue;
            }
            $weight = isset( $row['weight'] ) ? sanitize_text_field( $row['weight'] ) : '400';
            if ( ! preg_match( '/^(?:[1-9]00|normal|bold)$/', $weight ) ) {
                $weight = '400';
            }
            $style = isset( $row['style'] ) && 'italic' === sanitize_key( $row['style'] ) ? 'italic' : 'normal';
            $fonts[] = array( 'family' => $family, 'url' => $url, 'weight' => $weight, 'style' => $style );
        }

        $profile_id = $this->core->settings()->active_profile_id();
        $profile = $profile_id ? $this->core->profiles()->get( $profile_id ) : null;
        if ( ! is_array( $profile ) ) {
            wp_die( esc_html__( 'Select an active customer profile before saving local fonts.', 'itk-commerce-core' ) );
        }

        if ( ! isset( $profile['design'] ) || ! is_array( $profile['design'] ) ) {
            $profile['design'] = array();
        }
        $profile['design']['local_fonts'] = $fonts;
        $saved = $this->core->profiles()->save( $profile );
        if ( is_wp_error( $saved ) ) {
            wp_die( esc_html( $saved->get_error_message() ) );
        }

        wp_safe_redirect( add_query_arg( array( 'page' => self::PAGE_SLUG, 'updated' => '1' ), admin_url( 'admin.php' ) ) );
        exit;
    }

    /** @return void */
    public function render_font_faces() {
        $fonts = $this->fonts();
        if ( empty( $fonts ) ) {
            return;
        }
        echo "<style id=\"itk-commerce-local-fonts\">\n";
        foreach ( $fonts as $font ) {
            $format = $this->format_for_url( $font['url'] );
            echo '@font-face{font-family:' . wp_json_encode( $font['family'] ) . ';src:url(' . wp_json_encode( $font['url'] ) . ')' . ( $format ? ' format(' . wp_json_encode( $format ) . ')' : '' ) . ';font-weight:' . esc_html( $font['weight'] ) . ';font-style:' . esc_html( $font['style'] ) . ';font-display:swap;}'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- values are normalized below.
        }
        echo "\n</style>\n";
    }

    /** @param mixed $existing Existing value. @return array<int,array<string,string>> */
    public function filter_fonts( $existing ) {
        unset( $existing );
        return $this->fonts();
    }

    /** @return array<int,array<string,string>> */
    private function fonts() {
        $profile_id = $this->core->settings()->active_profile_id();
        $profile = $profile_id ? $this->core->profiles()->get( $profile_id ) : null;
        $rows = is_array( $profile ) && isset( $profile['design']['local_fonts'] ) && is_array( $profile['design']['local_fonts'] ) ? $profile['design']['local_fonts'] : array();
        $fonts = array();
        foreach ( array_slice( $rows, 0, 12 ) as $row ) {
            if ( ! is_array( $row ) ) {
                continue;
            }
            $family = isset( $row['family'] ) ? sanitize_text_field( $row['family'] ) : '';
            $url = isset( $row['url'] ) ? esc_url_raw( $row['url'] ) : '';
            if ( '' === $family || ! $this->is_local_font_url( $url ) ) {
                continue;
            }
            $weight = isset( $row['weight'] ) ? sanitize_text_field( $row['weight'] ) : '400';
            if ( ! preg_match( '/^(?:[1-9]00|normal|bold)$/', $weight ) ) {
                $weight = '400';
            }
            $fonts[] = array(
                'family' => $family,
                'url' => $url,
                'weight' => $weight,
                'style' => isset( $row['style'] ) && 'italic' === $row['style'] ? 'italic' : 'normal',
            );
        }
        return $fonts;
    }

    /** @return bool */
    private function is_font_admin_page() {
        $page = isset( $_GET['page'] ) ? sanitize_key( wp_unslash( $_GET['page'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        return self::PAGE_SLUG === $page;
    }

    /** @param string $url URL. @return bool */
    private function is_local_font_url( $url ) {
        if ( '' === $url ) {
            return false;
        }
        $home_host = strtolower( (string) wp_parse_url( home_url( '/' ), PHP_URL_HOST ) );
        $url_host = strtolower( (string) wp_parse_url( $url, PHP_URL_HOST ) );
        $path = strtolower( (string) wp_parse_url( $url, PHP_URL_PATH ) );
        if ( '' === $home_host || $home_host !== $url_host ) {
            return false;
        }
        return (bool) preg_match( '/\.(woff2?|ttf|otf)$/', $path );
    }

    /** @param string $url URL. @return string */
    private function format_for_url( $url ) {
        $path = strtolower( (string) wp_parse_url( $url, PHP_URL_PATH ) );
        if ( str_ends_with( $path, '.woff2' ) ) {
            return 'woff2';
        }
        if ( str_ends_with( $path, '.woff' ) ) {
            return 'woff';
        }
        if ( str_ends_with( $path, '.ttf' ) ) {
            return 'truetype';
        }
        if ( str_ends_with( $path, '.otf' ) ) {
            return 'opentype';
        }
        return '';
    }
}

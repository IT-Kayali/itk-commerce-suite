<?php
/**
 * Semantic language-switcher surface for the Commerce Suite.
 *
 * @package ITK_Commerce_Multilingual
 */

namespace ITK\Commerce\Multilingual;

defined( 'ABSPATH' ) || exit;

final class LanguageSwitcher {
    /** @var LanguageContext */
    private $context;

    /** @var LanguageRouter */
    private $router;

    /**
     * @param LanguageContext $context Current request language context.
     * @param LanguageRouter  $router Directory-language router.
     */
    public function __construct( LanguageContext $context, LanguageRouter $router ) {
        $this->context = $context;
        $this->router  = $router;
    }

    /** @return void */
    public function register() {
        add_shortcode( 'itk_language_switcher', array( $this, 'shortcode' ) );
        add_filter( 'itk_commerce_language_switcher_html', array( $this, 'filter_html' ), 10, 2 );
    }

    /**
     * Return normalized switcher rows for the current/supplied storefront URL.
     * Entity-aware permalink services can refine the generic directory URL via
     * the shared itk_commerce_language_url contract.
     *
     * @param string $source_url Optional same-origin URL.
     * @return array<int,array<string,mixed>>
     */
    public function items( $source_url = '' ) {
        $items = array();

        foreach ( $this->context->enabled_languages() as $language ) {
            if ( empty( $language['code'] ) ) {
                continue;
            }

            $code = (string) $language['code'];
            $url  = function_exists( 'apply_filters' )
                ? apply_filters( 'itk_commerce_language_url', '', $code, $source_url )
                : $this->router->url_for( $code, $source_url );
            if ( '' === $url ) {
                continue;
            }

            $items[] = array(
                'code'      => $code,
                'locale'    => isset( $language['locale'] ) ? (string) $language['locale'] : '',
                'label'     => isset( $language['label'] ) ? (string) $language['label'] : strtoupper( $code ),
                'direction' => isset( $language['direction'] ) && 'rtl' === $language['direction'] ? 'rtl' : 'ltr',
                'url'       => $url,
                'current'   => $code === $this->context->code(),
            );
        }

        $filtered = apply_filters( 'itk_commerce_language_switcher_items', $items, $source_url, $this->context );
        return is_array( $filtered ) ? array_values( $filtered ) : $items;
    }

    /**
     * Shortcode callback.
     *
     * @param array<string,mixed> $atts Shortcode attributes.
     * @return string
     */
    public function shortcode( $atts ) {
        $atts = shortcode_atts(
            array(
                'display' => 'label',
                'class'   => '',
            ),
            is_array( $atts ) ? $atts : array(),
            'itk_language_switcher'
        );

        return $this->render( $atts );
    }

    /**
     * Public filter callback. Existing non-empty HTML stays authoritative so
     * themes/builders can deliberately replace the default markup.
     *
     * @param mixed $html Existing switcher HTML.
     * @param mixed $args Optional render arguments.
     * @return string
     */
    public function filter_html( $html, $args = array() ) {
        if ( is_string( $html ) && '' !== trim( $html ) ) {
            return $html;
        }

        return $this->render( is_array( $args ) ? $args : array() );
    }

    /**
     * Render accessible, style-neutral language links.
     *
     * @param array<string,mixed> $args Render arguments.
     * @return string
     */
    public function render( array $args = array() ) {
        $display = isset( $args['display'] ) ? sanitize_key( $args['display'] ) : 'label';
        if ( ! in_array( $display, array( 'label', 'code', 'both' ), true ) ) {
            $display = 'label';
        }

        $items = $this->items();
        if ( count( $items ) < 2 ) {
            return '';
        }

        $classes = array( 'itk-language-switcher' );
        if ( ! empty( $args['class'] ) ) {
            foreach ( preg_split( '/\s+/', (string) $args['class'] ) as $class ) {
                $class = sanitize_html_class( $class );
                if ( '' !== $class ) {
                    $classes[] = $class;
                }
            }
        }

        ob_start();
        ?>
        <nav class="<?php echo esc_attr( implode( ' ', array_values( array_unique( $classes ) ) ); ?>" aria-label="<?php echo esc_attr__( 'Language', 'itk-commerce-multilingual' ); ?>" data-itk-language-switcher>
            <ul class="itk-language-switcher__list" role="list">
                <?php foreach ( $items as $item ) : ?>
                    <?php
                    if ( ! is_array( $item ) || empty( $item['code'] ) || empty( $item['url'] ) ) {
                        continue;
                    }

                    $code      = (string) $item['code'];
                    $label     = isset( $item['label'] ) ? (string) $item['label'] : strtoupper( $code );
                    $direction = isset( $item['direction'] ) && 'rtl' === $item['direction'] ? 'rtl' : 'ltr';
                    $current   = ! empty( $item['current'] );

                    if ( 'code' === $display ) {
                        $visible = strtoupper( $code );
                    } elseif ( 'both' === $display ) {
                        $visible = $label . ' (' . strtoupper( $code ) . ')';
                    } else {
                        $visible = $label;
                    }
                    ?>
                    <li class="itk-language-switcher__item<?php echo $current ? ' is-current' : ''; ?>">
                        <a
                            class="itk-language-switcher__link"
                            href="<?php echo esc_url( (string) $item['url'] ); ?>"
                            lang="<?php echo esc_attr( $code ); ?>"
                            dir="<?php echo esc_attr( $direction ); ?>"
                            hreflang="<?php echo esc_attr( $code ); ?>"
                            <?php echo $current ? 'aria-current="page"' : ''; ?>
                        ><?php echo esc_html( $visible ); ?></a>
                    </li>
                <?php endforeach; ?>
            </ul>
        </nav>
        <?php

        return (string) ob_get_clean();
    }
}

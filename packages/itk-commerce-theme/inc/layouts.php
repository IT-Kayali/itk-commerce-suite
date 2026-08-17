<?php
/**
 * Reusable Theme layout-model registry and renderer.
 *
 * @package ITK_Commerce_Theme
 */

namespace ITK\Commerce\Theme;

defined( 'ABSPATH' ) || exit;

/**
 * Return Theme-owned presentation models.
 *
 * Optional modules may add models through the filter, but the Theme remains
 * responsible for rendering the actual template parts.
 *
 * @return array<string,array<string,array<string,string>>>
 */
function layout_models() {
    $models = array(
        'header' => array(
            'classic' => array(
                'label'    => __( 'Classic', 'itk-commerce' ),
                'template' => 'template-parts/header/site-header',
            ),
            'centered' => array(
                'label'    => __( 'Centered', 'itk-commerce' ),
                'template' => 'template-parts/header/centered',
            ),
            'shop' => array(
                'label'    => __( 'Shop Search', 'itk-commerce' ),
                'template' => 'template-parts/header/shop',
            ),
            'transparent' => array(
                'label'    => __( 'Transparent', 'itk-commerce' ),
                'template' => 'template-parts/header/site-header',
            ),
            'dark' => array(
                'label'    => __( 'Dark', 'itk-commerce' ),
                'template' => 'template-parts/header/site-header',
            ),
            'luxury' => array(
                'label'    => __( 'Luxury', 'itk-commerce' ),
                'template' => 'template-parts/header/centered',
            ),
            'sticky' => array(
                'label'    => __( 'Sticky', 'itk-commerce' ),
                'template' => 'template-parts/header/site-header',
            ),
            'vertical' => array(
                'label'    => __( 'Vertical', 'itk-commerce' ),
                'template' => 'template-parts/header/vertical',
            ),
        ),
        'footer' => array(
            'classic' => array(
                'label'    => __( 'Classic', 'itk-commerce' ),
                'template' => 'template-parts/footer/site-footer',
            ),
            'compact' => array(
                'label'    => __( 'Compact', 'itk-commerce' ),
                'template' => 'template-parts/footer/compact',
            ),
            'columns' => array(
                'label'    => __( 'Columns', 'itk-commerce' ),
                'template' => 'template-parts/footer/columns',
            ),
            'simple' => array(
                'label'    => __( 'Simple', 'itk-commerce' ),
                'template' => 'template-parts/footer/compact',
            ),
            'luxury' => array(
                'label'    => __( 'Luxury', 'itk-commerce' ),
                'template' => 'template-parts/footer/columns',
            ),
            'newsletter' => array(
                'label'    => __( 'Newsletter', 'itk-commerce' ),
                'template' => 'template-parts/footer/columns',
            ),
            'branches' => array(
                'label'    => __( 'Branches', 'itk-commerce' ),
                'template' => 'template-parts/footer/columns',
            ),
        ),
    );

    /**
     * Filter Theme presentation models.
     *
     * @param array<string,array<string,array<string,string>>> $models Models by area.
     */
    return apply_filters( 'itk_commerce_theme_layout_models', $models );
}

/**
 * Resolve a valid model identifier for a Theme layout area.
 *
 * @param string $area          Layout area, for example header or footer.
 * @param string $default_model Default model identifier.
 * @return string
 */
function layout_model( $area, $default_model = 'classic' ) {
    $area   = sanitize_key( $area );
    $models = layout_models();

    if ( ! isset( $models[ $area ] ) || ! is_array( $models[ $area ] ) ) {
        return sanitize_key( $default_model );
    }

    $default_model = sanitize_key( $default_model );
    if ( ! isset( $models[ $area ][ $default_model ] ) ) {
        $available     = array_keys( $models[ $area ] );
        $default_model = $available ? reset( $available ) : 'classic';
    }

    /**
     * Filter the selected model for an area.
     *
     * The Layouts module uses this extension point to apply customer-profile
     * and contextual assignments without modifying Theme templates.
     *
     * @param string $default_model Default/current model.
     * @param string $area          Layout area.
     */
    $selected = sanitize_key( apply_filters( 'itk_commerce_theme_layout_model', $default_model, $area ) );

    return isset( $models[ $area ][ $selected ] ) ? $selected : $default_model;
}

/**
 * Render a selected Theme-owned layout template with safe fallback.
 *
 * @param string $area          Layout area.
 * @param string $default_model Default model identifier.
 * @return void
 */
function render_layout( $area, $default_model = 'classic' ) {
    $area   = sanitize_key( $area );
    $models = layout_models();
    $model  = layout_model( $area, $default_model );

    if ( empty( $models[ $area ][ $model ]['template'] ) ) {
        return;
    }

    $template = sanitize_text_field( $models[ $area ][ $model ]['template'] );
    $located  = locate_template( $template . '.php', false, false );

    if ( ! $located && isset( $models[ $area ][ $default_model ]['template'] ) ) {
        $template = sanitize_text_field( $models[ $area ][ $default_model ]['template'] );
        $model    = $default_model;
    }

    /**
     * Fires immediately before a Theme layout model is rendered.
     *
     * @param string $area  Layout area.
     * @param string $model Selected model.
     */
    do_action( 'itk_commerce_before_theme_layout', $area, $model );

    get_template_part(
        $template,
        null,
        array(
            'itk_layout_area'  => $area,
            'itk_layout_model' => $model,
        )
    );

    /**
     * Fires immediately after a Theme layout model is rendered.
     *
     * @param string $area  Layout area.
     * @param string $model Selected model.
     */
    do_action( 'itk_commerce_after_theme_layout', $area, $model );
}

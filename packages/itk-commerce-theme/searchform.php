<?php
/**
 * Accessible site search form.
 *
 * @package ITK_Commerce_Theme
 */

defined( 'ABSPATH' ) || exit;
?>
<form role="search" method="get" class="itk-search-form" action="<?php echo esc_url( home_url( '/' ) ); ?>">
    <label class="screen-reader-text" for="itk-search-field"><?php esc_html_e( 'Search for:', 'itk-commerce' ); ?></label>
    <input id="itk-search-field" type="search" class="itk-search-form__field" placeholder="<?php echo esc_attr_x( 'Search …', 'placeholder', 'itk-commerce' ); ?>" value="<?php echo esc_attr( get_search_query() ); ?>" name="s">
    <button type="submit" class="itk-search-form__submit">
        <?php ITK\Commerce\Theme\icon( 'search' ); ?>
        <span class="screen-reader-text"><?php esc_html_e( 'Search', 'itk-commerce' ); ?></span>
    </button>
</form>

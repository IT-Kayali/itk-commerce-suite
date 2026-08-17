<?php
/**
 * WordPress menu-item field for binding a local menu item to a portable
 * customer-profile mega-menu definition key.
 *
 * @package ITK_Commerce_Layouts
 */

namespace ITK\Commerce\Layouts\Admin;

defined( 'ABSPATH' ) || exit;

final class MegaMenuFields {
    const META_KEY = '_itk_commerce_mega_menu_key';

    /**
     * Attach WordPress menu editor hooks.
     *
     * @return void
     */
    public function register() {
        add_action( 'wp_nav_menu_item_custom_fields', array( $this, 'render_field' ), 10, 5 );
        add_action( 'wp_update_nav_menu_item', array( $this, 'save_field' ), 10, 3 );
    }

    /**
     * Render the portable definition-key field in Appearance > Menus.
     *
     * @param int    $item_id           Menu item ID.
     * @param object $item              Menu item object.
     * @param int    $depth             Menu depth.
     * @param object $args              Menu arguments.
     * @param int    $current_object_id Current object ID.
     * @return void
     */
    public function render_field( $item_id, $item, $depth, $args, $current_object_id ) {
        unset( $item, $depth, $args, $current_object_id );

        if ( ! current_user_can( 'edit_theme_options' ) ) {
            return;
        }

        $value = get_post_meta( (int) $item_id, self::META_KEY, true );
        ?>
        <p class="description description-wide itk-commerce-menu-field">
            <label for="edit-menu-item-itk-mega-<?php echo esc_attr( (string) $item_id ); ?>">
                <?php esc_html_e( 'Commerce Mega-menu definition key', 'itk-commerce-layouts' ); ?><br>
                <input
                    type="text"
                    id="edit-menu-item-itk-mega-<?php echo esc_attr( (string) $item_id ); ?>"
                    class="widefat code edit-menu-item-itk-mega"
                    name="itk_mega_menu_key[<?php echo esc_attr( (string) $item_id ); ?>]"
                    value="<?php echo esc_attr( (string) $value ); ?>"
                    placeholder="catalog"
                >
                <span class="description">
                    <?php esc_html_e( 'Optional. Connects this menu item to a portable definition from the active customer profile.', 'itk-commerce-layouts' ); ?>
                </span>
            </label>
        </p>
        <?php
    }

    /**
     * Save the menu-item binding. WordPress already validates the menu-edit
     * request; capability and per-field sanitization are still enforced here.
     *
     * @param int   $menu_id         Menu ID.
     * @param int   $menu_item_db_id Menu item DB ID.
     * @param array $args            Menu item arguments.
     * @return void
     */
    public function save_field( $menu_id, $menu_item_db_id, $args ) {
        unset( $menu_id, $args );

        if ( ! current_user_can( 'edit_theme_options' ) ) {
            return;
        }

        $raw = isset( $_POST['itk_mega_menu_key'][ $menu_item_db_id ] )
            ? wp_unslash( $_POST['itk_mega_menu_key'][ $menu_item_db_id ] )
            : '';
        $key = sanitize_key( $raw );

        if ( '' === $key ) {
            delete_post_meta( (int) $menu_item_db_id, self::META_KEY );
            return;
        }

        update_post_meta( (int) $menu_item_db_id, self::META_KEY, $key );
    }
}

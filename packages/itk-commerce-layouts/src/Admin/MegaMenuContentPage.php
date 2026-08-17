<?php
/**
 * Rich mega-menu content editor.
 *
 * @package ITK_Commerce_Layouts
 */

namespace ITK\Commerce\Layouts\Admin;

use ITK\Commerce\Core\Core;

defined( 'ABSPATH' ) || exit;

final class MegaMenuContentPage {
    const PAGE_SLUG = 'itk-commerce-mega-content';

    /** @var string */
    private $page_hook = '';

    /**
     * @return void
     */
    public function register() {
        add_action( 'admin_menu', array( $this, 'add_menu' ) );
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
        add_action( 'admin_post_itk_commerce_save_mega_content', array( $this, 'save' ) );
    }

    /**
     * @return void
     */
    public function add_menu() {
        $this->page_hook = add_theme_page(
            __( 'Commerce Mega Menu', 'itk-commerce-layouts' ),
            __( 'Commerce Mega Menu', 'itk-commerce-layouts' ),
            'itk_manage_design',
            self::PAGE_SLUG,
            array( $this, 'render' )
        );
    }

    /**
     * @param string $hook_suffix Current admin hook.
     * @return void
     */
    public function enqueue_assets( $hook_suffix ) {
        if ( $this->page_hook !== $hook_suffix ) {
            return;
        }

        wp_enqueue_style(
            'itk-commerce-mega-content',
            plugins_url( 'assets/admin/mega-menu-content.css', \ITK\Commerce\Layouts\FILE ),
            array(),
            \ITK\Commerce\Layouts\VERSION
        );

        wp_enqueue_script(
            'itk-commerce-mega-content',
            plugins_url( 'assets/admin/mega-menu-content.js', \ITK\Commerce\Layouts\FILE ),
            array(),
            \ITK\Commerce\Layouts\VERSION,
            true
        );
    }

    /**
     * @return void
     */
    public function render() {
        if ( ! current_user_can( 'itk_manage_design' ) ) {
            wp_die( esc_html__( 'You do not have permission to manage Commerce mega menus.', 'itk-commerce-layouts' ) );
        }

        $core       = Core::instance();
        $profile_id = $core->settings()->active_profile_id();
        $profile    = $profile_id ? $core->profiles()->get( $profile_id ) : null;

        if ( ! is_array( $profile ) ) {
            $this->render_missing_profile();
            return;
        }

        $definitions = isset( $profile['layouts']['mega_menu']['definitions'] ) && is_array( $profile['layouts']['mega_menu']['definitions'] )
            ? $profile['layouts']['mega_menu']['definitions']
            : array();
        $content     = $this->rich_content( $profile );
        $saved       = isset( $_GET['updated'] ) && '1' === sanitize_text_field( wp_unslash( $_GET['updated'] ) );
        $error       = isset( $_GET['itk_error'] ) ? sanitize_key( wp_unslash( $_GET['itk_error'] ) ) : '';
        ?>
        <div class="wrap itk-mega-content-page">
            <div class="itk-mega-content-head">
                <div>
                    <span class="itk-mega-eyebrow"><?php esc_html_e( 'IT-Kayali Commerce Suite', 'itk-commerce-layouts' ); ?></span>
                    <h1><?php esc_html_e( 'Rich Mega Menu', 'itk-commerce-layouts' ); ?></h1>
                    <p><?php esc_html_e( 'Combine WordPress menu links, WooCommerce categories or products, promotional images, banners and optional Elementor templates inside each portable mega-menu definition.', 'itk-commerce-layouts' ); ?></p>
                </div>
                <div class="itk-mega-profile">
                    <span><?php esc_html_e( 'Active profile', 'itk-commerce-layouts' ); ?></span>
                    <strong><?php echo esc_html( isset( $profile['name'] ) ? $profile['name'] : $profile_id ); ?></strong>
                    <code><?php echo esc_html( $profile_id ); ?></code>
                </div>
            </div>

            <?php if ( $saved ) : ?>
                <div class="notice notice-success is-dismissible"><p><?php esc_html_e( 'Rich mega-menu content saved.', 'itk-commerce-layouts' ); ?></p></div>
            <?php endif; ?>
            <?php if ( $error ) : ?>
                <div class="notice notice-error"><p><?php esc_html_e( 'The rich mega-menu configuration could not be saved.', 'itk-commerce-layouts' ); ?></p></div>
            <?php endif; ?>

            <?php if ( ! $definitions ) : ?>
                <div class="itk-mega-empty">
                    <h2><?php esc_html_e( 'Create a mega-menu definition first', 'itk-commerce-layouts' ); ?></h2>
                    <p><?php esc_html_e( 'Open Commerce Layouts, create at least one Mega Menu definition key, save it, and then return here to add rich content.', 'itk-commerce-layouts' ); ?></p>
                    <a class="button button-primary" href="<?php echo esc_url( admin_url( 'themes.php?page=itk-commerce-layout-builder' ) ); ?>"><?php esc_html_e( 'Open Commerce Layouts', 'itk-commerce-layouts' ); ?></a>
                </div>
            <?php else : ?>
                <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" data-itk-mega-content-form>
                    <input type="hidden" name="action" value="itk_commerce_save_mega_content">
                    <input type="hidden" name="profile_id" value="<?php echo esc_attr( $profile_id ); ?>">
                    <?php wp_nonce_field( 'itk_commerce_save_mega_content' ); ?>

                    <div class="itk-mega-definition-list">
                        <?php foreach ( $definitions as $key => $definition ) : ?>
                            <?php
                            $key    = sanitize_key( $key );
                            $label  = isset( $definition['label'] ) && $definition['label'] ? $definition['label'] : $key;
                            $blocks = isset( $content[ $key ]['blocks'] ) && is_array( $content[ $key ]['blocks'] )
                                ? array_values( $content[ $key ]['blocks'] )
                                : array( array( 'type' => 'menu', 'title' => '', 'span' => 2 ) );
                            while ( count( $blocks ) < 4 ) {
                                $blocks[] = array();
                            }
                            ?>
                            <section class="itk-mega-definition">
                                <header class="itk-mega-definition__head">
                                    <div>
                                        <span><?php esc_html_e( 'Definition', 'itk-commerce-layouts' ); ?></span>
                                        <h2><?php echo esc_html( $label ); ?></h2>
                                        <code><?php echo esc_html( $key ); ?></code>
                                    </div>
                                    <div class="itk-mega-definition__meta">
                                        <span><?php echo esc_html( sprintf( __( '%d columns', 'itk-commerce-layouts' ), isset( $definition['columns'] ) ? absint( $definition['columns'] ) : 1 ) ); ?></span>
                                        <span><?php echo esc_html( isset( $definition['width'] ) && 'full' === $definition['width'] ? __( 'Full width', 'itk-commerce-layouts' ) : __( 'Aligned', 'itk-commerce-layouts' ) ); ?></span>
                                    </div>
                                </header>

                                <div class="itk-mega-content-blocks">
                                    <?php foreach ( array_slice( $blocks, 0, 6 ) as $index => $block ) : ?>
                                        <?php $this->render_block_editor( $key, $index, is_array( $block ) ? $block : array() ); ?>
                                    <?php endforeach; ?>
                                </div>
                            </section>
                        <?php endforeach; ?>
                    </div>

                    <div class="itk-mega-savebar">
                        <div>
                            <strong><?php esc_html_e( 'Safe profile storage', 'itk-commerce-layouts' ); ?></strong>
                            <span><?php esc_html_e( 'Rich blocks are stored under the Layouts module configuration, so the basic layout builder can update widths and assignments without deleting them.', 'itk-commerce-layouts' ); ?></span>
                        </div>
                        <button type="submit" class="button button-primary button-hero"><?php esc_html_e( 'Save rich menu', 'itk-commerce-layouts' ); ?></button>
                    </div>
                </form>
            <?php endif; ?>
        </div>
        <?php
    }

    /**
     * @param string              $definition_key Definition key.
     * @param int                 $index          Block index.
     * @param array<string,mixed> $block          Existing block.
     * @return void
     */
    private function render_block_editor( $definition_key, $index, array $block ) {
        $prefix = 'mega_blocks[' . $definition_key . '][' . $index . ']';
        $type   = isset( $block['type'] ) ? sanitize_key( $block['type'] ) : '';
        $span   = max( 1, min( 6, isset( $block['span'] ) ? absint( $block['span'] ) : 1 ) );
        ?>
        <article class="itk-mega-content-block" data-itk-mega-block>
            <div class="itk-mega-content-block__top">
                <label>
                    <span><?php esc_html_e( 'Block type', 'itk-commerce-layouts' ); ?></span>
                    <select name="<?php echo esc_attr( $prefix . '[type]' ); ?>" data-itk-block-type>
                        <option value="" <?php selected( $type, '' ); ?>><?php esc_html_e( 'Unused', 'itk-commerce-layouts' ); ?></option>
                        <option value="menu" <?php selected( $type, 'menu' ); ?>><?php esc_html_e( 'Menu links', 'itk-commerce-layouts' ); ?></option>
                        <option value="categories" <?php selected( $type, 'categories' ); ?>><?php esc_html_e( 'Product categories', 'itk-commerce-layouts' ); ?></option>
                        <option value="products" <?php selected( $type, 'products' ); ?>><?php esc_html_e( 'Products', 'itk-commerce-layouts' ); ?></option>
                        <option value="image" <?php selected( $type, 'image' ); ?>><?php esc_html_e( 'Image', 'itk-commerce-layouts' ); ?></option>
                        <option value="banner" <?php selected( $type, 'banner' ); ?>><?php esc_html_e( 'Promo banner', 'itk-commerce-layouts' ); ?></option>
                        <option value="elementor" <?php selected( $type, 'elementor' ); ?>><?php esc_html_e( 'Elementor template', 'itk-commerce-layouts' ); ?></option>
                    </select>
                </label>
                <label>
                    <span><?php esc_html_e( 'Column span', 'itk-commerce-layouts' ); ?></span>
                    <select name="<?php echo esc_attr( $prefix . '[span]' ); ?>">
                        <?php for ( $span_option = 1; $span_option <= 6; $span_option++ ) : ?>
                            <option value="<?php echo esc_attr( (string) $span_option ); ?>" <?php selected( $span, $span_option ); ?>><?php echo esc_html( (string) $span_option ); ?></option>
                        <?php endfor; ?>
                    </select>
                </label>
                <label class="itk-field-wide">
                    <span><?php esc_html_e( 'Section title', 'itk-commerce-layouts' ); ?></span>
                    <input type="text" name="<?php echo esc_attr( $prefix . '[title]' ); ?>" value="<?php echo esc_attr( isset( $block['title'] ) ? $block['title'] : '' ); ?>" placeholder="<?php esc_attr_e( 'Optional heading', 'itk-commerce-layouts' ); ?>">
                </label>
            </div>

            <div class="itk-block-fields" data-itk-block-fields="menu">
                <p><?php esc_html_e( 'Uses the existing WordPress child menu items assigned under this top-level menu item. Second-level children are included automatically.', 'itk-commerce-layouts' ); ?></p>
            </div>

            <div class="itk-block-fields" data-itk-block-fields="categories">
                <label class="itk-field-wide">
                    <span><?php esc_html_e( 'Category slugs', 'itk-commerce-layouts' ); ?></span>
                    <input type="text" name="<?php echo esc_attr( $prefix . '[slugs]' ); ?>" value="<?php echo esc_attr( isset( $block['slugs'] ) ? implode( ', ', (array) $block['slugs'] ) : '' ); ?>" placeholder="men, women, gifts">
                    <small><?php esc_html_e( 'Comma-separated. Leave blank to show top-level product categories.', 'itk-commerce-layouts' ); ?></small>
                </label>
                <label>
                    <span><?php esc_html_e( 'Limit', 'itk-commerce-layouts' ); ?></span>
                    <input type="number" min="1" max="12" name="<?php echo esc_attr( $prefix . '[limit]' ); ?>" value="<?php echo esc_attr( (string) ( isset( $block['limit'] ) ? absint( $block['limit'] ) : 6 ) ); ?>">
                </label>
                <label class="itk-check-row">
                    <input type="checkbox" name="<?php echo esc_attr( $prefix . '[show_images]' ); ?>" value="1" <?php checked( ! empty( $block['show_images'] ) ); ?>>
                    <span><?php esc_html_e( 'Show category images', 'itk-commerce-layouts' ); ?></span>
                </label>
            </div>

            <div class="itk-block-fields" data-itk-block-fields="products">
                <label>
                    <span><?php esc_html_e( 'Product source', 'itk-commerce-layouts' ); ?></span>
                    <?php $source = isset( $block['source'] ) ? sanitize_key( $block['source'] ) : 'latest'; ?>
                    <select name="<?php echo esc_attr( $prefix . '[source]' ); ?>">
                        <option value="latest" <?php selected( $source, 'latest' ); ?>><?php esc_html_e( 'Latest', 'itk-commerce-layouts' ); ?></option>
                        <option value="featured" <?php selected( $source, 'featured' ); ?>><?php esc_html_e( 'Featured', 'itk-commerce-layouts' ); ?></option>
                        <option value="on_sale" <?php selected( $source, 'on_sale' ); ?>><?php esc_html_e( 'On sale', 'itk-commerce-layouts' ); ?></option>
                        <option value="category" <?php selected( $source, 'category' ); ?>><?php esc_html_e( 'Category slug', 'itk-commerce-layouts' ); ?></option>
                        <option value="ids" <?php selected( $source, 'ids' ); ?>><?php esc_html_e( 'Specific product IDs', 'itk-commerce-layouts' ); ?></option>
                    </select>
                </label>
                <label class="itk-field-wide">
                    <span><?php esc_html_e( 'Source value', 'itk-commerce-layouts' ); ?></span>
                    <input type="text" name="<?php echo esc_attr( $prefix . '[value]' ); ?>" value="<?php echo esc_attr( isset( $block['value'] ) ? $block['value'] : '' ); ?>" placeholder="category-slug or 12, 18, 44">
                    <small><?php esc_html_e( 'Used only for Category or Specific IDs.', 'itk-commerce-layouts' ); ?></small>
                </label>
                <label>
                    <span><?php esc_html_e( 'Limit', 'itk-commerce-layouts' ); ?></span>
                    <input type="number" min="1" max="8" name="<?php echo esc_attr( $prefix . '[limit]' ); ?>" value="<?php echo esc_attr( (string) ( isset( $block['limit'] ) ? absint( $block['limit'] ) : 4 ) ); ?>">
                </label>
            </div>

            <div class="itk-block-fields" data-itk-block-fields="image">
                <?php $this->render_media_fields( $prefix, $block, true ); ?>
            </div>

            <div class="itk-block-fields" data-itk-block-fields="banner">
                <label>
                    <span><?php esc_html_e( 'Eyebrow', 'itk-commerce-layouts' ); ?></span>
                    <input type="text" name="<?php echo esc_attr( $prefix . '[eyebrow]' ); ?>" value="<?php echo esc_attr( isset( $block['eyebrow'] ) ? $block['eyebrow'] : '' ); ?>" placeholder="New collection">
                </label>
                <label class="itk-field-wide">
                    <span><?php esc_html_e( 'Banner text', 'itk-commerce-layouts' ); ?></span>
                    <textarea rows="2" name="<?php echo esc_attr( $prefix . '[text]' ); ?>"><?php echo esc_textarea( isset( $block['text'] ) ? $block['text'] : '' ); ?></textarea>
                </label>
                <?php $this->render_media_fields( $prefix, $block, false ); ?>
                <label>
                    <span><?php esc_html_e( 'Button label', 'itk-commerce-layouts' ); ?></span>
                    <input type="text" name="<?php echo esc_attr( $prefix . '[link_label]' ); ?>" value="<?php echo esc_attr( isset( $block['link_label'] ) ? $block['link_label'] : '' ); ?>" placeholder="Shop now">
                </label>
            </div>

            <div class="itk-block-fields" data-itk-block-fields="elementor">
                <label>
                    <span><?php esc_html_e( 'Elementor template ID', 'itk-commerce-layouts' ); ?></span>
                    <input type="number" min="1" name="<?php echo esc_attr( $prefix . '[template_id]' ); ?>" value="<?php echo esc_attr( (string) ( isset( $block['template_id'] ) ? absint( $block['template_id'] ) : '' ) ); ?>">
                    <small><?php esc_html_e( 'Optional integration. If Elementor is inactive or the template is missing, this block renders nothing and navigation continues normally.', 'itk-commerce-layouts' ); ?></small>
                </label>
            </div>
        </article>
        <?php
    }

    /**
     * @param string              $prefix Form field prefix.
     * @param array<string,mixed> $block  Existing block.
     * @param bool                $alt    Whether to render image alt field.
     * @return void
     */
    private function render_media_fields( $prefix, array $block, $alt ) {
        ?>
        <label class="itk-field-wide">
            <span><?php esc_html_e( 'Image URL', 'itk-commerce-layouts' ); ?></span>
            <input type="url" name="<?php echo esc_attr( $prefix . '[image_url]' ); ?>" value="<?php echo esc_attr( isset( $block['image_url'] ) ? $block['image_url'] : '' ); ?>" placeholder="https://example.com/image.jpg">
        </label>
        <label class="itk-field-wide">
            <span><?php esc_html_e( 'Link URL', 'itk-commerce-layouts' ); ?></span>
            <input type="url" name="<?php echo esc_attr( $prefix . '[link_url]' ); ?>" value="<?php echo esc_attr( isset( $block['link_url'] ) ? $block['link_url'] : '' ); ?>" placeholder="https://example.com/collection/">
        </label>
        <?php if ( $alt ) : ?>
            <label class="itk-field-wide">
                <span><?php esc_html_e( 'Image alt text', 'itk-commerce-layouts' ); ?></span>
                <input type="text" name="<?php echo esc_attr( $prefix . '[alt]' ); ?>" value="<?php echo esc_attr( isset( $block['alt'] ) ? $block['alt'] : '' ); ?>">
            </label>
        <?php endif; ?>
        <?php
    }

    /**
     * Persist only rich mega-menu module configuration for existing definition
     * keys. Other profile sections and modules remain untouched.
     *
     * @return void
     */
    public function save() {
        if ( ! current_user_can( 'itk_manage_design' ) ) {
            wp_die( esc_html__( 'You do not have permission to manage Commerce mega menus.', 'itk-commerce-layouts' ) );
        }

        check_admin_referer( 'itk_commerce_save_mega_content' );

        $core       = Core::instance();
        $profile_id = isset( $_POST['profile_id'] ) ? sanitize_key( wp_unslash( $_POST['profile_id'] ) ) : '';
        $profile    = $profile_id ? $core->profiles()->get( $profile_id ) : null;

        if ( ! is_array( $profile ) ) {
            $this->redirect_error( 'profile_missing' );
        }

        $definitions = isset( $profile['layouts']['mega_menu']['definitions'] ) && is_array( $profile['layouts']['mega_menu']['definitions'] )
            ? $profile['layouts']['mega_menu']['definitions']
            : array();
        $submitted   = isset( $_POST['mega_blocks'] ) && is_array( $_POST['mega_blocks'] )
            ? wp_unslash( $_POST['mega_blocks'] )
            : array();
        $content     = array();

        foreach ( $definitions as $raw_key => $definition ) {
            $key = sanitize_key( $raw_key );
            if ( ! $key ) {
                continue;
            }

            $raw_blocks = isset( $submitted[ $key ] ) && is_array( $submitted[ $key ] ) ? $submitted[ $key ] : array();
            $blocks     = $this->sanitize_blocks( $raw_blocks );
            if ( $blocks ) {
                $content[ $key ] = array( 'blocks' => $blocks );
            }
        }

        if ( empty( $profile['modules'] ) || ! is_array( $profile['modules'] ) ) {
            $profile['modules'] = array();
        }
        if ( empty( $profile['modules']['configuration'] ) || ! is_array( $profile['modules']['configuration'] ) ) {
            $profile['modules']['configuration'] = array();
        }
        if ( empty( $profile['modules']['configuration'][ \ITK\Commerce\Layouts\MODULE_ID ] ) || ! is_array( $profile['modules']['configuration'][ \ITK\Commerce\Layouts\MODULE_ID ] ) ) {
            $profile['modules']['configuration'][ \ITK\Commerce\Layouts\MODULE_ID ] = array();
        }

        $profile['modules']['configuration'][ \ITK\Commerce\Layouts\MODULE_ID ]['mega_content'] = $content;
        $profile['profile_version'] = $this->next_patch_version( isset( $profile['profile_version'] ) ? $profile['profile_version'] : '1.0.0' );

        $result = $core->profiles()->save( $profile );
        if ( is_wp_error( $result ) ) {
            $this->redirect_error( $result->get_error_code() );
        }

        wp_safe_redirect( add_query_arg( 'updated', '1', admin_url( 'themes.php?page=' . self::PAGE_SLUG ) ) );
        exit;
    }

    /**
     * @param array<int,array<string,mixed>> $blocks Raw blocks.
     * @return array<int,array<string,mixed>>
     */
    private function sanitize_blocks( array $blocks ) {
        $clean   = array();
        $allowed = array( 'menu', 'categories', 'products', 'image', 'banner', 'elementor' );

        foreach ( array_slice( $blocks, 0, 6 ) as $block ) {
            if ( ! is_array( $block ) ) {
                continue;
            }

            $type = isset( $block['type'] ) ? sanitize_key( $block['type'] ) : '';
            if ( ! in_array( $type, $allowed, true ) ) {
                continue;
            }

            $item = array(
                'type'  => $type,
                'title' => isset( $block['title'] ) ? sanitize_text_field( $block['title'] ) : '',
                'span'  => max( 1, min( 6, isset( $block['span'] ) ? absint( $block['span'] ) : 1 ) ),
            );

            if ( 'categories' === $type ) {
                $item['slugs']       = isset( $block['slugs'] ) ? sanitize_text_field( $block['slugs'] ) : '';
                $item['limit']       = max( 1, min( 12, isset( $block['limit'] ) ? absint( $block['limit'] ) : 6 ) );
                $item['show_images'] = ! empty( $block['show_images'] );
            } elseif ( 'products' === $type ) {
                $source = isset( $block['source'] ) ? sanitize_key( $block['source'] ) : 'latest';
                $item['source'] = in_array( $source, array( 'latest', 'featured', 'on_sale', 'category', 'ids' ), true ) ? $source : 'latest';
                $item['value']  = isset( $block['value'] ) ? sanitize_text_field( $block['value'] ) : '';
                $item['limit']  = max( 1, min( 8, isset( $block['limit'] ) ? absint( $block['limit'] ) : 4 ) );
            } elseif ( 'image' === $type ) {
                $item['image_url'] = isset( $block['image_url'] ) ? esc_url_raw( $block['image_url'] ) : '';
                $item['link_url']  = isset( $block['link_url'] ) ? esc_url_raw( $block['link_url'] ) : '';
                $item['alt']       = isset( $block['alt'] ) ? sanitize_text_field( $block['alt'] ) : '';
            } elseif ( 'banner' === $type ) {
                $item['eyebrow']    = isset( $block['eyebrow'] ) ? sanitize_text_field( $block['eyebrow'] ) : '';
                $item['text']       = isset( $block['text'] ) ? sanitize_textarea_field( $block['text'] ) : '';
                $item['image_url']  = isset( $block['image_url'] ) ? esc_url_raw( $block['image_url'] ) : '';
                $item['link_url']   = isset( $block['link_url'] ) ? esc_url_raw( $block['link_url'] ) : '';
                $item['link_label'] = isset( $block['link_label'] ) ? sanitize_text_field( $block['link_label'] ) : '';
            } elseif ( 'elementor' === $type ) {
                $item['template_id'] = isset( $block['template_id'] ) ? absint( $block['template_id'] ) : 0;
            }

            $clean[] = $item;
        }

        return $clean;
    }

    /**
     * @param array<string,mixed> $profile Profile.
     * @return array<string,mixed>
     */
    private function rich_content( array $profile ) {
        if (
            empty( $profile['modules']['configuration'][ \ITK\Commerce\Layouts\MODULE_ID ]['mega_content'] ) ||
            ! is_array( $profile['modules']['configuration'][ \ITK\Commerce\Layouts\MODULE_ID ]['mega_content'] )
        ) {
            return array();
        }

        return $profile['modules']['configuration'][ \ITK\Commerce\Layouts\MODULE_ID ]['mega_content'];
    }

    /**
     * @return void
     */
    private function render_missing_profile() {
        ?>
        <div class="wrap itk-mega-content-page">
            <div class="itk-mega-empty">
                <h1><?php esc_html_e( 'Rich Mega Menu', 'itk-commerce-layouts' ); ?></h1>
                <p><?php esc_html_e( 'No active Commerce customer profile exists yet. Open Commerce Layouts and save a layout first.', 'itk-commerce-layouts' ); ?></p>
                <a class="button button-primary" href="<?php echo esc_url( admin_url( 'themes.php?page=itk-commerce-layout-builder' ) ); ?>"><?php esc_html_e( 'Open Commerce Layouts', 'itk-commerce-layouts' ); ?></a>
            </div>
        </div>
        <?php
    }

    /**
     * @param string $code Error code.
     * @return void
     */
    private function redirect_error( $code ) {
        wp_safe_redirect(
            add_query_arg(
                'itk_error',
                sanitize_key( $code ),
                admin_url( 'themes.php?page=' . self::PAGE_SLUG )
            )
        );
        exit;
    }

    /**
     * @param string $version Semantic version.
     * @return string
     */
    private function next_patch_version( $version ) {
        $parts = array_map( 'absint', explode( '.', preg_replace( '/[^0-9.].*$/', '', (string) $version ) ) );
        $parts = array_pad( array_slice( $parts, 0, 3 ), 3, 0 );
        $parts[2]++;
        return implode( '.', $parts );
    }
}

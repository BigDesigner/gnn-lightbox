<?php
/**
 * Plugin Name: GNN Lightbox
 * Plugin URI:  https://github.com/BigDesigner/gnn-lightbox
 * Description: Modern, lightweight and fast lightbox plugin built with vanilla JS and CSS3.
 * Version:     1.2.0
 * Requires at least: 6.0
 * Requires PHP: 7.4
 * Author:      BigDesigner
 * Author URI:  https://github.com/BigDesigner
 * Text Domain: gnn-lightbox
 * Domain Path: /languages
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Block direct access
}

define( 'GNN_LIGHTBOX_FILE', __FILE__ );
define( 'GNN_LIGHTBOX_DIR', plugin_dir_path( __FILE__ ) );
define( 'GNN_LIGHTBOX_URL', plugin_dir_url( __FILE__ ) );

// Read and lock the version from the VERSION file
$gnn_lightbox_version = '1.0.0';
if ( file_exists( GNN_LIGHTBOX_DIR . 'VERSION' ) ) {
    $gnn_lightbox_version = trim( file_get_contents( GNN_LIGHTBOX_DIR . 'VERSION' ) );
}
define( 'GNN_LIGHTBOX_VERSION', $gnn_lightbox_version );

class GNN_Lightbox {

    private static $instance = null;

    public static function get_instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        $this->init_hooks();
        $this->load_updater();
    }

    private function init_hooks() {
        add_action( 'init', array( $this, 'load_textdomain' ), 1 );
        add_action( 'admin_menu', array( $this, 'register_admin_menu' ) );
        add_action( 'admin_init', array( $this, 'register_settings' ) );
        add_filter( 'plugin_action_links', array( $this, 'plugin_action_links' ), 10, 2 );
        add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_frontend_assets' ) );

        // Shortcode support (for legacy plugin compatibility or direct use)
        add_shortcode( 'gnn_lightbox', array( $this, 'lightbox_shortcode' ) );

        // Automatic content detection filter
        add_filter( 'the_content', array( $this, 'auto_lightbox_content' ), 12 );
    }

    private function get_default_settings() {
        return array(
            'enabled'          => '1', // Turns the plugin on/off
            'auto_detect'      => '1', // Automatically catch image links in content
            'standard_support' => '1', // class="lightbox" / rel="lightbox" support (legacy WP standard)
            'theme'            => 'light', // light | dark
            'gutenberg_mode'   => 'ignore', // ignore = don't interfere with WP's native lightbox, override = force ours
        );
    }

    public function get_settings() {
        $settings = get_option( 'gnn_lightbox_settings', array() );
        return wp_parse_args( $settings, $this->get_default_settings() );
    }

    public function register_settings() {
        register_setting( 'gnn_lightbox_settings_group', 'gnn_lightbox_settings', array( $this, 'sanitize_settings' ) );
    }

    public function sanitize_settings( $input ) {
        $defaults = $this->get_default_settings();
        $output   = array();

        $output['enabled']          = empty( $input['enabled'] ) ? '0' : '1';
        $output['auto_detect']      = empty( $input['auto_detect'] ) ? '0' : '1';
        $output['standard_support'] = empty( $input['standard_support'] ) ? '0' : '1';
        $output['theme']            = ( isset( $input['theme'] ) && 'dark' === $input['theme'] ) ? 'dark' : 'light';
        $output['gutenberg_mode']   = ( isset( $input['gutenberg_mode'] ) && 'override' === $input['gutenberg_mode'] ) ? 'override' : 'ignore';

        return wp_parse_args( $output, $defaults );
    }

    public function load_textdomain() {
        load_plugin_textdomain( 'gnn-lightbox', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
    }

    private function load_updater() {
        if ( is_admin() ) {
            require_once GNN_LIGHTBOX_DIR . 'class-gnn-lightbox-updater.php';
            new GNN_Lightbox_Updater();
        }
    }

    public function register_admin_menu() {
        // GNN menu position standard: 79.XXX (see GNN Plugin Standard)
        // Position: 79.108
        add_menu_page(
            __( 'GNN Lightbox - Overview', 'gnn-lightbox' ),
            'GNN Lightbox',
            'manage_options',
            'lightbox-settings',
            array( $this, 'render_overview_page' ),
            'dashicons-format-image',
            '79.108'
        );
    }

    public function render_overview_page() {
        if ( ! current_user_can( 'manage_options' ) ) {
            return;
        }

        $settings = $this->get_settings();
        ?>
        <style>
            .gnn-page { max-width: 900px; }
            .gnn-page h1 { display: flex; align-items: center; gap: 10px; font-weight: 600; }
            .gnn-page h1 .dashicons { font-size: 26px; width: 26px; height: 26px; color: #2271b1; }
            .gnn-page .gnn-lead { color: #555; max-width: 640px; }

            .gnn-card { background: #fff; border: 1px solid #dcdcde; border-radius: 8px; box-shadow: 0 1px 2px rgba(0,0,0,.04); margin-top: 20px; overflow: hidden; }
            .gnn-card-header { padding: 18px 24px; border-bottom: 1px solid #eee; }
            .gnn-card-header h2 { margin: 0; font-size: 14px; text-transform: uppercase; letter-spacing: .04em; color: #1d2327; }

            .gnn-row { display: flex; align-items: flex-start; gap: 24px; padding: 20px 24px; border-bottom: 1px solid #f0f0f1; }
            .gnn-row:last-child { border-bottom: none; }
            .gnn-row-label { flex: 0 0 240px; font-weight: 600; color: #1d2327; padding-top: 2px; }
            .gnn-row-control { flex: 1; min-width: 0; }
            .gnn-row-desc { margin: 8px 0 0; color: #6b7280; font-size: 13px; }

            .gnn-toggle { position: relative; display: inline-block; width: 44px; height: 24px; vertical-align: middle; flex-shrink: 0; }
            .gnn-toggle input { opacity: 0; width: 0; height: 0; position: absolute; }
            .gnn-toggle-slider { position: absolute; inset: 0; background-color: #ccc; transition: background-color .2s ease; border-radius: 999px; cursor: pointer; }
            .gnn-toggle-slider::before { content: ""; position: absolute; height: 18px; width: 18px; left: 3px; top: 3px; background-color: #fff; transition: transform .2s ease; border-radius: 50%; box-shadow: 0 1px 2px rgba(0,0,0,.3); }
            .gnn-toggle input:checked + .gnn-toggle-slider { background-color: #2271b1; }
            .gnn-toggle input:checked + .gnn-toggle-slider::before { transform: translateX(20px); }
            .gnn-toggle input:focus-visible + .gnn-toggle-slider { outline: 2px solid #2271b1; outline-offset: 2px; }
            .gnn-toggle-row { display: flex; align-items: center; gap: 12px; }
            .gnn-toggle-row .gnn-toggle-label { font-weight: 400; color: #1d2327; }

            .gnn-choice-list { display: flex; flex-direction: column; gap: 10px; }
            .gnn-choice { display: flex; align-items: flex-start; gap: 10px; padding: 10px 12px; border: 1px solid #e2e2e2; border-radius: 6px; cursor: pointer; transition: border-color .15s ease, background-color .15s ease; }
            .gnn-choice:hover { border-color: #b8d3ea; }
            .gnn-choice input { margin-top: 3px; }
            .gnn-choice input:checked ~ .gnn-choice-text { color: #1d2327; }
            .gnn-choice:has(input:checked) { border-color: #2271b1; background: #f2f8fc; }
            .gnn-choice-text { font-size: 13px; line-height: 1.5; color: #444; }

            .gnn-actions { padding: 18px 24px; background: #fafafa; border-top: 1px solid #eee; }
            .gnn-save-btn { background: #2271b1; border-color: #2271b1; padding: 6px 18px; height: auto; border-radius: 4px; font-weight: 500; }
            .gnn-save-btn:hover { background: #135e96; border-color: #135e96; }
        </style>
        <div class="wrap gnn-page">
            <h1><span class="dashicons dashicons-format-image"></span><?php echo esc_html__( 'GNN Lightbox', 'gnn-lightbox' ); ?></h1>
            <p class="gnn-lead"><?php echo esc_html__( 'Welcome to the modern, lightweight and fast lightbox plugin.', 'gnn-lightbox' ); ?> <?php echo esc_html__( 'You can use it by adding class="gnn-lightbox" to your image links, or by letting image links in your content open in the lightbox automatically.', 'gnn-lightbox' ); ?></p>

            <form method="post" action="options.php">
                <?php settings_fields( 'gnn_lightbox_settings_group' ); ?>

                <div class="gnn-card">
                    <div class="gnn-card-header">
                        <h2><?php echo esc_html__( 'General Settings', 'gnn-lightbox' ); ?></h2>
                    </div>

                    <div class="gnn-row">
                        <div class="gnn-row-label"><?php echo esc_html__( 'Plugin Status', 'gnn-lightbox' ); ?></div>
                        <div class="gnn-row-control">
                            <div class="gnn-toggle-row">
                                <label class="gnn-toggle">
                                    <input type="checkbox" name="gnn_lightbox_settings[enabled]" value="1" <?php checked( '1', $settings['enabled'] ); ?> />
                                    <span class="gnn-toggle-slider"></span>
                                </label>
                                <span class="gnn-toggle-label"><?php echo esc_html__( 'Active', 'gnn-lightbox' ); ?></span>
                            </div>
                            <p class="gnn-row-desc"><?php echo esc_html__( 'When disabled, the lightbox CSS/JS files are not loaded on the site and automatic detection is turned off.', 'gnn-lightbox' ); ?></p>
                        </div>
                    </div>

                    <div class="gnn-row">
                        <div class="gnn-row-label"><?php echo esc_html__( 'Automatic Detection', 'gnn-lightbox' ); ?></div>
                        <div class="gnn-row-control">
                            <div class="gnn-toggle-row">
                                <label class="gnn-toggle">
                                    <input type="checkbox" name="gnn_lightbox_settings[auto_detect]" value="1" <?php checked( '1', $settings['auto_detect'] ); ?> />
                                    <span class="gnn-toggle-slider"></span>
                                </label>
                                <span class="gnn-toggle-label"><?php echo esc_html__( 'Automatically bind image links in content (.jpg, .png, .webp, etc.) to the lightbox', 'gnn-lightbox' ); ?></span>
                            </div>
                        </div>
                    </div>

                    <div class="gnn-row">
                        <div class="gnn-row-label"><?php echo esc_html__( 'Standard Compatibility', 'gnn-lightbox' ); ?></div>
                        <div class="gnn-row-control">
                            <div class="gnn-toggle-row">
                                <label class="gnn-toggle">
                                    <input type="checkbox" name="gnn_lightbox_settings[standard_support]" value="1" <?php checked( '1', $settings['standard_support'] ); ?> />
                                    <span class="gnn-toggle-slider"></span>
                                </label>
                                <span class="gnn-toggle-label"><?php echo esc_html__( 'Also recognize the legacy WordPress lightbox standard: class="lightbox" and rel="lightbox"', 'gnn-lightbox' ); ?></span>
                            </div>
                            <p class="gnn-row-desc"><?php echo esc_html__( 'Links with rel="lightbox" or class="lightbox" coming from other plugins/themes are also caught automatically.', 'gnn-lightbox' ); ?></p>
                        </div>
                    </div>

                    <div class="gnn-row">
                        <div class="gnn-row-label"><?php echo esc_html__( 'Gutenberg Image Block Lightbox', 'gnn-lightbox' ); ?></div>
                        <div class="gnn-row-control">
                            <div class="gnn-choice-list">
                                <label class="gnn-choice">
                                    <input type="radio" name="gnn_lightbox_settings[gutenberg_mode]" value="ignore" <?php checked( 'ignore', $settings['gutenberg_mode'] ); ?> />
                                    <span class="gnn-choice-text"><?php echo esc_html__( "Don't interfere (default) — let WordPress's own image block lightbox (wp-lightbox-container) work as-is, ours will not activate", 'gnn-lightbox' ); ?></span>
                                </label>
                                <label class="gnn-choice">
                                    <input type="radio" name="gnn_lightbox_settings[gutenberg_mode]" value="override" <?php checked( 'override', $settings['gutenberg_mode'] ); ?> />
                                    <span class="gnn-choice-text"><?php echo esc_html__( "Override — disable WordPress's native lightbox and use GNN Lightbox for these images too", 'gnn-lightbox' ); ?></span>
                                </label>
                            </div>
                        </div>
                    </div>

                    <div class="gnn-row">
                        <div class="gnn-row-label"><?php echo esc_html__( 'Theme Color', 'gnn-lightbox' ); ?></div>
                        <div class="gnn-row-control">
                            <div class="gnn-choice-list">
                                <label class="gnn-choice">
                                    <input type="radio" name="gnn_lightbox_settings[theme]" value="light" <?php checked( 'light', $settings['theme'] ); ?> />
                                    <span class="gnn-choice-text"><?php echo esc_html__( 'White', 'gnn-lightbox' ); ?></span>
                                </label>
                                <label class="gnn-choice">
                                    <input type="radio" name="gnn_lightbox_settings[theme]" value="dark" <?php checked( 'dark', $settings['theme'] ); ?> />
                                    <span class="gnn-choice-text"><?php echo esc_html__( 'Black', 'gnn-lightbox' ); ?></span>
                                </label>
                            </div>
                        </div>
                    </div>

                    <div class="gnn-actions">
                        <?php submit_button( __( 'Save Settings', 'gnn-lightbox' ), 'primary', 'submit', false, array( 'class' => 'button button-primary gnn-save-btn' ) ); ?>
                    </div>
                </div>
            </form>
        </div>
        <?php
    }

    public function plugin_action_links( $links, $file ) {
        if ( $file === plugin_basename( GNN_LIGHTBOX_FILE ) ) {
            $donate_link = '<a href="https://buymeacoffee.com/bigdesigner" target="_blank" rel="noopener noreferrer" style="font-weight:bold; color:#d63638;">'
                . esc_html__( 'Donate', 'gnn-lightbox' ) . '</a>';

            $settings_link = '<a href="' . esc_url( admin_url( 'admin.php?page=lightbox-settings' ) ) . '">'
                . esc_html__( 'Settings', 'gnn-lightbox' ) . '</a>';

            $update_url  = wp_nonce_url( admin_url( 'plugins.php?gnn_lightbox_check_update=1' ), 'gnn_lightbox_manual_update' );
            $update_link = '<a href="' . esc_url( $update_url ) . '">' . esc_html__( 'Check Updates', 'gnn-lightbox' ) . '</a>';

            array_unshift( $links, $donate_link, $settings_link, $update_link );
        }
        return $links;
    }

    /**
     * Theme color presets. Managed from a single source; extend this array
     * when a new theme or custom color picker is added to the admin panel.
     */
    private function get_theme_presets() {
        return array(
            'light' => array(
                '--gnn-lightbox-overlay-bg'     => 'rgba(0, 0, 0, 0.85)',
                '--gnn-lightbox-content-bg'     => '#ffffff',
                '--gnn-lightbox-content-shadow' => 'rgba(0, 0, 0, 0.5)',
                '--gnn-lightbox-img-shadow'     => 'rgba(0, 0, 0, 0.1)',
                '--gnn-lightbox-close-bg'       => '#ffffff',
                '--gnn-lightbox-close-bg-hover' => '#f0f0f0',
                '--gnn-lightbox-close-shadow'   => 'rgba(0, 0, 0, 0.3)',
                '--gnn-lightbox-close-stroke'   => '#333333',
                '--gnn-lightbox-loader-track'   => 'rgba(255, 255, 255, 0.3)',
                '--gnn-lightbox-loader-active'  => '#ffffff',
                '--gnn-lightbox-nav-bg'         => 'rgba(255, 255, 255, 0.15)',
                '--gnn-lightbox-nav-bg-hover'   => 'rgba(255, 255, 255, 0.3)',
                '--gnn-lightbox-nav-stroke'     => '#ffffff',
                '--gnn-lightbox-icon-bg'        => 'rgba(0, 0, 0, 0.55)',
                '--gnn-lightbox-icon-stroke'    => '#ffffff',
            ),
            'dark' => array(
                '--gnn-lightbox-overlay-bg'     => 'rgba(0, 0, 0, 0.92)',
                '--gnn-lightbox-content-bg'     => '#1e1e1e',
                '--gnn-lightbox-content-shadow' => 'rgba(0, 0, 0, 0.7)',
                '--gnn-lightbox-img-shadow'     => 'rgba(0, 0, 0, 0.4)',
                '--gnn-lightbox-close-bg'       => '#1e1e1e',
                '--gnn-lightbox-close-bg-hover' => '#2c2c2c',
                '--gnn-lightbox-close-shadow'   => 'rgba(0, 0, 0, 0.5)',
                '--gnn-lightbox-close-stroke'   => '#ffffff',
                '--gnn-lightbox-loader-track'   => 'rgba(255, 255, 255, 0.2)',
                '--gnn-lightbox-loader-active'  => '#ffffff',
                '--gnn-lightbox-nav-bg'         => 'rgba(255, 255, 255, 0.1)',
                '--gnn-lightbox-nav-bg-hover'   => 'rgba(255, 255, 255, 0.2)',
                '--gnn-lightbox-nav-stroke'     => '#ffffff',
                '--gnn-lightbox-icon-bg'        => 'rgba(0, 0, 0, 0.65)',
                '--gnn-lightbox-icon-stroke'    => '#ffffff',
            ),
        );
    }

    public function enqueue_frontend_assets() {
        $settings = $this->get_settings();

        if ( empty( $settings['enabled'] ) ) {
            return;
        }

        wp_enqueue_style(
            'gnn-lightbox-css',
            GNN_LIGHTBOX_URL . 'assets/css/gnn-lightbox.css',
            array(),
            GNN_LIGHTBOX_VERSION
        );

        $presets = $this->get_theme_presets();
        $theme   = isset( $presets[ $settings['theme'] ] ) ? $presets[ $settings['theme'] ] : $presets['light'];

        $inline_css = ':root{';
        foreach ( $theme as $var_name => $value ) {
            $inline_css .= esc_attr( $var_name ) . ':' . esc_attr( $value ) . ';';
        }
        $inline_css .= '}';

        wp_add_inline_style( 'gnn-lightbox-css', $inline_css );

        wp_enqueue_script(
            'gnn-lightbox-js',
            GNN_LIGHTBOX_URL . 'assets/js/gnn-lightbox.js',
            array(),
            GNN_LIGHTBOX_VERSION,
            true // Footer
        );

        wp_localize_script( 'gnn-lightbox-js', 'gnnLightboxSettings', array(
            'standardSupport' => ! empty( $settings['standard_support'] ),
            'gutenbergMode'   => $settings['gutenberg_mode'],
        ) );
    }

    public function auto_lightbox_content( $content ) {
        $settings = $this->get_settings();

        if ( empty( $settings['enabled'] ) || empty( $settings['auto_detect'] ) ) {
            return $content;
        }

        // Automatically add the gnn-lightbox class to every <a> tag whose href points to an image
        $pattern = '/(<a[^>]*href=["\'](?:[^"\']+)\.(?:jpg|jpeg|png|gif|webp|avif|bmp)(?:\?[^"\']*)?["\'][^>]*>)/i';
        $content = preg_replace_callback( $pattern, function( $matches ) {
            $link = $matches[1];
            if ( strpos( $link, 'class=' ) !== false ) {
                // A class already exists, append gnn-lightbox
                if ( strpos( $link, 'gnn-lightbox' ) === false ) {
                    $link = preg_replace( '/class=["\']/', 'class="gnn-lightbox ', $link, 1 );
                }
            } else {
                // No class yet, add class="gnn-lightbox"
                $link = str_replace( '<a ', '<a class="gnn-lightbox" ', $link );
            }
            return $link;
        }, $content );

        return $content;
    }

    public function lightbox_shortcode( $atts, $content = null ) {
        $atts = shortcode_atts( array(
            'href' => '',
            'rel'  => ''
        ), $atts, 'gnn_lightbox' );

        $href_attr = ! empty( $atts['href'] ) ? ' href="' . esc_url( $atts['href'] ) . '"' : '';
        $rel_attr  = ! empty( $atts['rel'] ) ? ' data-lightbox-gallery="' . esc_attr( $atts['rel'] ) . '"' : '';

        return '<a class="gnn-lightbox"' . $href_attr . $rel_attr . '>' . do_shortcode( $content ) . '</a>';
    }
}

// Bootstrap the plugin
GNN_Lightbox::get_instance();

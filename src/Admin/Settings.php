<?php
/**
 * Truemail for Easy Digital Downloads
 *
 * @package   daandev/correct-contact
 * @author    Daan van den Bergh
 *            https://daan.dev
 * @copyright © 2023-2026 Daan van den Bergh
 */

namespace CorrectContact\Admin;

defined( 'ABSPATH' ) || exit;

class Settings {
    const ACCESS_TOKEN = 'cc_access_token';

    const APP_URL = 'cc_app_url';

    const BLOCK_PURCHASE = 'cc_block_checkout';

    const FIELD_SELECTORS = 'cc_field_selectors';

    const SETTINGS_FIELD_GENERAL = 'cc-general-settings';

    const SETTINGS_FIELD_ADVANCED = 'cc-advanced-settings';

    /** @var string */
    private $active_tab = '';

    /**
     * Settings constructor.
     */
    public function __construct() {
        $this->active_tab = isset( $_GET['tab'] ) ? sanitize_text_field( $_GET['tab'] ) : self::SETTINGS_FIELD_GENERAL;

        add_action( 'admin_menu', [ $this, 'add_menu' ] );
        add_action( 'admin_init', [ $this, 'register_settings' ] );
        add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_assets' ] );

        // Tabs
        add_action( 'cc_settings_tab', [ $this, 'general_settings_tab' ], 1 );
        add_action( 'cc_settings_tab', [ $this, 'advanced_settings_tab' ], 2 );

        // Content
        add_action( 'cc_settings_content', [ $this, 'general_settings_content' ], 1 );
        add_action( 'cc_settings_content', [ $this, 'advanced_settings_content' ], 2 );
    }

    /**
     * Add the settings menu.
     */
    public function add_menu() {
        add_options_page(
                __( 'Correct Contact', 'correct-contact' ),
                __( 'Correct Contact', 'correct-contact' ),
                'manage_options',
                'correct-contact',
                [ $this, 'render_settings_page' ]
        );
    }

    /**
     * Register settings.
     */
    public function register_settings() {
        $settings_config = [
                self::SETTINGS_FIELD_GENERAL  => [
                        'section_id'    => 'cc_general_section',
                        'section_title' => __( 'General Settings', 'correct-contact' ),
                        'settings'      => [
                                self::FIELD_SELECTORS => [
                                        'label'             => __( 'Fields to Validate', 'correct-contact' ),
                                        'callback'          => [ $this, 'render_selectors_field' ],
                                        'desc'              => __( 'Add the CSS selectors (classes or IDs) of the email fields you want to validate. Press enter after each entry.', 'correct-contact' ),
                                        'sanitize_callback' => [ $this, 'sanitize_selectors' ],
                                ],
                                self::BLOCK_PURCHASE  => [
                                        'label'             => __( 'Prevent Purchase on Failure', 'correct-contact' ),
                                        'callback'          => [ $this, 'render_checkbox_field' ],
                                        'desc'              => __( 'If enabled, the user won\'t be able to finalize the purchase in WooCommerce and Easy Digital Downloads if the email address fails to validate. Fails silently on a request timeout.', 'correct-contact' ),
                                        'sanitize_callback' => null,
                                ],
                        ],
                ],
                self::SETTINGS_FIELD_ADVANCED => [
                        'section_id'    => 'cc_advanced_section',
                        'section_title' => __( 'Advanced Settings', 'correct-contact' ),
                        'settings'      => [
                                self::ACCESS_TOKEN => [
                                        'label'             => __( 'Access Token', 'correct-contact' ),
                                        'callback'          => [ $this, 'render_text_field' ],
                                        'desc'              => __( 'Enter the Access Token (environment variable) you\'ve configured in your Truemail instance here.', 'correct-contact' ),
                                        'sanitize_callback' => null,
                                ],
                                self::APP_URL      => [
                                        'label'             => __( 'Application URL', 'correct-contact' ),
                                        'callback'          => [ $this, 'render_text_field' ],
                                        'desc'              => __( 'Enter the URL of your Truemail instance here.', 'correct-contact' ),
                                        'sanitize_callback' => null,
                                ],
                        ],
                ],
        ];

        // Register all settings
        foreach ( $settings_config as $tab => $config ) {
            foreach ( $config['settings'] as $setting_id => $setting ) {
                $args = [];
                if ( $setting['sanitize_callback'] ) {
                    $args['sanitize_callback'] = $setting['sanitize_callback'];
                }
                register_setting( $tab, $setting_id, $args );
            }
        }

        // Add sections and fields for active tab
        if ( isset( $settings_config[ $this->active_tab ] ) ) {
            $config = $settings_config[ $this->active_tab ];

            add_settings_section(
                    $config['section_id'],
                    $config['section_title'],
                    null,
                    $this->active_tab
            );

            foreach ( $config['settings'] as $setting_id => $setting ) {
                add_settings_field(
                        $setting_id,
                        $setting['label'],
                        $setting['callback'],
                        $this->active_tab,
                        $config['section_id'],
                        [
                                'id'   => $setting_id,
                                'desc' => $setting['desc'],
                        ]
                );
            }
        }
    }

    /**
     * Enqueue Select2 and admin styles for the settings page.
     */
    public function enqueue_assets( $hook ) {
        if ( $hook !== 'settings_page_correct-contact' ) {
            return;
        }

        // Enqueue admin styles
        wp_enqueue_style( 'cc-admin', plugin_dir_url( CC_PLUGIN_FILE ) . 'assets/css/cc-admin.css', [], filemtime( plugin_dir_path( CC_PLUGIN_FILE ) . 'assets/css/cc-admin.css' ) );

        // Enqueue Select2
        wp_enqueue_style( 'select2', 'https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css' );
        wp_enqueue_script( 'select2', 'https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js', [ 'jquery' ] );

        wp_add_inline_script( 'select2', "
			jQuery(document).ready(function($) {
				$('#cc_field_selectors').select2({
					tags: true,
					tokenSeparators: [',', ' '],
					placeholder: '" . __( 'Add selectors...', 'correct-contact' ) . "'
				});
			});
		" );
    }

    /**
     * Render text field.
     */
    public function render_text_field( $args ) {
        $value = get_option( $args['id'] );
        echo '<input type="text" id="' . esc_attr( $args['id'] ) . '" name="' . esc_attr( $args['id'] ) . '" value="' . esc_attr( $value ) . '" class="regular-text">';
        echo '<p class="description">' . esc_html( $args['desc'] ) . '</p>';
    }

    /**
     * Render checkbox field.
     */
    public function render_checkbox_field( $args ) {
        $value = get_option( $args['id'] );
        echo '<label for="' . esc_attr( $args['id'] ) . '">';
        echo '<input type="checkbox" id="' . esc_attr( $args['id'] ) . '" name="' . esc_attr( $args['id'] ) . '" value="1" ' . checked( 1, $value, false ) . '>';
        echo esc_html( $args['desc'] );
        echo '</label>';
    }

    /**
     * Render selectors field using Select2.
     */
    public function render_selectors_field( $args ) {
        $value     = get_option( $args['id'], '#edd-email' );
        $selectors = explode( ',', $value );
        echo '<select id="' . esc_attr( $args['id'] ) . '" name="' . esc_attr( $args['id'] ) . '[]" class="regular-text" multiple="multiple" style="width: 100%;">';
        foreach ( $selectors as $selector ) {
            $selector = trim( $selector );
            if ( ! empty( $selector ) ) {
                echo '<option value="' . esc_attr( $selector ) . '" selected="selected">' . esc_html( $selector ) . '</option>';
            }
        }
        echo '</select>';
        echo '<p class="description">' . esc_html( $args['desc'] ) . '</p>';
    }

    /**
     * Sanitize selectors.
     */
    public function sanitize_selectors( $input ) {
        if ( is_array( $input ) ) {
            return implode( ',', array_map( 'sanitize_text_field', $input ) );
        }

        return sanitize_text_field( $input );
    }

    /**
     * Render settings page.
     */
    public function render_settings_page() {
        ?>
        <div class="wrap cc-admin">
            <h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
            <div class="settings-column">
                <h2 class="cc-nav nav-tab-wrapper">
                    <?php do_action( 'cc_settings_tab' ); ?>
                </h2>
                <?php do_action( 'cc_settings_content' ); ?>
            </div>
        </div>
        <?php
    }

    /**
     * General Settings tab.
     */
    public function general_settings_tab() {
        $this->generate_tab(
                self::SETTINGS_FIELD_GENERAL,
                'dashicons-admin-settings',
                __( 'General', 'correct-contact' )
        );
    }

    /**
     * Generate a tab.
     *
     * @param string $id
     * @param string $icon
     * @param string $label
     */
    private function generate_tab( $id, $icon, $label ) {
        $active = $this->active_tab === $id ? 'nav-tab-active' : '';
        ?>
        <a class="nav-tab dashicons-before <?php echo esc_attr( $icon ); ?> <?php echo esc_attr( $active ); ?>"
           href="<?php echo esc_url( admin_url( 'options-general.php?page=correct-contact&tab=' . $id ) ); ?>">
            <?php echo esc_html( $label ); ?>
        </a>
        <?php
    }

    /**
     * Advanced Settings tab.
     */
    public function advanced_settings_tab() {
        $this->generate_tab(
                self::SETTINGS_FIELD_ADVANCED,
                'dashicons-admin-generic',
                __( 'Advanced', 'correct-contact' )
        );
    }

    /**
     * General Settings content.
     */
    public function general_settings_content() {
        if ( $this->active_tab !== self::SETTINGS_FIELD_GENERAL ) {
            return;
        }
        ?>
        <form action="options.php" method="post">
            <div class="cc-settings-container">
                <?php
                settings_fields( self::SETTINGS_FIELD_GENERAL );
                do_settings_sections( self::SETTINGS_FIELD_GENERAL );
                ?>
            </div>
            <?php submit_button(); ?>
        </form>
        <?php
    }

    /**
     * Advanced Settings content.
     */
    public function advanced_settings_content() {
        if ( $this->active_tab !== self::SETTINGS_FIELD_ADVANCED ) {
            return;
        }
        ?>
        <form action="options.php" method="post">
            <div class="cc-settings-container">
                <?php
                settings_fields( self::SETTINGS_FIELD_ADVANCED );
                do_settings_sections( self::SETTINGS_FIELD_ADVANCED );
                ?>
            </div>
            <?php submit_button(); ?>
        </form>
        <?php
    }
}

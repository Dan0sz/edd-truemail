<?php
/**
 * Correct Contact - Email validation for WordPress
 *
 * @package   daandev/correct-contact
 * @author    Daan van den Bergh
 *            https://daan.dev
 * @copyright © 2023-2026 Daan van den Bergh
 */

namespace CorrectContact\Admin;

use CorrectContact\Helper;
use CorrectContact\Options;

defined( 'ABSPATH' ) || exit;

class Settings {
    const OPTION_NAME = Options::OPTION_NAME;

    const ACCESS_TOKEN = 'access_token';

    const APP_URL = 'app_url';

    const BLOCK_PURCHASE = 'block_checkout';

    const FIELD_SELECTORS = 'field_selectors';

    const DO_TOKEN = 'do_token';

    const REGION = 'region';

    const SETTINGS_FIELD_GENERAL = 'cc-general-settings';

    const SETTINGS_FIELD_ADVANCED = 'cc-advanced-settings';

    const SETUP_COMPLETED = 'cc_setup_completed';

    const SETUP_SKIPPED = 'cc_setup_skipped';

    /** @var string */
    private $active_tab = '';

    /**
     * Settings constructor.
     */
    public function __construct() {
        $this->active_tab = isset( $_GET['tab'] ) ? sanitize_text_field( $_GET['tab'] ) : self::SETTINGS_FIELD_GENERAL;

        new Wizard\Ajax();

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
                __( 'Correct Contact | Email Validation for WordPress', 'correct-contact' ),
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
                        'intro'         => '<p>' . __( 'CorrectContact validates email addresses using a Truemail service.', 'correct-contact' ) . '</p><p>' . __( 'For most users, this service is created automatically during setup.', 'correct-contact' ) . ' ' . __( 'Advanced users can configure a custom Truemail server here.', 'correct-contact' ) . '</p>',
                        'settings'      => [
                                self::ACCESS_TOKEN  => [
                                        'label'             => __( 'Access Token', 'correct-contact' ),
                                        'callback'          => [ $this, 'render_text_field' ],
                                        'desc'              => __( 'Enter the Access Token (environment variable) you\'ve configured in your Truemail instance here.', 'correct-contact' ),
                                        'sanitize_callback' => null,
                                ],
                                self::APP_URL       => [
                                        'label'             => __( 'Application URL', 'correct-contact' ),
                                        'callback'          => [ $this, 'render_text_field' ],
                                        'desc'              => __( 'Enter the URL of your Truemail instance here.', 'correct-contact' ),
                                        'sanitize_callback' => null,
                                ],
                                'app_url_interlude' => [
                                        'callback' => [ $this, 'render_interlude_field' ],
                                        'desc'     => '<p><strong>' . __( 'Don\'t have a Truemail server yet?', 'correct-contact' ) . '</strong></p><ul><li><a href="#" class="cc-wizard-restart">' .
                                                      __( 'Follow the wizard', 'correct-contact' ) . '</a> ' . __( 'to create one automatically, or', 'correct-contact' ) . '</li><li><a href="#" 
        target="_blank">' . __( 'Set one up yourself', 'correct-contact' ) . '</a>.</li></ul>',
                                ],
                        ],
                ],
        ];

        // Register all settings
        register_setting( self::SETTINGS_FIELD_GENERAL, self::OPTION_NAME, [ 'sanitize_callback' => [ $this, 'sanitize', ], ] );
        register_setting( self::SETTINGS_FIELD_ADVANCED, self::OPTION_NAME, [ 'sanitize_callback' => [ $this, 'sanitize', ], ] );

        // Add sections and fields for the active tab
        if ( isset( $settings_config[ $this->active_tab ] ) ) {
            $config = $settings_config[ $this->active_tab ];

            // Create callback for section intro if it exists
            $section_callback = null;
            if ( isset( $config['intro'] ) && ! empty( $config['intro'] ) ) {
                $section_callback = function () use ( $config ) {
                    echo '<p>' . wp_kses_post( $config['intro'] ) . '</p>';
                };
            }

            add_settings_section( $config['section_id'], $config['section_title'], $section_callback, $this->active_tab );

            foreach ( $config['settings'] as $setting_id => $setting ) {
                // Check if this is an interlude field (no label)
                $label = isset( $setting['label'] ) ? $setting['label'] : '';

                add_settings_field(
                        $setting_id,
                        $label,
                        $setting['callback'],
                        $this->active_tab,
                        $config['section_id'],
                        [
                                'id'   => $setting_id,
                                'desc' => $setting['desc'],
                                'name' => self::OPTION_NAME . "[$setting_id]",
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

        // Enqueue admin scripts (always loaded)
        wp_enqueue_script( 'cc-admin', plugin_dir_url( CC_PLUGIN_FILE ) . 'assets/js/cc-admin.js', [], filemtime( plugin_dir_path( CC_PLUGIN_FILE ) . 'assets/js/cc-admin.js' ), true );

        // Create nonce once for both admin and wizard scripts
        $wizard_nonce = wp_create_nonce( 'cc_wizard_nonce' );

        wp_localize_script( 'cc-admin', 'ccAdmin', [
                'ajaxUrl' => admin_url( 'admin-ajax.php' ),
                'nonce'   => $wizard_nonce,
        ] );

        // If setup is not completed, enqueue wizard assets
        if ( Helper::should_display_wizard() ) {
            wp_enqueue_style( 'cc-wizard', plugin_dir_url( CC_PLUGIN_FILE ) . 'assets/css/cc-wizard.css', [], filemtime( plugin_dir_path( CC_PLUGIN_FILE ) . 'assets/css/cc-wizard.css' ) );
            wp_enqueue_script( 'cc-wizard', plugin_dir_url( CC_PLUGIN_FILE ) . 'assets/js/cc-wizard.js', [], filemtime( plugin_dir_path( CC_PLUGIN_FILE ) . 'assets/js/cc-wizard.js' ), true );

            wp_localize_script( 'cc-wizard', 'ccWizard', [
                    'ajaxUrl'          => admin_url( 'admin-ajax.php' ),
                    'nonce'            => $wizard_nonce,
                    'removingText'     => __( 'Removing...', 'correct-contact' ),
                    'completingText'   => __( 'Completing...', 'correct-contact' ),
                    'redirectingText'  => __( 'Redirecting...', 'correct-contact' ),
                    'tokenRemovedText' => __( 'Token removed', 'correct-contact' ),
            ] );

            return;
        }

        // Enqueue Select2
        wp_enqueue_style( 'select2', 'https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css' );
        wp_enqueue_script( 'select2', 'https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js' );

        wp_add_inline_script( 'select2', "
			document.addEventListener('DOMContentLoaded', function() {
                if (typeof jQuery !== 'undefined') {
                    jQuery('#field_selectors').select2({
                        tags: true,
                        tokenSeparators: [',', ' '],
                        placeholder: '" . __( 'Add selectors...', 'correct-contact' ) . "'
                    });
                }
			});
		" );
    }

    /**
     * Render text field.
     */
    public function render_text_field( $args ) {
        $value = Options::get( $args['id'] );
        echo '<input type="text" id="' . esc_attr( $args['id'] ) . '" name="' . esc_attr( $args['name'] ) . '" value="' . esc_attr( $value ) . '" class="regular-text">';
        echo '<p class="description">' . esc_html( $args['desc'] ) . '</p>';
    }

    /**
     * Render checkbox field.
     */
    public function render_checkbox_field( $args ) {
        $value = Options::get( $args['id'] );
        echo '<label for="' . esc_attr( $args['id'] ) . '">';
        echo '<input type="checkbox" id="' . esc_attr( $args['id'] ) . '" name="' . esc_attr( $args['name'] ) . '" value="1" ' . checked( 1, $value, false ) . '>';
        echo esc_html( $args['desc'] );
        echo '</label>';
    }

    /**
     * Render selectors field using Select2.
     */
    public function render_selectors_field( $args ) {
        $value     = Options::get( $args['id'], [] );
        $selectors = is_array( $value ) ? $value : explode( ',', $value );
        echo '<select id="' . esc_attr( $args['id'] ) . '" name="' . esc_attr( $args['name'] ) . '[]" class="regular-text" multiple="multiple" style="width: 100%;">';
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
     * Render interlude field (description only, no label or input).
     */
    public function render_interlude_field( $args ) {
        echo wp_kses_post( $args['desc'] );
    }

    /**
     * Sanitize all settings.
     */
    public function sanitize( $input ) {
        // Get existing options to merge with new input
        $existing = get_option( self::OPTION_NAME, [] );

        // Merge existing options with new input to preserve settings from other tabs
        $output = is_array( $existing ) ? $existing : [];

        if ( isset( $input[ self::FIELD_SELECTORS ] ) ) {
            $output[ self::FIELD_SELECTORS ] = $this->sanitize_selectors( $input[ self::FIELD_SELECTORS ] );
        }

        if ( isset( $input[ self::ACCESS_TOKEN ] ) ) {
            $output[ self::ACCESS_TOKEN ] = sanitize_text_field( $input[ self::ACCESS_TOKEN ] );
        }

        if ( isset( $input[ self::APP_URL ] ) ) {
            $output[ self::APP_URL ] = esc_url_raw( $input[ self::APP_URL ] );
        }

        if ( isset( $input[ self::DO_TOKEN ] ) ) {
            $output[ self::DO_TOKEN ] = sanitize_text_field( $input[ self::DO_TOKEN ] );
        }

        if ( isset( $input[ self::REGION ] ) ) {
            $output[ self::REGION ] = sanitize_text_field( $input[ self::REGION ] );
        }

        if ( isset( $input[ self::BLOCK_PURCHASE ] ) ) {
            $output[ self::BLOCK_PURCHASE ] = (int) $input[ self::BLOCK_PURCHASE ];
        }

        return $output;
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
        // Show the wizard if setup is not completed
        if ( Helper::should_display_wizard() ) {
            Helper::render_admin_view( 'view-wizard' );

            return;
        }

        // Show Settings page
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
        $this->render_settings_content( self::SETTINGS_FIELD_GENERAL );
    }

    /**
     * Render settings content for a specific tab.
     *
     * @param string $tab_id The tab ID to render.
     */
    private function render_settings_content( $tab_id ) {
        if ( $this->active_tab !== $tab_id ) {
            return;
        }
        ?>
        <form action="options.php" method="post">
            <div class="cc-settings-container">
                <?php
                settings_fields( $tab_id );
                do_settings_sections( $tab_id );
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
        $this->render_settings_content( self::SETTINGS_FIELD_ADVANCED );
    }
}

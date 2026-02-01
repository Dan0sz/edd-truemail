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

    const SETUP_COMPLETED = 'cc_setup_completed';

    const DO_TOKEN = 'do_token';

    const REGION = 'region';

    const SETTINGS_FIELD_GENERAL = 'cc-general-settings';

    const SETTINGS_FIELD_ADVANCED = 'cc-advanced-settings';

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
        register_setting( self::SETTINGS_FIELD_GENERAL, self::OPTION_NAME, [
                'sanitize_callback' => [
                        $this,
                        'sanitize',
                ],
        ] );
        register_setting( self::SETTINGS_FIELD_ADVANCED, self::OPTION_NAME, [
                'sanitize_callback' => [
                        $this,
                        'sanitize',
                ],
        ] );

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

        // If setup is not completed, enqueue wizard assets
        if ( ! Helper::is_setup_completed() ) {
            wp_enqueue_style( 'cc-wizard', plugin_dir_url( CC_PLUGIN_FILE ) . 'assets/css/cc-wizard.css', [], filemtime( plugin_dir_path( CC_PLUGIN_FILE ) . 'assets/css/cc-wizard.css' ) );
            wp_enqueue_script( 'cc-wizard', plugin_dir_url( CC_PLUGIN_FILE ) . 'assets/js/cc-wizard.js', [], filemtime( plugin_dir_path( CC_PLUGIN_FILE ) . 'assets/js/cc-wizard.js' ), true );

            wp_localize_script( 'cc-wizard', 'ccWizard', [
                    'ajaxUrl' => admin_url( 'admin-ajax.php' ),
                    'nonce'   => wp_create_nonce( 'cc_wizard_nonce' ),
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
        // Show wizard if setup is not completed
        if ( ! Helper::is_setup_completed() ) {
            $this->render_wizard();

            return;
        }

        // Show normal settings page
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
     * Render the setup wizard.
     */
    private function render_wizard() {
        ?>
        <div class="wrap cc-admin">
            <h1><?php echo esc_html__( 'Correct Contact Setup Wizard', 'correct-contact' ); ?></h1>
            <h2 class="cc-nav nav-tab-wrapper">
                <a href="#" class="nav-tab nav-tab-active cc-wizard-nav-item"
                   data-slide="0"><?php esc_html_e( 'Getting Started', 'correct-contact' ); ?></a>
                <a href="#" class="nav-tab cc-wizard-nav-item"
                   data-slide="1"><?php esc_html_e( 'Create Account', 'correct-contact' ); ?></a>
                <a href="#" class="nav-tab cc-wizard-nav-item"
                   data-slide="2"><?php esc_html_e( 'Create API Token', 'correct-contact' ); ?></a>
                <a href="#" class="nav-tab cc-wizard-nav-item"
                   data-slide="3"><?php esc_html_e( 'Create App', 'correct-contact' ); ?></a>
                <a href="#" class="nav-tab cc-wizard-nav-item"
                   data-slide="4"><?php esc_html_e( 'Done', 'correct-contact' ); ?></a>
            </h2>
            <div class="cc-wizard-container">
                <!-- Slide 0: Intro -->
                <div class="cc-wizard-slide" data-slide="0">
                    <h2><?php esc_html_e( 'Before we get started', 'correct-contact' ); ?></h2>
                    <p><?php esc_html_e( 'CorrectContact runs on your own infrastructure. It does not use external email verification services.', 'correct-contact' ); ?></p>
                    <p><?php esc_html_e( 'Instead, we\'re going to create a small server in your own DigitalOcean account.', 'correct-contact' ); ?></p>

                    <h3><?php esc_html_e( 'Why DigitalOcean?', 'correct-contact' ); ?></h3>
                    <ul>
                        <li><?php esc_html_e( 'Automatically create your own email validation server', 'correct-contact' ); ?></li>
                        <li><?php esc_html_e( 'Unlimited email validations for a low, monthly fee', 'correct-contact' ); ?></li>
                        <li><?php esc_html_e( 'Reliable, EU-based infrastructure', 'correct-contact' ); ?></li>
                    </ul>

                    <h3><?php esc_html_e( 'What this means for you:', 'correct-contact' ); ?></h3>
                    <ul>
                        <li><?php esc_html_e( 'No third-party email validation services', 'correct-contact' ); ?></li>
                        <li><?php esc_html_e( 'No API limits or usage-based pricing', 'correct-contact' ); ?></li>
                        <li><?php esc_html_e( 'Full control over your data and infrastructure', 'correct-contact' ); ?></li>
                    </ul>

                    <div class="cc-wizard-actions">
                        <button type="button"
                                class="button button-primary cc-wizard-next"><?php esc_html_e( 'Get started', 'correct-contact' ); ?></button>
                    </div>

                    <p class="cc-wizard-footer"><?php esc_html_e( 'You\'re in control at all times. CorrectContact only automates the setup.', 'correct-contact' ); ?></p>
                </div>

                <!-- Slide 1: Create DigitalOcean account -->
                <div class="cc-wizard-slide" data-slide="1" style="display: none;">
                    <h2><?php esc_html_e( 'Create your DigitalOcean account', 'correct-contact' ); ?></h2>
                    <p><?php esc_html_e( 'To run the email validation service, CorrectContact uses a small server in your own DigitalOcean account.', 'correct-contact' ); ?></p>
                    <p><?php esc_html_e( 'If you don\'t have a DigitalOcean account yet, create one by clicking the button below. If you\'re all set, continue to the next step.', 'correct-contact' ); ?></p>

                    <div class="cc-notice info">
                        <h3><?php esc_html_e( 'New to DigitalOcean?', 'correct-contact' ); ?></h3>
                        <p><?php esc_html_e( 'New accounts receive $200 in free credit to try DigitalOcean for 60 days.', 'correct-contact' ); ?></p>
                        <p><?php esc_html_e( 'This is more than enough to run your email validation app for weeks at no cost.', 'correct-contact' ); ?></p>
                    </div>

                    <div class="cc-wizard-actions">
                        <a href="https://m.do.co/c/1cfcf14ddad0" target="_blank"
                           class="button button-primary"><?php esc_html_e( 'Create DigitalOcean account', 'correct-contact' ); ?></a>
                        <button type="button"
                                class="button button-secondary cc-wizard-next"><?php esc_html_e( 'I already have an account → Continue', 'correct-contact' ); ?></button>
                    </div>

                    <p class="cc-wizard-footer"><?php esc_html_e( 'No server is created yet. You remain in full control of your account and infrastructure.', 'correct-contact' ); ?></p>
                </div>

                <!-- Slide 2: Create API token -->
                <div class="cc-wizard-slide" data-slide="2" style="display: none;">
                    <h2><?php esc_html_e( 'Create a DigitalOcean API token', 'correct-contact' ); ?></h2>
                    <p><?php esc_html_e( 'CorrectContact needs a DigitalOcean API token to create the email validation app for you.', 'correct-contact' ); ?></p>
                    <p><?php wp_kses_post( 'This token is used <strong>only during setup</strong>. You will be prompted to remove it once the setup is complete.', 'correct-contact' ); ?></p>
                    <p><?php esc_html_e( 'To create the API token, follow these steps', 'correct-contact' ); ?>:</p>
                    <ol>
                        <li><a href="https://cloud.digitalocean.com/account/api/tokens/new"
                               target="_blank"><?php esc_html_e( 'Click here to start creating a new API token', 'correct-contact' ); ?></a>
                        </li>
                        <li>
                            <?php esc_html_e( 'Set the Token Name to "Correct Contact". You can leave the Expiration and Scopes setting to their defaults.', 'correct-contact' ); ?>
                        </li>
                        <li>
                            <?php esc_html_e( 'Make sure the token has the following scopes or, permissions:', 'correct-contact' ); ?>
                            <ul>
                                <li><?php echo wp_kses_post( __( '<strong>App:</strong> create/read', 'correct-contact' ) ); ?></li>
                                <li><?php echo wp_kses_post( __( '<strong>Project:</strong> create/read', 'correct-contact' ) ); ?></li>
                            </ul>
                        </li>
                        <li>
                            <?php esc_html_e( 'Click "Generate Token"', 'correct-contact' ); ?>
                        </li>
                        <li>
                            <?php esc_html_e( 'Copy the token and paste it in the field below.', 'correct-contact' ); ?>
                        </li>
                    </ol>
                    <p>
                        <input type="text" id="cc-do-token" class="regular-text"
                               value="<?php echo esc_attr( Options::get( self::DO_TOKEN ) ); ?>"
                               placeholder="<?php esc_attr_e( 'Paste your API token here', 'correct-contact' ); ?>">
                    </p>

                    <div class="cc-wizard-actions">
                        <button type="button" class="button button-primary cc-wizard-next"
                                disabled><?php esc_html_e( 'Continue', 'correct-contact' ); ?></button>
                    </div>

                    <p class="cc-wizard-footer"><?php esc_html_e( 'The token is stored locally and never shared.', 'correct-contact' ); ?></p>
                </div>

                <!-- Slide 3: Create app -->
                <div class="cc-wizard-slide" data-slide="3" style="display: none;">
                    <h2><?php esc_html_e( 'Create your email validation app', 'correct-contact' ); ?></h2>
                    <p><?php esc_html_e( 'Once you click Create app, CorrectContact will set up your email validation service.', 'correct-contact' ); ?></p>
                    <p><?php esc_html_e( 'A small app, called Truemail, will be installed on a DigitalOcean droplet in your account. This app is responsible for validating email addresses in real time.', 'correct-contact' ); ?></p>
                    <p><?php esc_html_e( 'The app runs on a basic server costing $10 per month, billed directly by DigitalOcean. Truemail is a lightweight email validation service, and this setup is sufficient for most websites. If you need more processing power or bandwidth, you can upgrade the server later.', 'correct-contact' ); ?></p>
                    <p><?php esc_html_e( 'CorrectContact does not charge for infrastructure and does not add any markup.', 'correct-contact' ); ?></p>

                    <div class="cc-wizard-provision-content">
                        <p>
                            <label for="cc-region"><?php esc_html_e( 'Select datacenter region:', 'correct-contact' ); ?></label><br>
                            <select id="cc-region" class="regular-text">
                                <option value=""><?php esc_html_e( 'Loading regions...', 'correct-contact' ); ?></option>
                            </select>
                        </p>
                    </div>

                    <div class="cc-wizard-provision-controls">
                        <div class="cc-wizard-provision-progress" style="display: none;">
                            <div class="cc-wizard-progress-bar">
                                <div class="cc-wizard-progress-fill"></div>
                            </div>
                            <div class="cc-wizard-progress-status">
                                <ul class="cc-wizard-progress-steps">
                                    <li data-step="project"><?php esc_html_e( 'Creating project', 'correct-contact' ); ?></li>
                                    <li data-step="app"><?php esc_html_e( 'Creating app', 'correct-contact' ); ?></li>
                                    <li data-step="deploy"><?php esc_html_e( 'Deploying Truemail', 'correct-contact' ); ?></li>
                                    <li data-step="finalize"><?php esc_html_e( 'Finalizing configuration', 'correct-contact' ); ?></li>
                                    <li data-step="done"><?php esc_html_e( 'Done!', 'correct-contact' ); ?></li>
                                </ul>
                                <p class="cc-error-message" style="display: none;"></p>
                                <div class="cc-wizard-provision-error-actions" style="display: none;">
                                    <div class="cc-wizard-actions">
                                        <a href="https://cloud.digitalocean.com/account/billing" target="_blank"
                                           class="button button-secondary cc-add-payment"><?php esc_html_e( 'Add payment method', 'correct-contact' ); ?></a>
                                        <button type="button"
                                                class="button button-primary cc-wizard-retry"><?php esc_html_e( 'Retry setup', 'correct-contact' ); ?></button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="cc-wizard-actions">
                        <button type="button"
                                class="button button-primary cc-wizard-provision"
                                disabled><?php esc_html_e( 'Create app', 'correct-contact' ); ?></button>
                    </div>

                    <p class="cc-wizard-footer"><?php esc_html_e( 'This will only take a few minutes', 'correct-contact' ); ?></p>
                </div>

                <!-- Slide 4: Success -->
                <div class="cc-wizard-slide" data-slide="4" style="display: none;">
                    <h2><?php esc_html_e( 'Your Truemail app is up and running!', 'correct-contact' ); ?></h2>
                    <p><?php esc_html_e( 'Your email validation service is ready to validate email addresses in your forms.', 'correct-contact' ); ?></p>
                    <p><?php esc_html_e( 'CorrectContact is now connected to your Truemail app, running entirely in your own DigitalOcean account.', 'correct-contact' ); ?></p>

                    <h3><?php esc_html_e( 'You\'re in control.', 'correct-contact' ); ?></h3>
                    <ul>
                        <li><?php esc_html_e( 'The app runs entirely in your DigitalOcean account', 'correct-contact' ); ?></li>
                        <li><?php esc_html_e( 'No third-party email validation services are used', 'correct-contact' ); ?></li>
                        <li><?php esc_html_e( 'CorrectContact does not process or store email addresses', 'correct-contact' ); ?></li>
                    </ul>

                    <p><?php esc_html_e( 'CorrectContact no longer needs your DigitalOcean API token. You can safely remove it now.', 'correct-contact' ); ?></p>

                    <div class="cc-wizard-actions">
                        <button type="button"
                                class="button button-primary cc-wizard-complete"><?php esc_html_e( 'Continue to settings', 'correct-contact' ); ?></button>
                        <button type="button"
                                class="button button-secondary cc-wizard-remove-token"><?php esc_html_e( 'Remove API token', 'correct-contact' ); ?></button>
                    </div>
                </div>
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

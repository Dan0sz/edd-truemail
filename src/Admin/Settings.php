<?php
/**
 * Truemail for Easy Digital Downloads
 *
 * @package   daandev/correct-contacts
 * @author    Daan van den Bergh
 *            https://daan.dev
 * @copyright © 2023 Daan van den Bergh
 */

namespace CorrectContact\Admin;

defined( 'ABSPATH' ) || exit;

class Settings {
    const ACCESS_TOKEN = 'cc_access_token';

    const APP_URL = 'cc_app_url';

    const BLOCK_PURCHASE = 'cc_block_checkout';

    const FIELD_SELECTORS = 'cc_field_selectors';

    /**
     * Settings constructor.
     */
	public function __construct() {
        add_action( 'admin_menu', [ $this, 'add_menu' ] );
        add_action( 'admin_init', [ $this, 'register_settings' ] );
        add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_assets' ] );
	}

    /**
     * Add the settings menu.
     */
    public function add_menu() {
        add_options_page(
                __( 'Correct Contacts', 'correct-contacts' ),
                __( 'Correct Contacts', 'correct-contacts' ),
                'manage_options',
                'correct-contacts',
                [ $this, 'render_settings_page' ]
        );
	}

	/**
     * Register settings.
	 */
    public function register_settings() {
        register_setting( 'correct-contacts', self::ACCESS_TOKEN );
        register_setting( 'correct-contacts', self::APP_URL );
        register_setting( 'correct-contacts', self::BLOCK_PURCHASE );
        register_setting( 'correct-contacts', self::FIELD_SELECTORS, [
                'sanitize_callback' => [ $this, 'sanitize_selectors' ],
        ] );

        add_settings_section(
                'cc_general_section',
                __( 'General Settings', 'correct-contacts' ),
                null,
                'correct-contacts'
        );

        add_settings_field(
                self::ACCESS_TOKEN,
                __( 'Access Token', 'correct-contacts' ),
                [ $this, 'render_text_field' ],
                'correct-contacts',
                'cc_general_section',
                [ 'id'   => self::ACCESS_TOKEN,
                  'desc' => __( 'Enter the Access Token (environment variable) you\'ve configured in your Truemail instance here.', 'correct-contacts' )
                ]
        );

        add_settings_field(
                self::APP_URL,
                __( 'Application URL', 'correct-contacts' ),
                [ $this, 'render_text_field' ],
                'correct-contacts',
                'cc_general_section',
                [ 'id'   => self::APP_URL,
                  'desc' => __( 'Enter the URL of your Truemail instance here.', 'correct-contacts' )
                ]
        );

        add_settings_field(
                self::BLOCK_PURCHASE,
                __( 'Prevent Purchase on Failure', 'correct-contacts' ),
                [ $this, 'render_checkbox_field' ],
                'correct-contacts',
                'cc_general_section',
                [ 'id'   => self::BLOCK_PURCHASE,
                  'desc' => __( 'If enabled, the user won\'t be able to finalize the purchase if the email address fails to validate. Fails silently on a request timeout.', 'correct-contacts' )
                ]
        );

        add_settings_field(
                self::FIELD_SELECTORS,
                __( 'Field Selectors', 'correct-contacts' ),
                [ $this, 'render_selectors_field' ],
                'correct-contacts',
                'cc_general_section',
                [ 'id'   => self::FIELD_SELECTORS,
                  'desc' => __( 'Add the CSS selectors (classes or IDs) of the email fields you want to validate. Press enter after each entry.', 'correct-contacts' )
                ]
        );
	}

    /**
     * Enqueue Select2 and admin styles for the settings page.
     */
    public function enqueue_assets( $hook ) {
        if ( $hook !== 'settings_page_correct-contacts' ) {
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
					placeholder: '" . __( 'Add selectors...', 'correct-contacts' ) . "'
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
        echo '<input type="checkbox" id="' . esc_attr( $args['id'] ) . '" name="' . esc_attr( $args['id'] ) . '" value="1" ' . checked( 1, $value, false ) . '>';
        echo '<p class="description">' . esc_html( $args['desc'] ) . '</p>';
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
            <form action="options.php" method="post">
                <div class="cc-settings-container">
                    <?php
                    settings_fields( 'correct-contacts' );
                    do_settings_sections( 'correct-contacts' );
                    ?>
                </div>
                <?php submit_button(); ?>
            </form>
        </div>
        <?php
	}
}

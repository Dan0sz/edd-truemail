<?php
/**
 * Admin Notice Handler
 *
 * @package   daandev/correct-contact
 * @author    Daan van den Bergh
 *            https://daan.dev
 * @copyright © 2023-2026 Daan van den Bergh
 */

namespace CorrectContact\Admin;

defined( 'ABSPATH' ) || exit;

class Notice {
	/**
	 * Notice constructor.
	 */
	public function __construct() {
		add_action( 'admin_notices', [ $this, 'setup_notice' ] );
	}

	/**
	 * Check if setup wizard has been completed.
	 *
	 * @return bool
	 */
	private function is_setup_completed() {
		return (bool) get_option( Settings::SETUP_COMPLETED, false );
	}

	/**
	 * Display admin notice if setup is not completed.
	 */
	public function setup_notice() {
		if ( $this->is_setup_completed() ) {
			return;
		}

		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		// Don't show notice on the plugin's settings page
		$screen = get_current_screen();
		if ( $screen && $screen->id === 'settings_page_correct-contact' ) {
			return;
		}

		$settings_url = admin_url( 'options-general.php?page=correct-contact' );
		?>
		<div class="notice notice-info">
			<p>
				<?php esc_html_e( 'Before you can use your CorrectContact email validation service, you must complete the setup wizard.', 'correct-contact' ); ?>
				<a href="<?php echo esc_url( $settings_url ); ?>"><?php esc_html_e( 'Go to settings', 'correct-contact' ); ?></a>
			</p>
		</div>
		<?php
	}
}

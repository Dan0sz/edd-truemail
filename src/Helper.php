<?php
/**
 * Helper Functions
 *
 * @package   daandev/correct-contact
 * @author    Daan van den Bergh
 *            https://daan.dev
 * @copyright © 2023-2026 Daan van den Bergh
 */

namespace CorrectContact;

use CorrectContact\Admin\Settings;

defined( 'ABSPATH' ) || exit;

class Helper {
	/**
	 * Should we display the wizard?
	 *
	 * @return bool
	 */
	public static function should_display_wizard() {
		return ! self::is_setup_completed() && ! self::is_setup_skipped();
	}
	
	/**
	 * Check if the setup wizard has been completed.
	 *
	 * @return bool
	 */
	public static function is_setup_completed() {
		return (bool) get_option( Settings::SETUP_COMPLETED, false );
	}
	
	/**
	 * Check if the setup wizard has been completed.
	 *
	 * @return bool
	 */
	public static function is_setup_skipped() {
		return (bool) get_option( Settings::SETUP_SKIPPED, false );
	}
	
	/**
	 * Render an admin view template.
	 *
	 * @param string $view_name The name of the view file (without .phtml extension).
	 * @param array $data Optional. Data to pass to the view.
	 *
	 * @return void
	 */
	public static function render_admin_view( $view_name, $data = [] ) {
		$view_file = CC_PLUGIN_DIR . 'views/admin/' . $view_name . '.phtml';
		
		if ( ! file_exists( $view_file ) ) {
			return;
		}
		
		// Extract data array to variables for use in the template
		if ( ! empty( $data ) ) {
			extract( $data );
		}
		
		include $view_file;
	}
}

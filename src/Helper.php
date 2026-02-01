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
	 * Check if setup wizard has been completed.
	 *
	 * @return bool
	 */
	public static function is_setup_completed() {
		return (bool) Options::get( Settings::SETUP_COMPLETED );
	}
}

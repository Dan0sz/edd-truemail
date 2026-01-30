<?php
/**
 * Easy Digital Downloads Compatibility
 *
 * @package   daandev/correct-contacts
 * @author    Daan van den Bergh
 *            https://daan.dev
 * @copyright © 2023-2026 Daan van den Bergh
 */

namespace CorrectContacts\Compatibility;

use CorrectContacts\Compatibility;

defined( 'ABSPATH' ) || exit;

class EDD extends Compatibility {
	/**
	 * EDD constructor.
	 *
	 * @return void
	 */
	public function __construct() {
		$this->init();
	}
	
	/**
	 * Initializes the class.
	 *
	 * @return void
	 */
	private function init() {
		add_action( 'edd_checkout_error_checks', [ $this, 'validate_email' ], 10, 2 );
	}
	
	/**
	 * Get the email address from the EDD data array.
	 *
	 * @param mixed $data EDD checkout data array.
	 *
	 * @return string|null The email address or null if not found.
	 */
	protected function get_email_from_data( $data ) {
		return isset( $data['edd_email'] ) ? $data['edd_email'] : null;
	}
	
	/**
	 * Set the validation error for EDD.
	 *
	 * @param mixed $errors Not used for EDD (uses global error handler).
	 *
	 * @return void
	 */
	protected function set_validation_error( $errors ) {
		edd_set_error( 'invalid_email', __( 'The email address you entered either contains a typo or it doesn\'t exist.', 'correct-contacts' ) );
	}
}

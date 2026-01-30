<?php
/**
 * Base Compatibility Class
 *
 * @package   daandev/correct-contacts
 * @author    Daan van den Bergh
 *            https://daan.dev
 * @copyright © 2023-2026 Daan van den Bergh
 */

namespace CorrectContacts;

use CorrectContacts\Admin\Settings;
use CorrectContacts\Ajax;
use WpOrg\Requests\Exception\InvalidArgument;

defined( 'ABSPATH' ) || exit;

abstract class Compatibility {
	/**
	 * Get the email address from the platform-specific data array.
	 *
	 * @param mixed $data Platform-specific data array.
	 *
	 * @return string|null The email address or null if not found.
	 */
	abstract protected function get_email_from_data( $data );
	
	/**
	 * Set the validation error for the platform.
	 *
	 * @param mixed $errors Platform-specific error object/handler.
	 *
	 * @return void
	 */
	abstract protected function set_validation_error( $errors );
	
	/**
	 * Validates the email address for checkout.
	 *
	 * @param mixed $valid_data Platform-specific valid data.
	 * @param mixed $data Platform-specific data array.
	 *
	 * @return void
	 *
	 * @throws InvalidArgument
	 */
	public function validate_email( $valid_data, $data ) {
		if ( empty( get_option( Settings::BLOCK_PURCHASE ) ) ) {
			return;
		}
		
		$email = $this->get_email_from_data( $data );
		
		if ( empty( $email ) ) {
			return;
		}
		
		$email           = sanitize_email( $email );
		$transient_label = sprintf( Ajax::TRANSIENT_LABEL, preg_replace( '/\W/', '_', $email ) );
		$result          = get_transient( $transient_label );
		
		/**
		 * If transient isn't available, this probably means that a logged-in user placed a purchase, without changing
		 * his/her email. Which is a perfectly valid scenario, which is why we fail silently here. Same goes for time-outs.
		 */
		if ( empty( $result ) || $result['code'] === 408 ) {
			return;
		}
		
		if ( ! $result['success'] ) {
			$this->set_validation_error( $valid_data );
		}
	}
}

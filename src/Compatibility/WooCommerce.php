<?php
/**
 * WooCommerce Compatibility
 *
 * @package   daandev/correct-contact
 * @author    Daan van den Bergh
 *            https://daan.dev
 * @copyright © 2023-2026 Daan van den Bergh
 */

namespace CorrectContact\Compatibility;

use CorrectContact\Compatibility;

defined( 'ABSPATH' ) || exit;

class WooCommerce extends Compatibility {
	/**
	 * WooCommerce constructor.
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
		add_action( 'woocommerce_after_checkout_validation', [ $this, 'validate_email' ], 10, 2 );
	}
	
	/**
	 * Get the email address from the WooCommerce data array.
	 *
	 * @param mixed $data WooCommerce checkout data array.
	 *
	 * @return string|null The email address or null if not found.
	 */
	protected function get_email_from_data( $data ) {
		return isset( $data['billing_email'] ) ? $data['billing_email'] : null;
	}
	
	/**
	 * Set the validation error for WooCommerce.
	 *
	 * @param mixed $errors WP_Error object for WooCommerce validation errors.
	 *
	 * @return void
	 */
	protected function set_validation_error( $errors ) {
		$errors->add( 'invalid_email', __( 'The email address you entered either contains a typo or it doesn\'t exist.', 'correct-contact' ) );
	}
}

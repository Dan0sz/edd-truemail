<?php
/**
 * Truemail for Easy Digital Downloads
 *
 * @package   daandev/correct-contacts
 * @author    Daan van den Bergh
 *            https://daan.dev
 * @copyright © 2023 Daan van den Bergh
 */

namespace CorrectContact;

use CorrectContact\Admin\Settings;
use WpOrg\Requests\Exception\InvalidArgument;

defined( 'ABSPATH' ) || exit;

class Plugin {
	/**
	 * Plugin constructor.
	 *
	 * @return void
	 */
	public function __construct() {
		new Admin\Settings();
		new Ajax();
		
		$this->init();
	}
	
	/**
	 * Initializes the class.
	 *
	 * @return void
	 */
	private function init() {
		add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_scripts' ] );
		add_action( 'edd_checkout_error_checks', [ $this, 'validate_email' ], 10, 2 );
	}
	
	/**
	 * Enqueues scripts and styles. Loads minified versions if SCRIPT_DEBUG is true.
	 *
	 * @return void
	 */
	public function enqueue_scripts() {
		$ext = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG === true ? '.min' : '';
		
		wp_enqueue_script( 'correct-contacts', plugin_dir_url( CC_PLUGIN_FILE ) . "assets/js/correct-contacts$ext.js", [
			'wp-util',
		], filemtime( plugin_dir_path( CC_PLUGIN_FILE ) . 'assets/js/correct-contacts.js' ), false );
		wp_enqueue_style( 'correct-contacts', plugin_dir_url( CC_PLUGIN_FILE ) . "assets/css/correct-contacts$ext.css", [], filemtime( plugin_dir_path( CC_PLUGIN_FILE ) . 'assets/css/correct-contacts.css' ) );
		
		$selectors = get_option( Settings::FIELD_SELECTORS, '#edd-email' );
		
		wp_localize_script( 'correct-contacts', 'cc_ajax_obj', [
			'ajax_url'  => admin_url( 'admin-ajax.php' ),
			'selectors' => $selectors,
		] );
	}
	
	/**
	 * Validates the email address.
	 *
	 * @action edd_checkout_error_checks.
	 *
	 * @param mixed $valid_data
	 * @param mixed $data
	 *
	 * @return void
	 *
	 * @throws InvalidArgument
	 */
	public function validate_email( $valid_data, $data ) {
		if ( empty( get_option( Settings::BLOCK_PURCHASE ) ) ) {
			return;
		}
		
		if ( ! isset( $data['edd_email'] ) ) {
			return;
		}
		
		$email           = sanitize_email( $data['edd_email'] );
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
			edd_set_error( 'invalid_email', __( 'The email address you entered either contains a typo or it doesn\'t exist.', 'correct-contacts' ) );
		}
	}
}

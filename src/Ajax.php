<?php
/**
 * Truemail for Easy Digital Downloads
 *
 * @package   daandev/correct-contacts
 * @author    Daan van den Bergh
 *            https://daan.dev
 * @copyright © 2023-2026 Daan van den Bergh
 */

namespace CorrectContacts;

use WpOrg\Requests\Exception\InvalidArgument;

defined( 'ABSPATH' ) || exit;

class Ajax {
	const TRANSIENT_LABEL = 'cc_valid_%s';
	
	/**
	 * Ajax constructor.
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
		add_action( 'wp_ajax_cc_verify_email', [ $this, 'verify' ] );
		add_action( 'wp_ajax_nopriv_cc_verify_email', [ $this, 'verify' ] );
	}
	
	/**
	 * Verifies an email address.
	 *
	 * @return void
	 *
	 * @throws InvalidArgument
	 */
	public function verify() {
		$email    = isset( $_POST['email'] ) ? sanitize_email( $_POST['email'] ) : false;
		$client   = new Client();
		$result   = $client->verify( $email );
		$response = [
			'code'    => $result['code'],
			'success' => false,
		];
		
		if ( ! $result['success'] && $result['code'] === 200 ) {
			$response['message'] = __( 'We couldn\'t verify your email address. Are you sure it\'s spelled correctly?', 'correct-contacts' );
		}
		
		if ( ! $result['success'] && $result['code'] === 400 ) {
			$response['message'] = __( 'Please enter a valid email address.', 'correct-contacts' );
		}
		
		if ( ! $result['success'] && $result['code'] === 408 ) {
			$response['message'] = __( 'Request timed out.', 'correct-contacts' );
		}
		
		if ( $result['success'] && $result['code'] === 200 ) {
			$response['message'] = __( 'Email address verified.', 'correct-contacts' );
			$response['success'] = true;
		}
		
		$transient_label = sprintf( self::TRANSIENT_LABEL, preg_replace( '/\W/', '_', $email ) );
		
		/**
		 * Store result in a transient for 10 minutes, so it doesn't slow down the checkout process.
		 */
		if ( ! empty( $email ) ) {
			set_transient( $transient_label, $response, 600 );
		}
		
		if ( ! $result['success'] ) {
			wp_send_json_error( $response );
		}
		
		wp_send_json_success( $response );
	}
}

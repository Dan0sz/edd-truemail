<?php
/**
 * Truemail for Easy Digital Downloads
 *
 * @package   daandev/edd-truemail
 * @author    Daan van den Bergh
 *            https://daan.dev
 * @copyright © 2023 Daan van den Bergh
 */

namespace EDD\Truemail;

use WpOrg\Requests\Exception\InvalidArgument;

defined( 'ABSPATH' ) || exit;

class Ajax {
	const TRANSIENT_LABEL = 'edd_truemail_valid_%s';
	
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
		add_action( 'wp_ajax_edd_truemail_verify_email', [ $this, 'verify' ] );
		add_action( 'wp_ajax_nopriv_edd_truemail_verify_email', [ $this, 'verify' ] );
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
			$response['message'] = __( 'We couldn\'t verify your email address. Are you sure it\'s spelled correctly?', 'edd-truemail' );
		}
		
		if ( ! $result['success'] && $result['code'] === 400 ) {
			$response['message'] = __( 'Please enter a valid email address.', 'edd-truemail' );
		}
		
		if ( ! $result['success'] && $result['code'] === 408 ) {
			$response['message'] = __( 'Request timed out.', 'edd-truemail' );
		}
		
		if ( $result['success'] && $result['code'] === 200 ) {
			$response['message'] = __( 'Email address verified.', 'edd-truemail' );
			$response['success'] = true;
		}
		
		$transient_label = sprintf( self::TRANSIENT_LABEL, preg_replace( '/\W/', '_', $email ) );
		
		/**
		 * Store result in a transient for 10 minutes, so it doesn't slow down the checkout process.
		 */
		set_transient( $transient_label, $response, 600 );
		
		if ( ! $result['success'] ) {
			wp_send_json_error( $response );
		}
		
		wp_send_json_success( $response );
	}
}

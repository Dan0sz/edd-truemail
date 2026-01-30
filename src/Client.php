<?php
/**
 * Correct Contact - Email validation for WordPress
 *
 * @package   daandev/correct-contact
 * @author    Daan van den Bergh
 *            https://daan.dev
 * @copyright © 2023-2026 Daan van den Bergh
 */

namespace CorrectContact;

use CorrectContact\Admin\Settings;
use CorrectContact\Options;

defined( 'ABSPATH' ) || exit;

class Client {
	/**
	 * @var string Access Token
	 */
	private $token = '';
	
	/**
	 * @var string API URL
	 */
	private $api_url = '';
	
	/**
	 * Client constructor.
	 */
	public function __construct() {
		$this->token   = Options::get( Settings::ACCESS_TOKEN );
		$this->api_url = Options::get( Settings::APP_URL );
	}
	
	/**
	 * Verifies an email address.
	 *
	 * @param string $email
	 *
	 * @return array [ 'success' => bool, 'code' => int ]
	 */
	public function verify( $email ) {
		if ( empty( $this->token ) || empty( $this->api_url ) ) {
			// Not Acceptable
			return [
				'success' => false,
				'code'    => 406,
			];
		}
		
		$email = sanitize_email( $email );
		
		if ( ! $email ) {
			// Bad Request
			return [
				'success' => false,
				'code'    => 400,
			];
		}
		
		$url      = $this->api_url . '?' . http_build_query( [ 'email' => $email ] );
		$response = wp_remote_get(
			$url,
			[
				'timeout' => 15,
				'headers' => [
					'Content-Type'  => 'application/json',
					'Accept'        => 'application/json',
					'Authorization' => $this->token,
				],
			]
		);
		
		if ( is_wp_error( $response ) ) {
			// Timeout
			return [
				'success' => false,
				'code'    => 408,
			];
		}
		
		$body = json_decode( wp_remote_retrieve_body( $response ) );
		
		// Success
		return [
			'success' => $body->success,
			'code'    => 200,
		];
	}
}

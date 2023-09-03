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
	 * Enqueues scripts and styles.
	 *
	 * @return void
	 */
	public function enqueue_scripts() {
		if ( edd_is_checkout() ) {
			wp_enqueue_script( 'edd-truemail', plugin_dir_url( EDD_TM_PLUGIN_FILE ) . 'assets/js/edd-truemail.js', [ 'edd-ajax' ], filemtime( plugin_dir_path( EDD_TM_PLUGIN_FILE ) . 'assets/js/edd-truemail.js' ), false );
			wp_enqueue_style( 'edd-truemail', plugin_dir_url( EDD_TM_PLUGIN_FILE ) . 'assets/css/edd-truemail.css', [], filemtime( plugin_dir_path( EDD_TM_PLUGIN_FILE ) . 'assets/css/edd-truemail.css' ) );
		}
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
		if ( ! isset( $data['edd_email'] ) ) {
			return;
		}

		$email  = sanitize_email( $data['edd_email'] );
		$client = new Client();
		$result = $client->verify( $email );

		if ( ! $result ) {
			edd_set_error( 'invalid_email', __( 'The email address you entered either contains a typo or it doesn\'t exist.', 'edd-truemail' ) );
		}
	}
}
